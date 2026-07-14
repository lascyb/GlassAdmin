<?php
declare (strict_types = 1);

namespace app\admin\controller\dev\curd;

use app\admin\model\dev\curd\History;
use app\common\controller\AdminController;
use crud\AuthInstaller;
use crud\Config;
use crud\Context;
use crud\Generator;
use crud\SchemaInstaller;
use crud\Writer;
use think\facade\Db;
use think\Request;
use think\Response;

/**
 * Index CRUD 一键生成（可视化 UI 后端）
 */
class Index extends AdminController
{
    /** SAMPLES_DIR 样例配置目录（相对项目根） */
    private const string SAMPLES_DIR = 'extend/crud/samples';

    /**
     * samples 列出可用样例文件名
     */
    public function samples(Request $request): Response
    {
        if (!$request->isGet()) {
            return $this->fail(405, '仅支持 GET');
        }
        $dir = $this->samplesPath();
        if (!is_dir($dir)) {
            return $this->ok([]);
        }
        $names = [];
        foreach (scandir($dir) ?: [] as $file) {
            if (str_ends_with($file, '.json')) {
                $names[] = substr($file, 0, -5);
            }
        }
        sort($names);

        return $this->ok($names);
    }

    /**
     * sample 读取样例配置 JSON
     */
    public function sample(Request $request): Response
    {
        if (!$request->isGet()) {
            return $this->fail(405, '仅支持 GET');
        }
        $name = trim((string) $request->get('name', ''));
        if ($name === '' || !preg_match('/^[a-zA-Z0-9][a-zA-Z0-9_-]*$/', $name)) {
            return $this->fail(400, 'name 无效');
        }
        $path = $this->samplesPath() . '/' . $name . '.json';
        if (!is_file($path)) {
            return $this->fail(404, '样例不存在');
        }
        try {
            $config = Config::fromFile($path);
        } catch (\Throwable $e) {
            return $this->fail(400, $e->getMessage());
        }

        return $this->ok($config);
    }

    /**
     * tables 列出当前库数据表（去掉前缀；过滤系统表）
     */
    public function tables(Request $request): Response
    {
        if (!$request->isGet()) {
            return $this->fail(405, '仅支持 GET');
        }
        $prefix = (string) Db::getConfig('prefix');
        $rows = Db::query('SHOW TABLES');
        $names = [];
        foreach ($rows as $row) {
            $full = (string) reset($row);
            $name = $prefix !== '' && str_starts_with($full, $prefix)
                ? substr($full, strlen($prefix))
                : $full;
            if ($this->isSystemTable($name)) {
                continue;
            }
            $names[] = $name;
        }
        sort($names);

        return $this->ok($names);
    }

    /**
     * tableSchema 反推表结构为 CrudGenerateConfig 草稿
     */
    public function tableSchema(Request $request): Response
    {
        if (!$request->isGet()) {
            return $this->fail(405, '仅支持 GET');
        }
        $table = trim((string) $request->get('table', ''));
        if ($table === '' || !preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $table)) {
            return $this->fail(400, 'table 无效');
        }
        if ($this->isSystemTable($table)) {
            return $this->fail(400, '系统表不可用于生成');
        }

        $prefix = (string) Db::getConfig('prefix');
        $full = $prefix . $table;
        $like = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $full);
        $like = str_replace("'", "''", $like);
        if (Db::query("SHOW TABLES LIKE '{$like}'") === []) {
            return $this->fail(404, '表不存在');
        }

        $quoted = '`' . str_replace('`', '``', $full) . '`';
        try {
            $columns = Db::query("SHOW FULL COLUMNS FROM {$quoted}");
        } catch (\Throwable $e) {
            return $this->fail(400, '读取表结构失败: ' . $e->getMessage());
        }
        if ($columns === []) {
            return $this->fail(400, '表无列');
        }

        return $this->ok($this->mapTableToConfig($table, $columns));
    }

    /**
     * generate 执行 CRUD 生成（支持 dryRun / force / noAuth）
     *
     * 请求体：{ config: object, dryRun?: bool, force?: bool, noAuth?: bool, saveHistory?: bool }
     */
    public function generate(Request $request): Response
    {
        if (!$request->isPost()) {
            return $this->fail(405, '仅支持 POST');
        }

        $body = $this->readJsonBody($request);
        if ($body === null) {
            return $this->fail(400, '请求体无效');
        }

        $rawConfig = $body['config'] ?? null;
        if (is_string($rawConfig)) {
            $decoded = json_decode($rawConfig, true);
            if (!is_array($decoded)) {
                return $this->fail(400, 'config JSON 无效');
            }
            $rawConfig = $decoded;
        }
        if (!is_array($rawConfig)) {
            return $this->fail(400, '缺少 config');
        }

        $dryRun = !empty($body['dryRun']);
        $force = !empty($body['force']);
        $noAuth = !empty($body['noAuth']);
        $saveHistory = array_key_exists('saveHistory', $body) ? (bool) $body['saveHistory'] : true;

        try {
            $config = Config::normalize($rawConfig);
            if ($force) {
                $config['writeMode'] = 'overwrite';
            }
            if ($noAuth) {
                $config['generateAuth'] = false;
            }
            $ctx = Context::fromConfig($config);
            $files = (new Generator())->build($ctx);
        } catch (\Throwable $e) {
            return $this->fail(400, $e->getMessage());
        }

        $filePlan = [];
        foreach ($files as $file) {
            $filePlan[] = [
                'path'  => $file['path'],
                'bytes' => strlen($file['content']),
            ];
        }

        $schemaPreview = (new SchemaInstaller())->preview($ctx);
        $authPreview = $ctx->generateAuth ? (new AuthInstaller())->preview($ctx) : [];

        $result = [
            'dryRun'         => $dryRun,
            'module'         => $ctx->module,
            'entity'         => $ctx->entity,
            'table'          => $ctx->table,
            'title'          => $ctx->title,
            'writeMode'      => $config['writeMode'],
            'generateAuth'   => $ctx->generateAuth,
            'files'          => $filePlan,
            'schema'         => $schemaPreview,
            'auth'           => $authPreview,
            'written'        => [],
            'skipped'        => [],
            'tablesCreated'  => [],
            'tablesSkipped'  => [],
            'authResult'     => null,
            'historyId'      => null,
        ];

        if ($dryRun) {
            return $this->ok($result, 'dry-run：未写盘、未写库');
        }

        $root = rtrim(str_replace('\\', '/', root_path()), '/');
        try {
            $write = (new Writer())->write($root, $files, (string) $config['writeMode']);
        } catch (\Throwable $e) {
            return $this->fail(500, '写盘失败: ' . $e->getMessage(), $result);
        }
        $result['written'] = $write['written'];
        $result['skipped'] = $write['skipped'];

        try {
            $schema = (new SchemaInstaller())->install($ctx);
        } catch (\Throwable $e) {
            return $this->fail(500, '建表失败: ' . $e->getMessage(), $result);
        }
        $result['tablesCreated'] = $schema['created'];
        $result['tablesSkipped'] = $schema['skipped'];

        if ($ctx->generateAuth) {
            try {
                $auth = (new AuthInstaller())->install($ctx);
            } catch (\Throwable $e) {
                return $this->fail(500, '权限写入失败: ' . $e->getMessage(), $result);
            }
            $result['authResult'] = [
                'created'          => $auth['created'],
                'updated'          => $auth['updated'],
                'reused'           => $auth['reused'],
                'bindings'         => $auth['bindings'],
                'bindingsCleared'  => $auth['bindingsCleared'],
                'viewOverwritten'  => $auth['viewOverwritten'] ?? false,
            ];
        }

        if ($saveHistory) {
            try {
                $history = History::create([
                    'name'        => $ctx->module === '' ? $ctx->entity : (str_replace('/', '.', $ctx->module) . '.' . $ctx->entity),
                    'config'      => json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'fields'      => json_encode($config['fields'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'create_time' => date('Y-m-d H:i:s'),
                ]);
                $result['historyId'] = (int) $history->id;
            } catch (\Throwable $e) {
                // 历史写入失败不阻断生成结果
                $result['historyError'] = $e->getMessage();
            }
        }

        return $this->ok($result, '生成完成');
    }

    /** samplesPath 样例目录绝对路径 */
    private function samplesPath(): string
    {
        return rtrim(str_replace('\\', '/', root_path()), '/') . '/' . self::SAMPLES_DIR;
    }

    /**
     * readJsonBody 读取 POST/JSON 请求体
     *
     * @return array<string, mixed>|null
     */
    private function readJsonBody(Request $request): ?array
    {
        $post = $request->post();
        if (is_array($post) && $post !== []) {
            return $post;
        }
        $raw = (string) $request->getContent();
        if ($raw === '') {
            return is_array($post) ? $post : null;
        }
        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : null;
    }

    /** isSystemTable 是否系统/框架表（不参与 CRUD 反推） */
    private function isSystemTable(string $name): bool
    {
        $exact = [
            'migrations', 'migration', 'phinxlog', 'think_migrations',
            'auth_admin', 'auth_admin_role', 'auth_role', 'auth_role_rule',
            'auth_rule', 'auth_rule_frontend_api', 'system_config',
            'dev_crud_history', 'rsa_key',
            'admin', 'admin_role', 'admin_notification', 'admin_operation_log',
        ];
        if (in_array($name, $exact, true)) {
            return true;
        }
        foreach (['auth_', 'system_', 'admin_'] as $prefix) {
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
    private function mapTableToConfig(string $table, array $columns): array
    {
        $meta = $this->guessMetaFromTable($table);
        $fields = [];
        foreach ($columns as $col) {
            $fields[] = $this->mapColumnToField($col);
        }

        return [
            'table'        => $table,
            'title'        => $meta['title'],
            'module'       => $meta['module'],
            'entity'       => $meta['entity'],
            'moduleTitle'  => $meta['moduleTitle'],
            'menuIcon'     => $meta['menus'][0]['icon'] ?? 'el:Document',
            'menuSort'     => $meta['menus'][0]['sort'] ?? 0,
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
     * @return array{module: string, entity: string, title: string, moduleTitle: string, menus: list<array<string, mixed>>, paths: array<string, string>}
     */
    private function guessMetaFromTable(string $table): array
    {
        $parts = array_values(array_filter(explode('_', $table), static fn ($p) => $p !== ''));
        $menus = $this->guessMenusFromParts($parts);
        $paths = $this->guessPathsFromParts($parts);
        $parsed = \crud\Config::parseControllerPath($paths['controller']);
        $leaf = $menus[count($menus) - 1] ?? null;
        $title = (string) ($leaf['title'] ?? ($table !== '' ? $table : 'Item'));

        return [
            'module'      => $parsed['module'],
            'entity'      => $parsed['entity'],
            'title'       => $title,
            'moduleTitle' => (string) ($menus[0]['title'] ?? $title),
            'menus'       => $menus,
            'paths'       => $paths,
        ];
    }

    /**
     * guessMenusFromParts 末段为 view，前面为 menu；单段仅 view
     *
     * @param list<string> $parts
     * @return list<array{type: string, path: string, title: string, icon?: string, sort?: int, enabled: bool}>
     */
    private function guessMenusFromParts(array $parts): array
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
                'enabled' => true,
            ];
            if ($i === 0) {
                $row['icon'] = 'el:Document';
                $row['sort'] = 0;
            }
            $menus[] = $row;
        }
        $last = $parts[count($parts) - 1];
        $menus[] = [
            'type'    => 'view',
            'path'    => strtolower($last),
            'title'   => ucfirst(strtolower($last)),
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
    private function guessPathsFromParts(array $parts): array
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
        $entity = ucfirst(strtolower($last));
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
     * mapColumnToField SHOW FULL COLUMNS 行 → CrudFieldSchema
     *
     * @param array<string, mixed> $col
     * @return array<string, mixed>
     */
    private function mapColumnToField(array $col): array
    {
        $prop = (string) ($col['Field'] ?? '');
        $typeRaw = strtolower((string) ($col['Type'] ?? 'varchar'));
        $null = strtoupper((string) ($col['Null'] ?? '')) === 'YES';
        $key = strtoupper((string) ($col['Key'] ?? ''));
        $extra = strtolower((string) ($col['Extra'] ?? ''));
        $default = $col['Default'] ?? null;
        $comment = trim((string) ($col['Comment'] ?? ''));

        $parsed = $this->parseMysqlType($typeRaw);
        $column = [
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

        $label = $comment !== '' ? $this->labelFromComment($comment, $prop) : $prop;
        $field = [
            'prop'   => $prop,
            'label'  => $label,
            'column' => $column,
        ];

        $isPk = !empty($column['primary']);
        $isTimeMeta = in_array($prop, ['create_time', 'update_time', 'delete_time'], true);

        if ($isPk || $isTimeMeta) {
            $field['form'] = false;
            if ($isPk) {
                $field['list'] = ['render' => 'text', 'width' => 72];
            } elseif ($parsed['type'] === 'bigint' || $parsed['type'] === 'datetime' || $parsed['type'] === 'timestamp') {
                $field['list'] = ['render' => 'datetime', 'width' => 170];
            }

            return $field;
        }

        $inferred = $this->inferUiFromColumn($prop, $parsed['type'], $comment);
        $field['list'] = $inferred['list'];
        if ($inferred['search'] !== null) {
            $field['search'] = $inferred['search'];
        }
        $field['form'] = $inferred['form'];

        return $field;
    }

    /**
     * parseMysqlType 解析 MySQL Type 字符串
     *
     * @return array{type: string, length: int|null, precision: int|null, scale: int|null, unsigned: bool}
     */
    private function parseMysqlType(string $typeRaw): array
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
     * inferUiFromColumn 按列类型推断 list/search/form
     *
     * @return array{list: array<string, mixed>, search: array<string, mixed>|null, form: array<string, mixed>|false}
     */
    private function inferUiFromColumn(string $prop, string $type, string $comment): array
    {
        $list = ['render' => 'text', 'minWidth' => 120];
        $search = null;
        $form = ['render' => 'input'];

        if ($type === 'tinyint') {
            $list = ['render' => 'bool', 'width' => 90];
            $search = ['mode' => 'advanced', 'render' => 'bool'];
            $form = ['render' => 'switch'];
            if ($this->commentLooksLikeEnum($comment)) {
                $list = ['render' => 'status', 'width' => 100, 'statusMapKey' => 'STATUS_MAP'];
                $search = ['mode' => 'advanced', 'render' => 'select', 'statusMapKey' => 'STATUS_MAP'];
                $form = ['render' => 'radio', 'statusMapKey' => 'STATUS_MAP'];
            }
        } elseif (in_array($type, ['int', 'bigint', 'decimal'], true)) {
            $list = ['render' => 'number', 'width' => 100];
            $search = ['mode' => 'advanced', 'render' => 'number_range'];
            $form = ['render' => 'number'];
            if (str_ends_with($prop, '_id')) {
                $list = ['render' => 'text', 'width' => 100];
                $search = ['mode' => 'advanced', 'render' => 'remote_select'];
                $form = ['render' => 'remote_select'];
            } elseif ($type === 'bigint' && (str_ends_with($prop, '_time') || str_contains($prop, 'time'))) {
                $list = ['render' => 'datetime', 'width' => 170];
                $search = ['mode' => 'advanced', 'render' => 'datetime_range'];
                $form = ['render' => 'datetime'];
            }
        } elseif ($type === 'date') {
            $list = ['render' => 'datetime', 'width' => 120];
            $search = ['mode' => 'advanced', 'render' => 'date_range'];
            $form = ['render' => 'date'];
        } elseif (in_array($type, ['datetime', 'timestamp'], true)) {
            $list = ['render' => 'datetime', 'width' => 170];
            $search = ['mode' => 'advanced', 'render' => 'datetime_range'];
            $form = ['render' => 'datetime'];
        } elseif (in_array($type, ['text', 'mediumtext', 'longtext'], true)) {
            $list = ['render' => 'text', 'minWidth' => 160];
            $search = null;
            $form = ['render' => 'textarea'];
        } elseif ($type === 'json') {
            $list = ['render' => 'tags', 'minWidth' => 140];
            $search = null;
            $form = ['render' => 'multi_select'];
        } elseif ($type === 'varchar' || $type === 'char') {
            if (str_contains($prop, 'image') || str_contains($prop, 'cover') || str_contains($prop, 'avatar') || str_contains($prop, 'logo')) {
                $list = ['render' => 'image', 'width' => 80];
                $search = null;
                $form = ['render' => 'image'];
            } elseif (str_contains($prop, 'color')) {
                $list = ['render' => 'color', 'width' => 90];
                $search = null;
                $form = ['render' => 'color'];
            } else {
                $list = ['render' => 'text', 'minWidth' => 120];
                $search = ['mode' => 'keyword'];
                $form = ['render' => 'input'];
            }
        }

        return [
            'list'   => $list,
            'search' => $search,
            'form'   => $form,
        ];
    }

    /** commentLooksLikeEnum 注释是否像 {k:v} 枚举 */
    private function commentLooksLikeEnum(string $comment): bool
    {
        return (bool) preg_match('/\{[^}]+:[^}]+\}/', $comment);
    }

    /** labelFromComment 从列注释取简短 label */
    private function labelFromComment(string $comment, string $fallback): string
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
