<?php
declare (strict_types = 1);

namespace app\admin\controller\dev\curd;

use app\common\controller\AdminController;
use crud\DbConnection;
use think\Model;
use think\Request;
use think\Response;

/**
 * Remote CRUD 远程下拉数据源元数据（控制器列表 / 表字段）
 */
class Remote extends AdminController
{
    /**
     * controllers 扫描 admin 控制器，返回远程 list 数据源列表（不含字段）
     */
    public function controllers(Request $request): Response
    {
        if (!$request->isGet()) {
            return $this->fail(405, '仅支持 GET');
        }

        $root = root_path() . 'app' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'controller';
        if (!is_dir($root)) {
            return $this->ok([]);
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
            // 排除 dev 目录
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

        return $this->ok($list);
    }

    /**
     * tableColumns 按无前缀表名返回字段列表
     */
    public function tableColumns(Request $request): Response
    {
        if (!$request->isGet()) {
            return $this->fail(405, '仅支持 GET');
        }
        $table = trim((string) $request->get('table', ''));
        if ($table === '' || !preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $table)) {
            return $this->fail(400, 'table 无效');
        }
//        if ($this->isSystemTable($table)) {
//            return $this->fail(400, '系统表不可用于远程配置');
//        }

        if (!DbConnection::tableExists('', $table)) {
            return $this->fail(404, '表不存在');
        }

        try {
            $columns = DbConnection::showFullColumns('', $table);
        } catch (\Throwable $e) {
            return $this->fail(400, '读取表结构失败: ' . $e->getMessage());
        }
        if ($columns === []) {
            return $this->fail(400, '表无列');
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

        return $this->ok([
            'table'  => $table,
            'pk'     => $pk,
            'fields' => $fields,
        ]);
    }

    /**
     * pivotTables 按主表 + 关联表从库中匹配多对多中间表
     *
     * GET mainTable, relatedTable（无前缀表名）
     */
    public function pivotTables(Request $request): Response
    {
        if (!$request->isGet()) {
            return $this->fail(405, '仅支持 GET');
        }
        $mainTable = trim((string) $request->get('mainTable', ''));
        $relatedTable = trim((string) $request->get('relatedTable', ''));
        if ($mainTable === '' || !preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $mainTable)) {
            return $this->fail(400, 'mainTable 无效');
        }
        if ($relatedTable === '' || !preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $relatedTable)) {
            return $this->fail(400, 'relatedTable 无效');
        }
        if ($this->isSystemTable($mainTable) || $this->isSystemTable($relatedTable)) {
            return $this->fail(400, '系统表不可用于中间表匹配');
        }

        $mainSeg = $this->tableLastSegment($mainTable);
        $relatedSeg = $this->tableLastSegment($relatedTable);
        $expectedName = "{$mainTable}_{$relatedSeg}";

        $items = [];
        foreach ($this->listBusinessTables() as $name) {
            if ($name === $mainTable || $name === $relatedTable) {
                continue;
            }
            $parsed = $this->parsePivotTable($name, $mainSeg, $relatedSeg);
            if ($parsed === null) {
                continue;
            }
            if (!$parsed['matches'] && !str_contains($name, $mainSeg) && !str_contains($name, $relatedSeg)) {
                continue;
            }
            $items[] = [
                'table'      => $name,
                'foreignKey' => $parsed['foreignKey'],
                'relatedKey' => $parsed['relatedKey'],
                'comment'    => $parsed['comment'],
                'suggested'  => $name === $expectedName,
            ];
        }

        usort($items, static function (array $a, array $b): int {
            if (($a['suggested'] ?? false) !== ($b['suggested'] ?? false)) {
                return ($b['suggested'] ?? false) <=> ($a['suggested'] ?? false);
            }

            return strcmp((string) $a['table'], (string) $b['table']);
        });

        return $this->ok([
            'mainTable'    => $mainTable,
            'relatedTable' => $relatedTable,
            'expectedName' => $expectedName,
            'items'        => $items,
        ]);
    }

    /**
     * listBusinessTables 列出无前缀业务表名
     *
     * @return list<string>
     */
    private function listBusinessTables(): array
    {
        $names = [];
        foreach (DbConnection::tableNames('') as $full) {
            $name = $this->normalizeTableName($full);
            if ($name === '' || $this->isSystemTable($name)) {
                continue;
            }
            $names[] = $name;
        }
        sort($names);

        return $names;
    }

    /** tableLastSegment 表名末段（example_data → data） */
    private function tableLastSegment(string $table): string
    {
        $parts = array_values(array_filter(explode('_', $table), static fn (string $p): bool => $p !== ''));

        return $parts !== [] ? $parts[array_key_last($parts)] : $table;
    }

    /**
     * parsePivotTable 解析中间表外键列
     *
     * @return array{foreignKey: string, relatedKey: string, comment: string, matches: bool}|null
     */
    private function parsePivotTable(string $table, string $mainSeg, string $relatedSeg): ?array
    {
        if ($this->isSystemTable($table)) {
            return null;
        }
        try {
            $columns = DbConnection::showFullColumns('', $table);
        } catch (\Throwable) {
            return null;
        }
        if ($columns === []) {
            return null;
        }

        $idCols = [];
        foreach ($columns as $col) {
            $name = (string) ($col['Field'] ?? '');
            if ($name !== '' && str_ends_with($name, '_id')) {
                $idCols[] = $name;
            }
        }
        if ($idCols === []) {
            return null;
        }

        $fk = $this->matchPivotColumn($idCols, $mainSeg, "{$mainSeg}_id");
        $rk = $this->matchPivotColumn($idCols, $relatedSeg, "{$relatedSeg}_id");
        if ($fk === null || $rk === null) {
            if (count($idCols) !== 2) {
                return null;
            }
            [$c1, $c2] = $idCols;
            if ($fk === null && $rk === null) {
                $fk = $c1;
                $rk = $c2;
            } elseif ($fk === null) {
                $fk = $c1 === $rk ? $c2 : $c1;
            } else {
                $rk = $c1 === $fk ? $c2 : $c1;
            }
        }
        if ($fk === $rk) {
            return null;
        }

        $comment = '';
        try {
            $full = DbConnection::prefix() . $table;
            $like = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $full);
            $like = str_replace("'", "''", $like);
            $status = DbConnection::query('', "SHOW TABLE STATUS LIKE '{$like}'");
            if ($status !== []) {
                $comment = (string) ($status[0]['Comment'] ?? '');
            }
        } catch (\Throwable) {
            // ignore
        }

        return [
            'foreignKey' => $fk,
            'relatedKey' => $rk,
            'comment'    => $comment,
            'matches'    => $this->columnMatchesSegment($fk, $mainSeg) && $this->columnMatchesSegment($rk, $relatedSeg),
        ];
    }

    /**
     * matchPivotColumn 从 _id 列中匹配外键
     *
     * @param list<string> $cols
     */
    private function matchPivotColumn(array $cols, string $seg, string $exact): ?string
    {
        if (in_array($exact, $cols, true)) {
            return $exact;
        }
        foreach ($cols as $col) {
            if ($this->columnMatchesSegment($col, $seg)) {
                return $col;
            }
        }

        return null;
    }

    /** columnMatchesSegment 列名是否对应表名末段（data_id / example_data_id） */
    private function columnMatchesSegment(string $col, string $seg): bool
    {
        if ($seg === '') {
            return false;
        }
        if ($col === "{$seg}_id") {
            return true;
        }

        return str_ends_with($col, "_{$seg}_id");
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
        // 同相对路径：auth/Admin → app\admin\model\auth\Admin
        $candidates[] = 'app\\admin\\model\\' . implode('\\', $parts);
        // 仅类名：Admin → app\admin\model\Admin
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
                    $pk = $this->detectPk($table);
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

    /** detectPk 从表结构取主键列名 */
    private function detectPk(string $table): ?string
    {
        if ($this->isSystemTable($table)) {
            return null;
        }
        try {
            $columns = DbConnection::showFullColumns('', $table);
        } catch (\Throwable) {
            return null;
        }
        foreach ($columns as $col) {
            if (strtoupper((string) ($col['Key'] ?? '')) === 'PRI') {
                $name = (string) ($col['Field'] ?? '');

                return $name !== '' ? $name : null;
            }
        }

        return null;
    }

    /** isSystemTable 是否系统/框架表 */
    private function isSystemTable(string $name): bool
    {
        $exact = [
            'migrations', 'migration', 'phinxlog', 'system_db_version', 'think_migrations',
            'auth_admin', 'auth_admin_role', 'auth_admin_operation_log', 'auth_oauth2_feishu',
            'auth_role', 'auth_role_rule', 'auth_rule', 'auth_rule_api',
            'system_config', 'system_rsa_key', 'system_attachment', 'dev_crud_history',
        ];
        if (in_array($name, $exact, true)) {
            return true;
        }
        foreach (['auth_', 'system_'] as $p) {
            if (str_starts_with($name, $p)) {
                return true;
            }
        }

        return false;
    }
}
