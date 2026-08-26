<?php
declare (strict_types = 1);

namespace mcp;

use app\admin\model\dev\curd\History;
use crud\AuthInstaller;
use crud\Config;
use crud\Context;
use crud\DbConnection;
use crud\Generator;
use crud\SchemaInstaller;
use crud\TableSchema;
use crud\Writer;
use think\Model;

/**
 * CrudOps GlassAdmin CRUD 开发能力（供 MCP Tools / Resources 复用）
 */
class CrudOps
{
    /** SAMPLES_DIR 样例配置目录（相对项目根） */
    private const string SAMPLES_DIR = 'extend/crud/samples';

    /**
     * listConnections 列出可选数据库连接（不含账号密码）
     *
     * @return array{default: string, connections: list<array<string, mixed>>}
     */
    public function listConnections(): array
    {
        return [
            'default'     => DbConnection::defaultName(),
            'connections' => DbConnection::options(),
        ];
    }

    /**
     * listTables 列出指定连接的业务表（去掉前缀；过滤系统表）
     *
     * @return list<string>
     */
    public function listTables(string $connection = ''): array
    {
        $connection = DbConnection::normalize($connection);
        $prefix = DbConnection::prefix($connection);
        $names = [];
        foreach (DbConnection::tableNames($connection) as $full) {
            $name = $prefix !== '' && str_starts_with($full, $prefix)
                ? substr($full, strlen($prefix))
                : $full;
            if (TableSchema::isSystemTable($name)) {
                continue;
            }
            $names[] = $name;
        }
        sort($names);

        return $names;
    }

    /**
     * getTableSchema 反推表结构为 CrudGenerateConfig 草稿
     *
     * @return array<string, mixed>
     */
    public function getTableSchema(string $table, string $connection = ''): array
    {
        $table = trim($table);
        if ($table === '' || !preg_match('/^[a-z][a-z0-9_]*$/', $table)) {
            throw new \InvalidArgumentException('table 无效，须为小写 snake_case');
        }
        if (TableSchema::isSystemTable($table)) {
            throw new \InvalidArgumentException('系统表不可用于生成');
        }

        $connection = DbConnection::normalize($connection);
        $columns = $this->showFullColumns($table, $connection);
        if ($columns === []) {
            throw new \InvalidArgumentException('表无列');
        }

        return TableSchema::mapTableToConfig($table, $columns, $connection);
    }

    /**
     * listSamples 列出可用样例（含子目录，相对 samples 的路径，无 .json）
     *
     * @return list<string>
     */
    public function listSamples(): array
    {
        $dir = $this->samplesPath();
        if (!is_dir($dir)) {
            return [];
        }
        $names = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
        );
        $rootLen = strlen($dir) + 1;
        foreach ($iterator as $file) {
            if (!$file->isFile() || !str_ends_with($file->getFilename(), '.json')) {
                continue;
            }
            if (str_starts_with($file->getFilename(), '_')) {
                continue;
            }
            $rel = str_replace('\\', '/', substr($file->getPathname(), $rootLen));
            $names[] = substr($rel, 0, -5);
        }
        sort($names);

        return $names;
    }

    /**
     * getSample 读取样例配置 JSON（支持子目录路径，如 test/mcp_a）
     *
     * @return array<string, mixed>
     */
    public function getSample(string $name): array
    {
        $name = trim(str_replace('\\', '/', $name));
        $name = trim($name, '/');
        if ($name === '' || !preg_match('/^[a-zA-Z0-9][a-zA-Z0-9_\/-]*$/', $name) || str_contains($name, '..')) {
            throw new \InvalidArgumentException('name 无效');
        }
        $path = $this->samplesPath() . '/' . $name . '.json';
        if (!is_file($path)) {
            throw new \InvalidArgumentException('样例不存在');
        }

        return Config::fromFile($path);
    }

    /**
     * listRemoteControllers 扫描 admin 控制器远程 list 数据源
     *
     * @return list<array<string, mixed>>
     */
    public function listRemoteControllers(): array
    {
        $root = root_path() . 'app' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'controller';
        if (!is_dir($root)) {
            return [];
        }

        $list = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
        );
        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }
            $relative = substr($file->getPathname(), strlen($root) + 1);
            $relative = str_replace('\\', '/', $relative);
            if (str_starts_with($relative, 'dev/')) {
                continue;
            }
            if (!str_ends_with($relative, '.php')) {
                continue;
            }
            $controller = substr($relative, 0, -4);
            $parts = explode('/', $controller);
            if ($parts === [] || $parts[0] === '') {
                continue;
            }
            $key = implode('.', $parts);
            $resolved = $this->resolveModelAndTable($parts);
            $list[] = [
                'key'        => $key,
                'controller' => $controller,
                'listUrl'    => $key . '/list',
                'model'      => $resolved['model'],
                'table'      => $resolved['table'],
                'pk'         => $resolved['pk'],
            ];
        }
        usort($list, static fn (array $a, array $b): int => strcmp($a['controller'], $b['controller']));

        return $list;
    }

    /**
     * getTableColumns 按无前缀表名返回字段列表
     *
     * @return array{table: string, pk: ?string, fields: list<array{name: string, type: string, comment: string}>}
     */
    public function getTableColumns(string $table, string $connection = ''): array
    {
        $table = trim($table);
        if ($table === '' || !preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $table)) {
            throw new \InvalidArgumentException('table 无效');
        }
        if (TableSchema::isSystemTable($table)) {
            throw new \InvalidArgumentException('系统表不可用于远程配置');
        }

        $columns = $this->showFullColumns($table, DbConnection::normalize($connection));
        if ($columns === []) {
            throw new \InvalidArgumentException('表无列');
        }

        $pk = null;
        $fields = [];
        foreach ($columns as $col) {
            $name = (string) ($col['Field'] ?? '');
            if ($name === '') {
                continue;
            }
            $fields[] = [
                'name'    => $name,
                'type'    => (string) ($col['Type'] ?? ''),
                'comment' => (string) ($col['Comment'] ?? ''),
            ];
            if (strtoupper((string) ($col['Key'] ?? '')) === 'PRI' && $pk === null) {
                $pk = $name;
            }
        }

        return [
            'table'  => $table,
            'pk'     => $pk,
            'fields' => $fields,
        ];
    }

    /**
     * listHistory 生成历史分页
     *
     * @return array{list: list<array<string, mixed>>, total: int, page: int, limit: int}
     */
    public function listHistory(int $page = 1, int $limit = 10): array
    {
        $limit = min(100, max(1, $limit));
        $page = max(1, $page);
        $paginator = History::field('id,name,create_time')->order('id', 'desc')->paginate([
            'list_rows' => $limit,
            'page'      => $page,
        ]);

        return [
            'list'  => array_map(static fn ($row) => is_array($row) ? $row : $row->toArray(), $paginator->items()),
            'total' => $paginator->total(),
            'page'  => $paginator->currentPage(),
            'limit' => $paginator->listRows(),
        ];
    }

    /**
     * getHistory 历史详情（含完整 config）
     *
     * @return array<string, mixed>
     */
    public function getHistory(int $id): array
    {
        if ($id <= 0) {
            throw new \InvalidArgumentException('id 无效');
        }
        $row = History::find($id);
        if (!$row) {
            throw new \InvalidArgumentException('不存在');
        }
        $data = $row->toArray();
        $config = json_decode((string) ($data['config'] ?? ''), true);
        $data['configParsed'] = is_array($config) ? $config : null;

        return $data;
    }

    /**
     * generate 执行 CRUD 生成（支持 dryRun / force / noAuth）
     *
     * @param array<string, mixed>|string $config
     * @return array<string, mixed>
     */
    public function generate(
        array|string $config,
        bool $dryRun = false,
        bool $force = false,
        bool $noAuth = false,
        bool $saveHistory = true,
        bool $confirm = false,
    ): array {
        if (is_string($config)) {
            $decoded = json_decode($config, true);
            if (!is_array($decoded)) {
                throw new \InvalidArgumentException('config JSON 无效');
            }
            $config = $decoded;
        }
        if ($config === []) {
            throw new \InvalidArgumentException('缺少 config');
        }

        // 真实写盘须 confirm；force 覆盖仅允许在 confirm 后
        if (!$dryRun && !$confirm) {
            throw new \InvalidArgumentException('真实生成须 confirm=true；请先 dry_run 预览');
        }
        if ($force && !$confirm) {
            throw new \InvalidArgumentException('force 覆盖须 confirm=true');
        }

        $normalized = Config::normalize($config);
        if ($force) {
            $normalized['writeMode'] = 'overwrite';
        }
        if ($noAuth) {
            $normalized['generateAuth'] = false;
        }
        $ctx = Context::fromConfig($normalized);
        if ($ctx->generateAuth) {
            (new AuthInstaller())->validate($ctx);
        }
        $files = (new Generator())->build($ctx);

        $filePlan = [];
        foreach ($files as $file) {
            $filePlan[] = [
                'path'   => $file['path'],
                'bytes'  => strlen($file['content']),
                'status' => 'planned',
            ];
        }

        $schemaPreview = (new SchemaInstaller())->preview($ctx);
        $authInstaller = new AuthInstaller();
        $authPreview = $ctx->generateAuth ? $authInstaller->preview($ctx) : [];

        $result = [
            'dryRun'       => $dryRun,
            'module'       => $ctx->module,
            'entity'       => $ctx->entity,
            'table'        => $ctx->table,
            'connection'   => $ctx->connection,
            'title'        => $ctx->title,
            'writeMode'    => $normalized['writeMode'],
            'generateAuth' => $ctx->generateAuth,
            'files'        => $filePlan,
            'schema'       => $schemaPreview,
            'auth'         => $authPreview,
            'authMeta'     => null,
            'historyId'    => null,
        ];

        if ($dryRun) {
            $result['message'] = 'dry-run：未写盘、未写库';

            return $result;
        }

        $root = rtrim(str_replace('\\', '/', root_path()), '/');
        $result['files'] = (new Writer())->write($root, $files, (string) $normalized['writeMode']);
        $result['schema'] = (new SchemaInstaller())->install($ctx);

        if ($ctx->generateAuth) {
            $auth = $authInstaller->install($ctx);
            $result['auth'] = $authInstaller->applyInstallStatus($authPreview, $auth);
            $result['authMeta'] = [
                'bindings'        => $auth['bindings'],
                'bindingsCleared' => $auth['bindingsCleared'],
                'viewOverwritten' => $auth['viewOverwritten'] ?? false,
            ];
        }

        if ($saveHistory) {
            try {
                $history = History::create([
                    'name'        => $ctx->module === '' ? $ctx->entity : (str_replace('/', '.', $ctx->module) . '.' . $ctx->entity),
                    'config'      => json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'fields'      => json_encode($normalized['fields'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'create_time' => date('Y-m-d H:i:s'),
                ]);
                $result['historyId'] = (int) $history->id;
            } catch (\Throwable $e) {
                $result['historyError'] = $e->getMessage();
            }
        }

        $result['message'] = '生成完成';

        return $result;
    }

    /** samplesPath 样例目录绝对路径 */
    private function samplesPath(): string
    {
        return rtrim(str_replace('\\', '/', root_path()), '/') . '/' . self::SAMPLES_DIR;
    }

    /**
     * showFullColumns 读取表列
     *
     * @return list<array<string, mixed>>
     */
    private function showFullColumns(string $table, string $connection = ''): array
    {
        if (!DbConnection::tableExists($connection, $table)) {
            throw new \InvalidArgumentException('表不存在');
        }

        try {
            return DbConnection::showFullColumns($connection, $table);
        } catch (\Throwable $e) {
            throw new \InvalidArgumentException('读取表结构失败: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * resolveModelAndTable 按控制器相对路径推断模型与表
     *
     * @param list<string> $parts
     * @return array{model: ?string, table: ?string, pk: ?string}
     */
    private function resolveModelAndTable(array $parts): array
    {
        $candidates = [];
        $candidates[] = 'app\\admin\\model\\' . implode('\\', $parts);
        $className = $parts[array_key_last($parts)] ?? '';
        if ($className !== '') {
            $candidates[] = 'app\\admin\\model\\' . $className;
        }

        foreach ($candidates as $fqcn) {
            if (!class_exists($fqcn)) {
                continue;
            }
            try {
                $ref = new \ReflectionClass($fqcn);
                if (!$ref->isInstantiable() || !$ref->isSubclassOf(Model::class)) {
                    continue;
                }
                /** @var Model $model */
                $model = $ref->newInstance();
                $table = $this->normalizeTableName((string) $model->getTable());
                $pk = null;
                if ($table !== '') {
                    try {
                        $cols = $this->getTableColumns($table);
                        $pk = $cols['pk'];
                    } catch (\Throwable) {
                        $pk = null;
                    }
                }

                return [
                    'model' => $fqcn,
                    'table' => $table !== '' ? $table : null,
                    'pk'    => $pk,
                ];
            } catch (\Throwable) {
                continue;
            }
        }

        return ['model' => null, 'table' => null, 'pk' => null];
    }

    /** normalizeTableName 去掉库前缀，得到业务表名 */
    private function normalizeTableName(string $full): string
    {
        $full = trim($full);
        if ($full === '') {
            return '';
        }
        if (str_contains($full, '.')) {
            $full = substr($full, (int) strrpos($full, '.') + 1);
        }
        $prefix = DbConnection::prefix();
        if ($prefix !== '' && str_starts_with($full, $prefix)) {
            return substr($full, strlen($prefix));
        }

        return $full;
    }
}
