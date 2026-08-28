<?php
declare (strict_types = 1);

namespace app\admin\controller\dev\curd;

use app\admin\model\dev\curd\History;
use app\common\controller\AdminController;
use crud\AuthInstaller;
use crud\Config;
use crud\Context;
use crud\DbConnection;
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
     * connections 列出可选数据库连接（config/database.php connections）
     */
    public function connections(Request $request): Response
    {
        if (!$request->isGet()) {
            return $this->fail(405, '仅支持 GET');
        }

        return $this->ok([
            'default'     => DbConnection::defaultCrudName(),
            'connections' => DbConnection::options(),
        ]);
    }

    /**
     * tables 列出指定连接的数据表（去掉前缀；过滤系统表）
     */
    public function tables(Request $request): Response
    {
        if (!$request->isGet()) {
            return $this->fail(405, '仅支持 GET');
        }
        try {
            $connection = DbConnection::normalize($request->get('connection', ''));
            $prefix = DbConnection::prefix($connection);
            $rows = DbConnection::tableNames($connection);
        } catch (\Throwable $e) {
            return $this->fail(400, $e->getMessage());
        }

        $names = [];
        foreach ($rows as $full) {
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
        if ($table === '' || !preg_match('/^[a-z][a-z0-9_]*$/', $table)) {
            return $this->fail(400, 'table 无效，须为小写 snake_case');
        }
        if ($this->isSystemTable($table)) {
            return $this->fail(400, '系统表不可用于生成');
        }

        try {
            $connection = DbConnection::normalize($request->get('connection', ''));
        } catch (\Throwable $e) {
            return $this->fail(400, $e->getMessage());
        }
        if (!DbConnection::tableExists($connection, $table)) {
            return $this->fail(404, '表不存在');
        }

        try {
            $columns = DbConnection::showFullColumns($connection, $table);
        } catch (\Throwable $e) {
            return $this->fail(400, '读取表结构失败: ' . $e->getMessage());
        }
        if ($columns === []) {
            return $this->fail(400, '表无列');
        }

        return $this->ok($this->mapTableToConfig($table, $columns, $connection));
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
            if ($ctx->generateAuth) {
                (new AuthInstaller())->validate($ctx);
            }
            $files = (new Generator())->build($ctx);
        } catch (\Throwable $e) {
            return $this->fail(400, $e->getMessage());
        }

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
            'writeMode'    => $config['writeMode'],
            'generateAuth' => $ctx->generateAuth,
            'files'        => $filePlan,
            'schema'       => $schemaPreview,
            'auth'         => $authPreview,
            'authMeta'     => null,
            'historyId'    => null,
        ];

        if ($dryRun) {
            return $this->ok($result, 'dry-run：未写盘、未写库');
        }

        $root = rtrim(str_replace('\\', '/', root_path()), '/');
        try {
            $result['files'] = (new Writer())->write($root, $files, (string) $config['writeMode']);
        } catch (\Throwable $e) {
            return $this->fail(500, '写盘失败: ' . $e->getMessage(), $result);
        }

        try {
            $result['schema'] = (new SchemaInstaller())->install($ctx);
        } catch (\Throwable $e) {
            return $this->fail(500, '建表失败: ' . $e->getMessage(), $result);
        }

        if ($ctx->generateAuth) {
            try {
                $auth = $authInstaller->install($ctx);
            } catch (\Throwable $e) {
                return $this->fail(500, '权限写入失败: ' . $e->getMessage(), $result);
            }
            $result['auth'] = $authInstaller->applyInstallStatus($authPreview, $auth);
            $result['authMeta'] = [
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
        return \crud\TableSchema::isSystemTable($name);
    }

    /**
     * mapTableToConfig 表列 → CrudGenerateConfig 草稿
     *
     * @param list<array<string, mixed>> $columns
     * @return array<string, mixed>
     */
    private function mapTableToConfig(string $table, array $columns, string $connection = ''): array
    {
        return \crud\TableSchema::mapTableToConfig($table, $columns, $connection);
    }
}
