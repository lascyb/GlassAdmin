<?php
declare (strict_types = 1);

namespace crud;

use crud\field\FieldMeta;
use crud\field\Naming;
use think\db\exception\DbException;
use think\facade\Db;

/**
 * SchemaInstaller 根据配置创建/同步主表（及 belongs_to_many 中间表）
 *
 * 策略：
 * - 表不存在 → CREATE
 * - 表已存在 → 按字段 diff 同步：
 *   - 无变化：保留
 *   - 有变化：旧列 RENAME 为 `{prop}_update_{时间戳}`，再 ADD 新定义列
 *   - 新增：ADD COLUMN
 *   - 配置中删除：旧列 RENAME 为 `{prop}_delete_{时间戳}`（不 DROP）
 * 已带 `_update_` / `_delete_` 后缀的归档列不参与 diff
 */
class SchemaInstaller
{
    /** archiveSuffixPattern 归档列名后缀 */
    private const string ARCHIVE_SUFFIX_PATTERN = '/_(update|delete)_\d+$/';

    /**
     * install 执行建表/同步
     *
     * @return list<array{table: string, exists: bool, sql: string, status: string}>
     * @throws DbException
     */
    public function install(Context $ctx): array
    {
        $items = [];
        $ts = time();

        foreach ($this->buildPlans($ctx) as $plan) {
            $exists = $this->tableExists($plan['table']);
            if (!$exists) {
                Db::execute($plan['sql']);
                $items[] = [
                    'table'  => $plan['table'],
                    'exists' => false,
                    'sql'    => $plan['sql'],
                    'status' => 'created',
                ];
                continue;
            }

            $alter = $this->buildAlterPlan($ctx, $plan['table'], $plan['kind'], $plan['field'] ?? null, $ts);
            if ($alter['statements'] === []) {
                $items[] = [
                    'table'  => $plan['table'],
                    'exists' => true,
                    'sql'    => '',
                    'status' => 'unchanged',
                ];
                continue;
            }

            foreach ($alter['statements'] as $sql) {
                Db::execute($sql);
            }
            $items[] = [
                'table'  => $plan['table'],
                'exists' => true,
                'sql'    => implode(";\n", $alter['statements']),
                'status' => 'altered',
            ];
        }

        return $items;
    }

    /**
     * preview 干跑预览建表/同步计划
     *
     * @return list<array{table: string, exists: bool, sql: string, status: string}>
     */
    public function preview(Context $ctx): array
    {
        $items = [];
        $ts = time();

        foreach ($this->buildPlans($ctx) as $plan) {
            $exists = $this->tableExists($plan['table']);
            if (!$exists) {
                $items[] = [
                    'table'  => $plan['table'],
                    'exists' => false,
                    'sql'    => $plan['sql'],
                    'status' => 'pending',
                ];
                continue;
            }

            $alter = $this->buildAlterPlan($ctx, $plan['table'], $plan['kind'], $plan['field'] ?? null, $ts);
            if ($alter['statements'] === []) {
                $items[] = [
                    'table'  => $plan['table'],
                    'exists' => true,
                    'sql'    => '',
                    'status' => 'unchanged',
                ];
                continue;
            }

            $items[] = [
                'table'  => $plan['table'],
                'exists' => true,
                'sql'    => implode(";\n", $alter['statements']),
                'status' => 'will_alter',
            ];
        }

        return $items;
    }

    /**
     * buildPlans 生成主表 + 中间表建表计划
     *
     * @return list<array{table: string, sql: string, kind: string, field?: array<string, mixed>}>
     */
    private function buildPlans(Context $ctx): array
    {
        $plans = [
            [
                'table' => $ctx->table,
                'sql'   => $this->buildMainTableSql($ctx),
                'kind'  => 'main',
            ],
        ];
        foreach ($ctx->belongsToManyFields as $f) {
            $pivot = $this->pivotTableName($ctx, $f);
            $plans[] = [
                'table' => $pivot,
                'sql'   => $this->buildPivotTableSql($ctx, $f, $pivot),
                'kind'  => 'pivot',
                'field' => $f,
            ];
        }

        return $plans;
    }

    /**
     * buildAlterPlan 对已存在表生成同步 SQL
     *
     * @param array<string, mixed>|null $pivotField
     * @return array{statements: list<string>}
     */
    private function buildAlterPlan(
        Context $ctx,
        string $table,
        string $kind,
        ?array $pivotField,
        int $ts,
    ): array {
        $existing = $this->fetchActiveColumns($table);
        $desired = $kind === 'pivot' && $pivotField !== null
            ? $this->desiredPivotColumns($ctx, $pivotField)
            : $this->desiredMainColumns($ctx);

        $statements = [];
        $tableSql = $this->quoteIdent($table);

        // 删除：库中有、配置无 → rename _delete_
        foreach ($existing as $name => $row) {
            if (isset($desired[$name])) {
                continue;
            }
            if (!empty($row['primary'])) {
                throw new \InvalidArgumentException("不能删除主键列: {$table}.{$name}");
            }
            $archived = $this->archiveName($name, 'delete', $ts);
            $statements[] = "ALTER TABLE {$tableSql} RENAME COLUMN `{$name}` TO `{$archived}`";
        }

        // 新增 / 变更
        foreach ($desired as $name => $spec) {
            if (!isset($existing[$name])) {
                $colDef = $spec['definition'];
                $statements[] = "ALTER TABLE {$tableSql} ADD COLUMN {$colDef}";
                foreach ($spec['indexes'] as $idxSql) {
                    if (preg_match('/\b(?:UNIQUE\s+)?INDEX\s+`([^`]+)`/i', $idxSql, $m)
                        && $this->indexExists($table, $m[1])) {
                        $statements[] = "ALTER TABLE {$tableSql} DROP INDEX `{$m[1]}`";
                    }
                    $statements[] = "ALTER TABLE {$tableSql} ADD {$idxSql}";
                }
                continue;
            }

            $oldFp = $this->existingFingerprint($existing[$name]);
            $newFp = $spec['fingerprint'];
            if ($oldFp === $newFp) {
                // 列定义一致：仅补齐缺失索引（仅索引变更场景）
                foreach ($this->ensureIndexStatements($table, $tableSql, $spec['indexes']) as $sql) {
                    $statements[] = $sql;
                }
                continue;
            }

            if (!empty($existing[$name]['primary']) || !empty($spec['primary'])) {
                throw new \InvalidArgumentException("不支持变更主键列定义: {$table}.{$name}");
            }

            // 类型（含 auto_increment）未变，仅默认值/可空/注释差异 → 原地 MODIFY，保留列数据
            $oldParts = explode('|', $oldFp);
            $newParts = explode('|', $newFp);
            $typeSame = ($oldParts[0] ?? '') === ($newParts[0] ?? '')
                && ($oldParts[3] ?? '') === ($newParts[3] ?? '');
            if ($typeSame) {
                $statements[] = "ALTER TABLE {$tableSql} MODIFY COLUMN {$spec['definition']}";
                foreach ($this->ensureIndexStatements($table, $tableSql, $spec['indexes']) as $sql) {
                    $statements[] = $sql;
                }
                continue;
            }

            // 类型变更（可能丢数据）→ 保守归档重建：旧列 rename 归档，再建新列
            $archived = $this->archiveName($name, 'update', $ts);
            // RENAME 后索引名仍占用，需先删掉本列常用索引名，再为新列重建
            foreach ($this->columnIndexNames($name, $spec['indexes']) as $idxName) {
                if ($this->indexExists($table, $idxName)) {
                    $statements[] = "ALTER TABLE {$tableSql} DROP INDEX `{$idxName}`";
                }
            }
            $statements[] = "ALTER TABLE {$tableSql} RENAME COLUMN `{$name}` TO `{$archived}`";
            $statements[] = "ALTER TABLE {$tableSql} ADD COLUMN {$spec['definition']}";
            foreach ($spec['indexes'] as $idxSql) {
                $statements[] = "ALTER TABLE {$tableSql} ADD {$idxSql}";
            }
        }

        return ['statements' => $statements];
    }

    /**
     * ensureIndexStatements 为已存在列补齐缺失索引（已存在则跳过）
     *
     * @param list<string> $indexes
     * @return list<string>
     */
    private function ensureIndexStatements(string $table, string $tableSql, array $indexes): array
    {
        $out = [];
        foreach ($indexes as $idxSql) {
            if (preg_match('/\b(?:UNIQUE\s+)?INDEX\s+`([^`]+)`/i', $idxSql, $m)
                && $this->indexExists($table, $m[1])) {
                continue;
            }
            $out[] = "ALTER TABLE {$tableSql} ADD {$idxSql}";
        }

        return $out;
    }

    /**
     * desiredMainColumns 主表期望列
     *
     * @return array<string, array{definition: string, fingerprint: string, primary: bool, indexes: list<string>}>
     */
    private function desiredMainColumns(Context $ctx): array
    {
        $out = [];
        foreach ($ctx->fields as $f) {
            $col = FieldMeta::columnConfig($f);
            if ($col === null) {
                continue;
            }
            $prop = (string) ($f['prop'] ?? '');
            if ($prop === '' || !preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $prop)) {
                throw new \InvalidArgumentException("非法字段名: {$prop}");
            }
            $indexes = [];
            if (!empty($col['unique'])) {
                $indexes[] = "UNIQUE INDEX `uk_{$prop}`(`{$prop}` ASC) USING BTREE";
            }
            if (FieldMeta::relationType($f) === 'belongs_to') {
                $indexes[] = "INDEX `idx_{$prop}`(`{$prop}` ASC) USING BTREE";
            }
            $out[$prop] = [
                'definition'  => $this->columnDefinition($ctx, $f),
                'fingerprint' => $this->desiredFingerprint($ctx, $f),
                'primary'     => !empty($col['primary']),
                'indexes'     => $indexes,
            ];
        }

        return $out;
    }

    /**
     * desiredPivotColumns 中间表期望列
     *
     * @param array<string, mixed> $field
     * @return array<string, array{definition: string, fingerprint: string, primary: bool, indexes: list<string>}>
     */
    private function desiredPivotColumns(Context $ctx, array $field): array
    {
        $method = Naming::relationMethodName($field);
        $rel = FieldMeta::relationConfig($field) ?? [];
        $remote = Naming::parseRemoteApi(FieldMeta::remoteApi($field) ?: null);
        $relatedEntity = $remote['entity'] ?? ucfirst(rtrim($method, 's'));
        $fk = (string) ($rel['foreignKey'] ?? (strtolower($ctx->entity) . '_id'));
        $rk = (string) ($rel['relatedKey'] ?? (strtolower($relatedEntity) . '_id'));
        foreach ([$fk, $rk] as $col) {
            if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $col)) {
                throw new \InvalidArgumentException("非法中间表字段名: {$col}");
            }
        }

        $fkDef = "`{$fk}` bigint UNSIGNED NOT NULL COMMENT " . $this->quoteComment("{$ctx->table}.id");
        $rkDef = "`{$rk}` bigint UNSIGNED NOT NULL COMMENT " . $this->quoteComment('关联 id');

        return [
            $fk => [
                'definition'  => $fkDef,
                'fingerprint' => $this->normalizeFingerprint('bigint unsigned', false, null, false, "{$ctx->table}.id"),
                'primary'     => true,
                'indexes'     => [],
            ],
            $rk => [
                'definition'  => $rkDef,
                'fingerprint' => $this->normalizeFingerprint('bigint unsigned', false, null, false, '关联 id'),
                'primary'     => true,
                'indexes'     => ["INDEX `idx_{$rk}`(`{$rk}` ASC) USING BTREE"],
            ],
        ];
    }

    /**
     * fetchActiveColumns 读取表中未归档列
     *
     * @return array<string, array{name: string, type: string, null: bool, default: mixed, autoIncrement: bool, primary: bool, comment: string}>
     */
    private function fetchActiveColumns(string $table): array
    {
        $prefix = (string) Db::getConfig('prefix');
        $full = $prefix . $table;
        $quoted = '`' . str_replace('`', '``', $full) . '`';
        $rows = Db::query("SHOW FULL COLUMNS FROM {$quoted}");
        $out = [];
        foreach ($rows as $row) {
            $name = (string) ($row['Field'] ?? '');
            if ($name === '' || preg_match(self::ARCHIVE_SUFFIX_PATTERN, $name)) {
                continue;
            }
            $extra = strtolower((string) ($row['Extra'] ?? ''));
            $out[$name] = [
                'name'          => $name,
                'type'          => strtolower((string) ($row['Type'] ?? '')),
                'null'          => strtoupper((string) ($row['Null'] ?? '')) === 'YES',
                'default'       => $row['Default'] ?? null,
                'autoIncrement' => str_contains($extra, 'auto_increment'),
                'primary'       => strtoupper((string) ($row['Key'] ?? '')) === 'PRI',
                'comment'       => (string) ($row['Comment'] ?? ''),
            ];
        }

        return $out;
    }

    /**
     * existingFingerprint 已有列指纹
     *
     * @param array{type: string, null: bool, default: mixed, autoIncrement: bool, comment: string} $col
     */
    private function existingFingerprint(array $col): string
    {
        return $this->normalizeFingerprint(
            $col['type'],
            $col['null'],
            $col['default'],
            $col['autoIncrement'],
            $col['comment'],
        );
    }

    /**
     * desiredFingerprint 配置列指纹
     *
     * @param array<string, mixed> $field
     */
    private function desiredFingerprint(Context $ctx, array $field): string
    {
        $col = FieldMeta::columnConfig($field);
        if ($col === null) {
            throw new \InvalidArgumentException('缺少 column');
        }
        $type = (string) ($col['type'] ?? '');
        $typeSql = strtolower($this->mysqlTypeSql($col));
        // 去掉字符集片段，与 SHOW COLUMNS 的 Type 对齐
        $typeSql = preg_replace('/\s+character set\s+\w+/i', '', $typeSql) ?? $typeSql;
        $typeSql = preg_replace('/\s+collate\s+\w+/i', '', $typeSql) ?? $typeSql;
        $typeSql = trim(preg_replace('/\s+/', ' ', $typeSql) ?? $typeSql);

        $hasNullable = array_key_exists('nullable', $col);
        $nullable = $hasNullable ? (bool) $col['nullable'] : $this->defaultNullable($type);

        $default = null;
        $hasDefault = array_key_exists('default', $col);
        if ($hasDefault) {
            $default = $col['default'];
        } elseif (empty($col['autoIncrement']) && empty($col['primary'])) {
            if ($nullable) {
                $default = null;
            } elseif (in_array($type, ['bigint', 'int', 'tinyint', 'decimal', 'float'], true)) {
                $default = 0;
            }
        }
        // json/text 无默认
        if (in_array($type, ['json', 'text', 'mediumtext', 'longtext'], true) && !$hasDefault) {
            $default = null;
        }

        $label = (string) ($field['label'] ?? $field['prop']);
        $comment = !empty($col['comment'])
            ? (string) $col['comment']
            : $this->fieldCommentRaw($ctx, $field, $label, $col);

        return $this->normalizeFingerprint(
            $typeSql,
            $nullable,
            $default,
            !empty($col['autoIncrement']),
            $comment,
        );
    }

    /**
     * normalizeFingerprint 统一指纹
     */
    private function normalizeFingerprint(
        string $type,
        bool $nullable,
        mixed $default,
        bool $autoIncrement,
        string $comment,
    ): string {
        $type = strtolower(trim($type));
        $type = preg_replace('/\s+character set\s+\w+/i', '', $type) ?? $type;
        $type = preg_replace('/\s+collate\s+\w+/i', '', $type) ?? $type;
        $type = trim(preg_replace('/\s+/', ' ', $type) ?? $type);
        // 去掉括号参数里逗号后的空格：decimal(10, 2) 与 SHOW COLUMNS 的 decimal(10,2) 对齐
        $type = preg_replace('/,\s+/', ',', $type) ?? $type;

        if ($default === null) {
            $def = 'NULL';
        } elseif (is_bool($default)) {
            $def = $default ? '1' : '0';
        } else {
            $def = (string) $default;
            // 数值默认统一规范：0 与 0.00、1.5 与 1.50 视为相同（排除 01 这类前导零字符串）
            if (is_numeric($def) && preg_match('/^-?0\d/', $def) !== 1) {
                $normalized = rtrim(rtrim(number_format((float) $def, 6, '.', ''), '0'), '.');
                $def = ($normalized === '' || $normalized === '-0') ? '0' : $normalized;
            }
        }

        return implode('|', [
            $type,
            $nullable ? '1' : '0',
            $def,
            $autoIncrement ? '1' : '0',
            trim($comment),
        ]);
    }

    /** archiveName 生成归档列名 */
    private function archiveName(string $prop, string $action, int $ts): string
    {
        $name = "{$prop}_{$action}_{$ts}";
        if (strlen($name) > 64) {
            $keep = 64 - strlen("_{$action}_{$ts}");
            $name = substr($prop, 0, max(1, $keep)) . "_{$action}_{$ts}";
        }
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $name)) {
            throw new \InvalidArgumentException("非法归档列名: {$name}");
        }

        return $name;
    }

    /**
     * columnIndexNames 本列可能占用的索引名（uk_/idx_ + 计划索引）
     *
     * @param list<string> $indexSqls
     * @return list<string>
     */
    private function columnIndexNames(string $prop, array $indexSqls): array
    {
        $names = ["uk_{$prop}", "idx_{$prop}"];
        foreach ($indexSqls as $sql) {
            if (preg_match('/\b(?:UNIQUE\s+)?INDEX\s+`([^`]+)`/i', $sql, $m)) {
                $names[] = $m[1];
            }
        }

        return array_values(array_unique($names));
    }

    /** indexExists 索引是否存在 */
    private function indexExists(string $table, string $indexName): bool
    {
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $indexName)) {
            return false;
        }
        $prefix = (string) Db::getConfig('prefix');
        $full = $prefix . $table;
        $dbName = (string) Db::query('SELECT DATABASE() AS db')[0]['db'];
        $rows = Db::query(
            'SELECT 1 AS ok FROM information_schema.statistics'
            . ' WHERE table_schema = ? AND table_name = ? AND index_name = ? LIMIT 1',
            [$dbName, $full, $indexName],
        );

        return $rows !== [];
    }

    /** buildMainTableSql 生成主表 CREATE TABLE */
    private function buildMainTableSql(Context $ctx): string
    {
        $cols = [];
        $indexes = [];
        $pk = null;

        foreach ($ctx->fields as $f) {
            $col = FieldMeta::columnConfig($f);
            if ($col === null) {
                continue;
            }
            $prop = (string) ($f['prop'] ?? '');
            if ($prop === '' || !preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $prop)) {
                throw new \InvalidArgumentException("非法字段名: {$prop}");
            }
            $cols[] = '  ' . $this->columnDefinition($ctx, $f);
            if (!empty($col['primary'])) {
                if ($pk !== null) {
                    throw new \InvalidArgumentException('仅支持单一主键');
                }
                $pk = $prop;
            }
            if (!empty($col['unique'])) {
                $indexes[] = "  UNIQUE INDEX `uk_{$prop}`(`{$prop}` ASC) USING BTREE";
            }
            if (FieldMeta::relationType($f) === 'belongs_to') {
                $indexes[] = "  INDEX `idx_{$prop}`(`{$prop}` ASC) USING BTREE";
            }
        }

        if ($pk === null) {
            throw new \InvalidArgumentException('配置缺少 primary 列，无法建表');
        }

        $parts = $cols;
        $parts[] = "  PRIMARY KEY (`{$pk}`) USING BTREE";
        foreach ($indexes as $idx) {
            $parts[] = $idx;
        }

        $table = $this->quoteIdent($ctx->table);
        $comment = $this->quoteComment($ctx->title);
        $body = implode(",\n", $parts);

        return "CREATE TABLE {$table} (\n{$body}\n) ENGINE=InnoDB CHARACTER SET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT={$comment} ROW_FORMAT=Dynamic";
    }

    /**
     * buildPivotTableSql 生成 belongs_to_many 中间表
     *
     * @param array<string, mixed> $field
     */
    private function buildPivotTableSql(Context $ctx, array $field, string $pivot): string
    {
        $method = Naming::relationMethodName($field);
        $rel = FieldMeta::relationConfig($field) ?? [];
        $remote = Naming::parseRemoteApi(FieldMeta::remoteApi($field) ?: null);
        $relatedEntity = $remote['entity'] ?? ucfirst(rtrim($method, 's'));
        $fk = (string) ($rel['foreignKey'] ?? (strtolower($ctx->entity) . '_id'));
        $rk = (string) ($rel['relatedKey'] ?? (strtolower($relatedEntity) . '_id'));
        foreach ([$fk, $rk] as $col) {
            if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $col)) {
                throw new \InvalidArgumentException("非法中间表字段名: {$col}");
            }
        }

        $label = (string) ($field['label'] ?? $method);
        $table = $this->quoteIdent($pivot);
        $comment = $this->quoteComment("{$ctx->title}-{$label}");

        return "CREATE TABLE {$table} (\n"
            . "  `{$fk}` bigint UNSIGNED NOT NULL COMMENT " . $this->quoteComment("{$ctx->table}.id") . ",\n"
            . "  `{$rk}` bigint UNSIGNED NOT NULL COMMENT " . $this->quoteComment('关联 id') . ",\n"
            . "  PRIMARY KEY (`{$fk}`, `{$rk}`) USING BTREE,\n"
            . "  INDEX `idx_{$rk}`(`{$rk}` ASC) USING BTREE\n"
            . ") ENGINE=InnoDB CHARACTER SET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT={$comment} ROW_FORMAT=Dynamic";
    }

    /**
     * columnDefinition 单列 DDL（按 column.type + MySQL 参数）
     *
     * @param array<string, mixed> $field
     */
    private function columnDefinition(Context $ctx, array $field): string
    {
        $prop = (string) $field['prop'];
        $col = FieldMeta::columnConfig($field);
        if ($col === null) {
            throw new \InvalidArgumentException("字段 {$prop} 缺少 column");
        }
        $type = (string) ($col['type'] ?? '');
        if ($type === '') {
            throw new \InvalidArgumentException("字段 {$prop} 缺少 column.type");
        }
        $label = (string) ($field['label'] ?? $prop);
        $comment = $this->fieldComment($ctx, $field, $label, $col);

        $typeSql = $this->mysqlTypeSql($col);
        $nullSql = $this->nullDefaultSql($col, $type);
        $extra = '';
        if (!empty($col['autoIncrement'])) {
            $extra = ' AUTO_INCREMENT';
        }

        return "`{$prop}` {$typeSql}{$nullSql}{$extra} COMMENT {$comment}";
    }

    /**
     * mysqlTypeSql 类型片段（含长度/精度/字符集）
     *
     * @param array<string, mixed> $col
     */
    private function mysqlTypeSql(array $col): string
    {
        $type = (string) $col['type'];
        $unsigned = !empty($col['unsigned']) ? ' UNSIGNED' : '';

        return match ($type) {
            'bigint' => 'bigint' . $unsigned,
            'int' => 'int' . $unsigned,
            'tinyint' => 'tinyint' . $unsigned,
            'decimal' => $this->decimalType($col),
            'float' => 'float',
            'varchar' => $this->charType('varchar', $col),
            'char' => $this->charType('char', $col),
            'text' => 'text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci',
            'mediumtext' => 'mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci',
            'longtext' => 'longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci',
            'date' => 'date',
            'datetime' => 'datetime',
            'timestamp' => 'timestamp',
            'json' => 'json',
            default => throw new \InvalidArgumentException("不支持的 column.type: {$type}"),
        };
    }

    /**
     * charType varchar/char
     *
     * @param array<string, mixed> $col
     */
    private function charType(string $base, array $col): string
    {
        $len = (int) ($col['length'] ?? 255);
        if ($len <= 0) {
            $len = 255;
        }
        if ($len > 16383) {
            $len = 16383;
        }

        return "{$base}({$len}) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci";
    }

    /**
     * decimalType decimal(M,D)
     *
     * @param array<string, mixed> $col
     */
    private function decimalType(array $col): string
    {
        $precision = (int) ($col['precision'] ?? 12);
        $scale = (int) ($col['scale'] ?? 2);
        if ($precision < 1) {
            $precision = 12;
        }
        if ($scale < 0) {
            $scale = 0;
        }
        if ($scale > $precision) {
            $scale = $precision;
        }

        return "decimal({$precision}, {$scale})";
    }

    /**
     * nullDefaultSql NULL/NOT NULL + DEFAULT
     *
     * @param array<string, mixed> $col
     */
    private function nullDefaultSql(array $col, string $type): string
    {
        $hasNullable = array_key_exists('nullable', $col);
        $hasDefault = array_key_exists('default', $col);
        $nullable = $hasNullable ? (bool) $col['nullable'] : $this->defaultNullable($type);

        if (in_array($type, ['json', 'text', 'mediumtext', 'longtext'], true)) {
            if ($nullable) {
                return ' NULL';
            }

            return ' NOT NULL';
        }

        $nullPart = $nullable ? ' NULL' : ' NOT NULL';
        if (!$hasDefault) {
            if (!empty($col['autoIncrement']) || !empty($col['primary'])) {
                return $nullPart;
            }
            if ($nullable) {
                return $nullPart . ' DEFAULT NULL';
            }
            if (in_array($type, ['bigint', 'int', 'tinyint', 'decimal', 'float'], true)) {
                return $nullPart . ' DEFAULT 0';
            }

            return $nullPart;
        }

        $def = $col['default'];
        if ($def === null) {
            if ($nullable) {
                return $nullPart . ' DEFAULT NULL';
            }
            if (in_array($type, ['bigint', 'int', 'tinyint', 'decimal', 'float'], true)) {
                return $nullPart . ' DEFAULT 0';
            }

            return $nullPart;
        }
        if (is_int($def) || is_float($def) || (is_string($def) && is_numeric($def) && !preg_match('/^0\d/', $def))) {
            return $nullPart . ' DEFAULT ' . $def;
        }

        return $nullPart . ' DEFAULT ' . $this->quoteComment((string) $def);
    }

    /** defaultNullable 省略 nullable 时的默认 */
    private function defaultNullable(string $type): bool
    {
        return in_array($type, ['json', 'text', 'mediumtext', 'longtext', 'date', 'datetime', 'timestamp'], true);
    }

    /**
     * fieldComment 字段注释（带引号）
     *
     * @param array<string, mixed> $field
     * @param array<string, mixed> $col
     */
    private function fieldComment(Context $ctx, array $field, string $label, array $col): string
    {
        return $this->quoteComment($this->fieldCommentRaw($ctx, $field, $label, $col));
    }

    /**
     * fieldCommentRaw 字段注释原文
     *
     * @param array<string, mixed> $field
     * @param array<string, mixed> $col
     */
    private function fieldCommentRaw(Context $ctx, array $field, string $label, array $col): string
    {
        if (!empty($col['comment'])) {
            return (string) $col['comment'];
        }
        if (FieldMeta::isBoolField($field)) {
            return '{1:是,0:否}';
        }
        if (FieldMeta::isEnumField($field)) {
            $key = FieldMeta::enumKey($field);
            if ($key !== '' && isset($ctx->options[$key]) && is_array($ctx->options[$key])) {
                $parts = [];
                foreach ($ctx->options[$key] as $item) {
                    if (!is_array($item)) {
                        continue;
                    }
                    $k = $item['value'] ?? '';
                    $lab = (string) ($item['label'] ?? $k);
                    $parts[] = "{$k}:{$lab}";
                }
                if ($parts !== []) {
                    return '{' . implode(',', $parts) . '}';
                }
            }
        }
        if (FieldMeta::isUnixDatetime($field)) {
            return $label . '（Unix 秒）';
        }
        if (in_array(FieldMeta::formRender($field), ['image', 'file', 'image_upload'], true)) {
            return $label . '相对路径';
        }
        if (!empty($col['unique'])) {
            return $label . '（唯一）';
        }

        return $label;
    }

    /**
     * pivotTableName 中间表名
     *
     * @param array<string, mixed> $field
     */
    private function pivotTableName(Context $ctx, array $field): string
    {
        $method = Naming::relationMethodName($field);
        $rel = FieldMeta::relationConfig($field) ?? [];
        $pivot = (string) ($rel['pivotTable'] ?? ($ctx->table . '_' . rtrim($method, 's')));
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $pivot)) {
            throw new \InvalidArgumentException("非法中间表名: {$pivot}");
        }

        return $pivot;
    }

    /** tableExists 表是否已存在（含前缀） */
    private function tableExists(string $table): bool
    {
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $table)) {
            throw new \InvalidArgumentException("非法表名: {$table}");
        }
        $prefix = (string) Db::getConfig('prefix');
        $full = $prefix . $table;
        $like = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $full);
        $like = str_replace("'", "''", $like);
        $rows = Db::query("SHOW TABLES LIKE '{$like}'");

        return $rows !== [];
    }

    /** quoteIdent 反引号标识符 */
    private function quoteIdent(string $name): string
    {
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $name)) {
            throw new \InvalidArgumentException("非法标识符: {$name}");
        }
        $prefix = (string) Db::getConfig('prefix');

        return '`' . str_replace('`', '``', $prefix . $name) . '`';
    }

    /** quoteComment SQL 字符串字面量（注释） */
    private function quoteComment(string $text): string
    {
        return "'" . str_replace(["\\", "'"], ["\\\\", "\\'"], $text) . "'";
    }
}
