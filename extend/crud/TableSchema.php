<?php
declare (strict_types = 1);

namespace crud;

/**
 * TableSchema 数据表结构反推为 CrudGenerateConfig 草稿
 */
class TableSchema
{
    /** isSystemTable 是否系统/框架表（不参与 CRUD 反推） */
    public static function isSystemTable(string $name): bool
    {
        $exact = [
            'migrations', 'migration', 'phinxlog', 'think_migrations',
            'auth_admin', 'auth_admin_role', 'auth_admin_operation_log', 'auth_oauth2_feishu',
            'auth_role', 'auth_role_rule', 'auth_rule', 'auth_rule_api',
            'system_config', 'system_rsa_key', 'dev_crud_history',
        ];
        if (in_array($name, $exact, true)) {
            return true;
        }
        foreach (['auth_', 'system_', 'dev_'] as $prefix) {
            if (str_starts_with($name, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * mapTableToConfig 表列 → CrudGenerateConfig 草稿
     *
     * @param list<array<string, mixed>> $columns
     * @return array<string, mixed>
     */
    public static function mapTableToConfig(string $table, array $columns): array
    {
        $meta = self::guessMetaFromTable($table);
        $fields = [];
        foreach ($columns as $col) {
            $fields[] = self::mapColumnToField($col);
        }

        return [
            'table'        => $table,
            'title'        => $meta['title'],
            'module'       => $meta['module'],
            'entity'       => $meta['entity'],
            'menus'        => $meta['menus'],
            'paths'        => $meta['paths'],
            'generateAuth' => true,
            'writeMode'    => 'skip',
            'fields'       => $fields,
        ];
    }

    /**
     * guessMetaFromTable 从表名粗推 menus/paths 及派生字段
     *
     * @return array{module: string, entity: string, title: string, menus: list<array<string, mixed>>, paths: array<string, string>}
     */
    private static function guessMetaFromTable(string $table): array
    {
        $parts = array_values(array_filter(explode('_', $table), static fn ($p) => $p !== ''));
        $menus = self::guessMenusFromParts($parts);
        $paths = self::guessPathsFromParts($parts);
        $parsed = \crud\Config::parseControllerPath($paths['controller']);
        $leaf = $menus[count($menus) - 1] ?? null;
        $title = (string) ($leaf['title'] ?? ($table !== '' ? $table : 'Item'));

        return [
            'module' => $parsed['module'],
            'entity' => $parsed['entity'],
            'title'  => $title,
            'menus'  => $menus,
            'paths'  => $paths,
        ];
    }

    /**
     * guessMenusFromParts 末段为 view，前面为 menu；单段仅 view
     *
     * @param list<string> $parts
     * @return list<array{type: string, path: string, title: string, icon?: string, sort?: int, enabled: bool}>
     */
    private static function guessMenusFromParts(array $parts): array
    {
        if ($parts === []) {
            return [[
                'type'    => 'view',
                'path'    => 'item',
                'title'   => 'Item',
                'icon'    => 'el:Document',
                'sort'    => 0,
                'enabled' => true,
            ]];
        }
        if (count($parts) === 1) {
            $p = strtolower($parts[0]);

            return [[
                'type'    => 'view',
                'path'    => $p,
                'title'   => ucfirst(strtolower($parts[0])),
                'icon'    => 'el:Document',
                'sort'    => 0,
                'enabled' => true,
            ]];
        }
        $menus = [];
        $menuParts = array_slice($parts, 0, -1);
        foreach ($menuParts as $i => $p) {
            $row = [
                'type'    => 'menu',
                'path'    => strtolower($p),
                'title'   => ucfirst(strtolower($p)),
                'icon'    => 'el:Folder',
                'enabled' => true,
            ];
            if ($i === 0) {
                $row['sort'] = 0;
            }
            $menus[] = $row;
        }
        $last = $parts[count($parts) - 1];
        $menus[] = [
            'type'    => 'view',
            'path'    => strtolower($last),
            'title'   => ucfirst(strtolower($last)),
            'icon'    => 'el:Document',
            'enabled' => true,
        ];

        return $menus;
    }

    /**
     * guessPathsFromParts 按下划线逐级拆目录，末段为类名：a_b_c → a/b/C.php
     *
     * @param list<string> $parts
     * @return array{controller: string, model: string, validate: string, apis: string, viewsDir: string}
     */
    private static function guessPathsFromParts(array $parts): array
    {
        if ($parts === []) {
            return [
                'controller' => 'Item.php',
                'model'      => 'Item.php',
                'validate'   => 'Item.php',
                'apis'       => 'item.ts',
                'viewsDir'   => 'item/',
            ];
        }
        $last = $parts[count($parts) - 1];
        $entity = \crud\Config::safeEntityClassName(ucfirst(strtolower($last)));
        $file = strtolower($last);
        $dirs = array_map(static fn ($p) => strtolower($p), array_slice($parts, 0, -1));
        $prefix = $dirs === [] ? '' : implode('/', $dirs) . '/';

        return [
            'controller' => $prefix . $entity . '.php',
            'model'      => $prefix . $entity . '.php',
            'validate'   => $prefix . $entity . '.php',
            'apis'       => $prefix . $file . '.ts',
            'viewsDir'   => $prefix . $file . '/',
        ];
    }

    /**
     * mapColumnToField SHOW FULL COLUMNS 行 → CrudFieldSchema（对齐前端层结构）
     *
     * @param array<string, mixed> $col
     * @return array<string, mixed>
     */
    private static function mapColumnToField(array $col): array
    {
        $prop = (string) ($col['Field'] ?? '');
        $typeRaw = strtolower((string) ($col['Type'] ?? 'varchar'));
        $null = strtoupper((string) ($col['Null'] ?? '')) === 'YES';
        $key = strtoupper((string) ($col['Key'] ?? ''));
        $extra = strtolower((string) ($col['Extra'] ?? ''));
        $default = $col['Default'] ?? null;
        $comment = trim((string) ($col['Comment'] ?? ''));

        $parsed = self::parseMysqlType($typeRaw);
        $column = [
            'enabled'  => true,
            'type'     => $parsed['type'],
            'nullable' => $null,
        ];
        if ($parsed['length'] !== null) {
            $column['length'] = $parsed['length'];
        }
        if ($parsed['precision'] !== null) {
            $column['precision'] = $parsed['precision'];
        }
        if ($parsed['scale'] !== null) {
            $column['scale'] = $parsed['scale'];
        }
        if ($parsed['unsigned']) {
            $column['unsigned'] = true;
        }
        if ($key === 'PRI') {
            $column['primary'] = true;
        }
        if (str_contains($extra, 'auto_increment')) {
            $column['autoIncrement'] = true;
        }
        if ($key === 'UNI') {
            $column['unique'] = true;
        }
        if ($default !== null) {
            $column['default'] = is_numeric($default) && !preg_match('/^0\d/', (string) $default)
                ? (str_contains((string) $default, '.') ? (float) $default : (int) $default)
                : (string) $default;
        }
        if ($comment !== '') {
            $column['comment'] = $comment;
        }

        $label = $comment !== '' ? self::labelFromComment($comment, $prop) : $prop;
        $isPk = !empty($column['primary']);
        $isTimeMeta = in_array($prop, ['create_time', 'update_time', 'delete_time'], true);

        if ($isPk) {
            return [
                'prop'     => $prop,
                'label'    => $label,
                'uiType'   => 'primary_key',
                'column'   => $column,
                'relation' => ['type' => 'belongs_to', 'enabled' => false],
                'list'     => ['enabled' => true, 'render' => 'text', 'width' => 72],
                'search'   => ['enabled' => false, 'keyword' => false, 'advanced' => false],
                'form'     => ['enabled' => false, 'render' => 'input'],
            ];
        }

        if ($isTimeMeta) {
            $list = ['enabled' => true, 'render' => 'text', 'minWidth' => 120];
            if (in_array($parsed['type'], ['bigint', 'datetime', 'timestamp'], true)) {
                $list = ['enabled' => true, 'render' => 'datetime', 'width' => 170];
            }

            return [
                'prop'     => $prop,
                'label'    => $label,
                'column'   => $column,
                'relation' => ['type' => 'belongs_to', 'enabled' => false],
                'list'     => $list,
                'search'   => ['enabled' => false, 'keyword' => false, 'advanced' => false],
                'form'     => ['enabled' => false, 'render' => 'input'],
            ];
        }

        $inferred = self::inferUiFromColumn($prop, $parsed['type'], $comment);
        $field = [
            'prop'     => $prop,
            'label'    => $label,
            'column'   => $column,
            'relation' => ['type' => 'belongs_to', 'enabled' => false],
            'list'     => $inferred['list'],
            'search'   => $inferred['search'],
            'form'     => $inferred['form'],
        ];
        if (!empty($inferred['ext'])) {
            $field['ext'] = $inferred['ext'];
        }

        return $field;
    }

    /**
     * parseMysqlType 解析 MySQL Type 字符串
     *
     * @return array{type: string, length: int|null, precision: int|null, scale: int|null, unsigned: bool}
     */
    private static function parseMysqlType(string $typeRaw): array
    {
        $unsigned = str_contains($typeRaw, 'unsigned');
        $base = preg_replace('/\s+unsigned.*/', '', $typeRaw) ?? $typeRaw;
        $base = trim($base);
        $name = $base;
        $length = null;
        $precision = null;
        $scale = null;
        if (preg_match('/^([a-z]+)\((.+)\)$/', $base, $m)) {
            $name = $m[1];
            $args = $m[2];
            if ($name === 'decimal' || $name === 'numeric') {
                $parts = array_map('trim', explode(',', $args));
                $precision = (int) ($parts[0] ?? 12);
                $scale = (int) ($parts[1] ?? 2);
            } elseif (is_numeric($args)) {
                $length = (int) $args;
            }
        }

        $type = match ($name) {
            'bigint' => 'bigint',
            'int', 'integer', 'mediumint', 'smallint' => 'int',
            'tinyint', 'bool', 'boolean' => 'tinyint',
            'decimal', 'numeric', 'float', 'double', 'real' => 'decimal',
            'varchar' => 'varchar',
            'char' => 'char',
            'text', 'tinytext' => 'text',
            'mediumtext' => 'mediumtext',
            'longtext' => 'longtext',
            'date' => 'date',
            'datetime' => 'datetime',
            'timestamp' => 'timestamp',
            'json' => 'json',
            'enum', 'set' => 'varchar',
            default => 'varchar',
        };
        if ($type === 'varchar' && $length === null && in_array($name, ['enum', 'set'], true)) {
            $length = 50;
        }
        if ($type === 'varchar' && $length === null) {
            $length = 255;
        }

        return [
            'type'      => $type,
            'length'    => $length,
            'precision' => $precision,
            'scale'     => $scale,
            'unsigned'  => $unsigned,
        ];
    }

    /**
     * inferUiFromColumn 按列类型推断 list/search/form（新层结构）
     *
     * @return array{list: array<string, mixed>, search: array<string, mixed>, form: array<string, mixed>, ext: array<string, mixed>|null}
     */
    private static function inferUiFromColumn(string $prop, string $type, string $comment): array
    {
        $list = ['enabled' => true, 'render' => 'text', 'minWidth' => 120];
        $search = ['enabled' => false, 'keyword' => false, 'advanced' => false];
        $form = ['enabled' => true, 'render' => 'input'];
        $ext = null;

        if ($type === 'tinyint') {
            $list = ['enabled' => true, 'render' => 'bool', 'width' => 90];
            $search = ['enabled' => true, 'keyword' => false, 'advanced' => true, 'render' => 'bool'];
            $form = ['enabled' => true, 'render' => 'switch'];
            if (self::commentLooksLikeEnum($comment)) {
                $list = ['enabled' => true, 'render' => 'status', 'width' => 100];
                $search = ['enabled' => true, 'keyword' => false, 'advanced' => true, 'render' => 'select'];
                $form = ['enabled' => true, 'render' => 'radio'];
                $ext = ['enum' => ['key' => 'STATUS_MAP']];
            }
        } elseif (in_array($type, ['int', 'bigint', 'decimal'], true)) {
            $list = ['enabled' => true, 'render' => 'number', 'width' => 100];
            $search = ['enabled' => true, 'keyword' => false, 'advanced' => true, 'render' => 'number_range'];
            $form = ['enabled' => true, 'render' => 'number'];
            if (str_ends_with($prop, '_id')) {
                $list = ['enabled' => true, 'render' => 'text', 'width' => 100];
                $search = ['enabled' => true, 'keyword' => false, 'advanced' => true, 'render' => 'remote_select'];
                $form = ['enabled' => true, 'render' => 'remote_select'];
                $ext = [
                    'remote' => [
                        'api'         => '',
                        'valueField'  => 'id',
                        'labelField'  => 'name',
                    ],
                ];
            } elseif ($type === 'bigint' && (str_ends_with($prop, '_time') || str_contains($prop, 'time'))) {
                $list = ['enabled' => true, 'render' => 'datetime', 'width' => 170];
                $search = ['enabled' => true, 'keyword' => false, 'advanced' => true, 'render' => 'datetime_range'];
                $form = ['enabled' => true, 'render' => 'datetime'];
            }
        } elseif ($type === 'date') {
            $list = ['enabled' => true, 'render' => 'text', 'width' => 120];
            $search = ['enabled' => true, 'keyword' => false, 'advanced' => true, 'render' => 'date_range'];
            $form = ['enabled' => true, 'render' => 'date'];
        } elseif (in_array($type, ['datetime', 'timestamp'], true)) {
            $list = ['enabled' => true, 'render' => 'datetime', 'width' => 170];
            $search = ['enabled' => true, 'keyword' => false, 'advanced' => true, 'render' => 'datetime_range'];
            $form = ['enabled' => true, 'render' => 'datetime'];
        } elseif (in_array($type, ['text', 'mediumtext', 'longtext'], true)) {
            $list = ['enabled' => true, 'render' => 'text', 'minWidth' => 160];
            $search = ['enabled' => false, 'keyword' => false, 'advanced' => false];
            $form = ['enabled' => true, 'render' => 'textarea'];
        } elseif ($type === 'json') {
            $list = ['enabled' => true, 'render' => 'tags', 'minWidth' => 140];
            $search = ['enabled' => false, 'keyword' => false, 'advanced' => false];
            $form = ['enabled' => true, 'render' => 'multi_select'];
        } elseif ($type === 'varchar' || $type === 'char') {
            if (str_contains($prop, 'image') || str_contains($prop, 'cover') || str_contains($prop, 'avatar') || str_contains($prop, 'logo')) {
                $list = ['enabled' => true, 'render' => 'image', 'width' => 80];
                $search = ['enabled' => false, 'keyword' => false, 'advanced' => false];
                $form = ['enabled' => true, 'render' => 'image'];
            } elseif (str_contains($prop, 'color')) {
                $list = ['enabled' => true, 'render' => 'color', 'width' => 90];
                $search = ['enabled' => false, 'keyword' => false, 'advanced' => false];
                $form = ['enabled' => true, 'render' => 'color'];
            } else {
                $list = ['enabled' => true, 'render' => 'text', 'minWidth' => 120];
                $search = ['enabled' => true, 'keyword' => true, 'advanced' => false, 'match' => 'fuzzy'];
                $form = ['enabled' => true, 'render' => 'input'];
            }
        }

        return [
            'list'   => $list,
            'search' => $search,
            'form'   => $form,
            'ext'    => $ext,
        ];
    }

    /** commentLooksLikeEnum 注释是否像 {k:v} 枚举 */
    private static function commentLooksLikeEnum(string $comment): bool
    {
        return (bool) preg_match('/\{[^}]+:[^}]+\}/', $comment);
    }

    /** labelFromComment 从列注释取简短 label */
    private static function labelFromComment(string $comment, string $fallback): string
    {
        $label = preg_replace('/（.*?）|\(.*?\)|\{.*?\}/', '', $comment) ?? $comment;
        $label = trim($label);
        if ($label === '') {
            return $fallback;
        }
        if (mb_strlen($label) > 20) {
            return mb_substr($label, 0, 20);
        }

        return $label;
    }
}
