<?php
declare (strict_types = 1);

namespace crud;

use think\db\exception\DbException;
use think\facade\Db;

/**
 * SchemaInstaller 根据配置创建主表（及 belongs_to_many 中间表）
 *
 * 策略：表不存在则 CREATE；已存在则跳过（不做 ALTER / DROP）
 * 方言对齐 glass_admin.sql 中 example_* 约定（InnoDB / utf8mb4_unicode_ci / Unix 秒时间戳）
 * 列定义来自 field.column（MySQL 类型 + 参数）；belongs_to_many 可无主表物理列
 */
class SchemaInstaller
{
    /**
     * install 执行建表
     *
     * @return array{created: list<string>, skipped: list<string>, sql: list<string>}
     * @throws DbException
     */
    public function install(Context $ctx): array
    {
        $created = [];
        $skipped = [];
        $sqlList = [];

        foreach ($this->buildPlans($ctx) as $plan) {
            $sqlList[] = $plan['sql'];
            if ($this->tableExists($plan['table'])) {
                $skipped[] = $plan['table'];
                continue;
            }
            Db::execute($plan['sql']);
            $created[] = $plan['table'];
        }

        return [
            'created' => $created,
            'skipped' => $skipped,
            'sql'     => $sqlList,
        ];
    }

    /**
     * preview 干跑预览建表计划
     *
     * @return list<array{table: string, exists: bool, sql: string}>
     */
    public function preview(Context $ctx): array
    {
        $items = [];
        foreach ($this->buildPlans($ctx) as $plan) {
            $items[] = [
                'table'  => $plan['table'],
                'exists' => $this->tableExists($plan['table']),
                'sql'    => $plan['sql'],
            ];
        }

        return $items;
    }

    /**
     * buildPlans 生成主表 + 中间表建表计划
     *
     * @return list<array{table: string, sql: string}>
     */
    private function buildPlans(Context $ctx): array
    {
        $plans = [
            [
                'table' => $ctx->table,
                'sql'   => $this->buildMainTableSql($ctx),
            ],
        ];
        foreach ($ctx->belongsToManyFields as $f) {
            $pivot = $this->pivotTableName($ctx, $f);
            $plans[] = [
                'table' => $pivot,
                'sql'   => $this->buildPivotTableSql($ctx, $f, $pivot),
            ];
        }

        return $plans;
    }

    /** buildMainTableSql 生成主表 CREATE TABLE */
    private function buildMainTableSql(Context $ctx): string
    {
        $cols = [];
        $indexes = [];
        $pk = null;

        foreach ($ctx->fields as $f) {
            $col = Context::columnConfig($f);
            if ($col === null) {
                // belongs_to_many 等无主表物理列
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
            if (Context::relationType($f) === 'belongs_to') {
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

        return "CREATE TABLE {$table} (\n{$body}\n) ENGINE=InnoDB CHARACTER SET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT={$comment} ROW_FORMAT=Dynamic";
    }

    /**
     * buildPivotTableSql 生成 belongs_to_many 中间表
     *
     * @param array<string, mixed> $field
     */
    private function buildPivotTableSql(Context $ctx, array $field, string $pivot): string
    {
        $method = Context::relationMethodName($field);
        $rel = Context::relationConfig($field) ?? [];
        $remote = Context::parseRemoteApi(Context::optionsKey($field) ?: null);
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
            . ") ENGINE=InnoDB CHARACTER SET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT={$comment} ROW_FORMAT=Dynamic";
    }

    /**
     * columnDefinition 单列 DDL（按 column.type + MySQL 参数）
     *
     * @param array<string, mixed> $field
     */
    private function columnDefinition(Context $ctx, array $field): string
    {
        $prop = (string) $field['prop'];
        $col = Context::columnConfig($field);
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
            'varchar' => $this->charType('varchar', $col),
            'char' => $this->charType('char', $col),
            'text' => 'text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
            'mediumtext' => 'mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
            'longtext' => 'longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
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

        return "{$base}({$len}) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
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

        // json / text 类通常不写 DEFAULT（除显式）
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
            // 无显式 default 时：可空给 DEFAULT NULL，不可空整型给 0
            if ($nullable) {
                return $nullPart . ' DEFAULT NULL';
            }
            if (in_array($type, ['bigint', 'int', 'tinyint', 'decimal'], true)) {
                return $nullPart . ' DEFAULT 0';
            }

            return $nullPart;
        }

        $def = $col['default'];
        if ($def === null) {
            return $nullPart . ' DEFAULT NULL';
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
     * fieldComment 字段注释
     *
     * @param array<string, mixed> $field
     * @param array<string, mixed> $col
     */
    private function fieldComment(Context $ctx, array $field, string $label, array $col): string
    {
        if (!empty($col['comment'])) {
            return $this->quoteComment((string) $col['comment']);
        }
        if (Context::isBoolField($field)) {
            return $this->quoteComment('{1:是,0:否}');
        }
        if (Context::isEnumField($field)) {
            $key = Context::statusMapKey($field);
            if ($key !== '' && isset($ctx->maps[$key]) && is_array($ctx->maps[$key])) {
                $parts = [];
                foreach ($ctx->maps[$key] as $k => $entry) {
                    $lab = is_array($entry) ? (string) ($entry['label'] ?? $k) : (string) $entry;
                    $parts[] = "{$k}:{$lab}";
                }
                if ($parts !== []) {
                    return $this->quoteComment('{' . implode(',', $parts) . '}');
                }
            }
        }
        if (Context::isUnixDatetime($field)) {
            return $this->quoteComment($label . '（Unix 秒）');
        }
        if (Context::formRender($field) === 'image') {
            return $this->quoteComment($label . '相对路径');
        }
        if (!empty($col['unique'])) {
            return $this->quoteComment($label . '（唯一）');
        }

        return $this->quoteComment($label);
    }

    /**
     * pivotTableName 中间表名
     *
     * @param array<string, mixed> $field
     */
    private function pivotTableName(Context $ctx, array $field): string
    {
        $method = Context::relationMethodName($field);
        $rel = Context::relationConfig($field) ?? [];
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
        // SHOW TABLES LIKE 不支持预处理占位符
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
