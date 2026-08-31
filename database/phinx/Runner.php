<?php

declare(strict_types=1);

namespace phinx;

use PDO;
use Phinx\Config\Config;
use Phinx\Migration\Manager;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use think\facade\Db;

/**
 * Runner 程序化执行 Phinx（升级 migrate；新安装 baseline；生成升级脚本）
 *
 * Phinx 账本表不走 table_prefix，须把物理表名（含前缀）写入 migration_table。
 */
final class Runner
{
    /** 逻辑表名；有前缀时物理表为 {prefix}system_db_version */
    public const LOG_TABLE = 'system_db_version';

    public const BASELINE_NAME = 'InstallBaseline';

    /**
     * create 在 database/migrations 生成升级脚本，返回绝对路径
     */
    public static function create(string $className): string
    {
        if (!preg_match('/^[A-Z][a-zA-Z0-9]*$/', $className)) {
            throw new \InvalidArgumentException('类名须为 PascalCase，如 AddFooColumn');
        }

        $dir = self::migrationsPath();
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new \RuntimeException('无法创建目录 ' . $dir);
        }

        foreach (self::existingMigrationClassNames($dir) as $existing) {
            if (strcasecmp($existing, $className) === 0) {
                throw new \RuntimeException('已存在同名升级脚本：' . $className);
            }
        }

        $timestamp = self::nextMigrationTimestamp($dir);
        $snake = preg_replace_callback('/\d+|[A-Z]/', static fn(array $m): string => '_' . strtolower($m[0]), $className);
        $path = $dir . DIRECTORY_SEPARATOR . $timestamp . $snake . '.php';
        if (file_put_contents($path, self::migrationStub($className)) === false) {
            throw new \RuntimeException('无法写入 ' . $path);
        }

        return $path;
    }

    /**
     * migrateFromApp 使用当前 .env 数据库配置执行升级
     */
    public static function migrateFromApp(): string
    {
        $cfg = Db::connect()->getConfig();
        $database = (string) ($cfg['database'] ?? '');
        if ($database === '') {
            throw new \RuntimeException('未配置数据库名');
        }
        $charset = (string) ($cfg['charset'] ?? 'utf8mb4');
        $prefix = (string) ($cfg['prefix'] ?? '');
        $dsn = 'mysql:host=' . ($cfg['hostname'] ?? '127.0.0.1')
            . ';port=' . ($cfg['hostport'] ?? '3306')
            . ';dbname=' . $database
            . ';charset=' . $charset;
        $pdo = new PDO($dsn, (string) ($cfg['username'] ?? ''), (string) ($cfg['password'] ?? ''), [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        return self::migrate($pdo, $database, $prefix, $charset);
    }

    /**
     * migrate 跑完所有未应用的 migration（系统版本升级）
     */
    public static function migrate(PDO $pdo, string $database, string $prefix, string $charset = 'utf8mb4'): string
    {
        $renamed = self::renameLegacyLogTable($pdo, $prefix);
        $output = new BufferedOutput();
        $manager = self::manager($pdo, $database, $prefix, $charset, $output);
        $manager->migrate('glassadmin');
        $log = $output->fetch();
        if ($renamed !== '') {
            $log = $renamed . "\n" . $log;
        }

        return $log;
    }

    /**
     * baseline 写入安装基线，并把当前 migrations 全部记为已应用（新安装 SQL 已含这些变更）
     */
    public static function baseline(PDO $pdo, string $database, string $prefix, string $charset = 'utf8mb4'): string
    {
        $manager = self::manager($pdo, $database, $prefix, $charset, new BufferedOutput());
        $env = $manager->getEnvironment('glassadmin');
        $adapter = $env->getAdapter();
        $adapter->connect();
        $applied = $env->getVersionLog();
        $now = date('Y-m-d H:i:s');
        $n = 0;

        if (!self::hasBaselineRow($applied)) {
            $table = self::quoteIdent(self::physicalLogTable($prefix));
            $adapter->execute(sprintf(
                "INSERT INTO %s (`version`, `migration_name`, `start_time`, `end_time`, `breakpoint`) VALUES ('%d', '%s', '%s', '%s', 0)",
                $table,
                self::nextBaselineVersion($applied, $manager),
                self::BASELINE_NAME,
                $now,
                $now,
            ));
            $n++;
        }

        foreach ($manager->getMigrations('glassadmin') as $migration) {
            $version = (int) $migration->getVersion();
            if (isset($applied[$version])) {
                continue;
            }
            $adapter->migrated($migration, 'up', $now, $now);
            $n++;
        }

        return self::LOG_TABLE . ' baseline: ' . $n . ' rows';
    }

    private static function manager(PDO $pdo, string $database, string $prefix, string $charset, BufferedOutput $output): Manager
    {
        $root = dirname(__DIR__, 2);
        $config = new Config([
            'paths' => [
                'migrations' => self::migrationsPath(),
            ],
            'environments' => [
                'default_environment' => 'glassadmin',
                'glassadmin'          => [
                    'adapter'         => 'mysql',
                    'name'            => $database,
                    'connection'      => $pdo,
                    'charset'         => $charset,
                    'collation'       => 'utf8mb4_0900_ai_ci',
                    'table_prefix'    => $prefix,
                    'migration_table' => self::physicalLogTable($prefix),
                ],
            ],
            'templates' => [
                'style' => 'up_down',
            ],
            'migration_base_class' => Migration::class,
            'feature_flags' => [
                'unsigned_primary_keys' => true,
                'column_null_default'   => false,
            ],
        ], $root . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'migrations');

        return new Manager($config, new ArrayInput([]), $output);
    }

    /** physicalLogTable 账本物理表名（Phinx 不对账本表再加 prefix） */
    public static function physicalLogTable(string $prefix): string
    {
        return $prefix . self::LOG_TABLE;
    }

    /**
     * renameLegacyLogTable 旧 phinxlog 一次性改名为 system_db_version
     */
    private static function renameLegacyLogTable(PDO $pdo, string $prefix): string
    {
        $new = self::physicalLogTable($prefix);
        if (self::tableExists($pdo, $new)) {
            return '';
        }

        $candidates = [$prefix . 'phinxlog'];
        if ($prefix !== '' && !in_array('phinxlog', $candidates, true)) {
            $candidates[] = 'phinxlog';
        }

        foreach ($candidates as $old) {
            if (!self::tableExists($pdo, $old)) {
                continue;
            }
            $pdo->exec('RENAME TABLE ' . self::quoteIdent($old) . ' TO ' . self::quoteIdent($new));

            return 'renamed ' . $old . ' -> ' . $new;
        }

        return '';
    }

    private static function tableExists(PDO $pdo, string $table): bool
    {
        $stmt = $pdo->prepare(
            'SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1',
        );
        $stmt->execute([$table]);

        return (bool) $stmt->fetchColumn();
    }

    private static function quoteIdent(string $name): string
    {
        return '`' . str_replace('`', '', $name) . '`';
    }

    private static function migrationsPath(): string
    {
        return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'migrations';
    }

    /**
     * @return list<string>
     */
    private static function existingMigrationClassNames(string $dir): array
    {
        $names = [];
        foreach (glob($dir . DIRECTORY_SEPARATOR . '*.php') ?: [] as $path) {
            $base = basename($path);
            if (!preg_match('/^\d{14}_([a-z0-9_]+)\.php$/', $base, $m)) {
                continue;
            }
            $names[] = str_replace('_', '', ucwords($m[1], '_'));
        }

        return $names;
    }

    /**
     * @param array<int, mixed> $applied
     */
    private static function hasBaselineRow(array $applied): bool
    {
        foreach ($applied as $row) {
            if (is_array($row) && ($row['migration_name'] ?? '') === self::BASELINE_NAME) {
                return true;
            }
        }

        return false;
    }

    /**
     * nextBaselineVersion 安装当下的 YmdHis，避开已有账本 version 与 migration 文件
     *
     * @param array<int, mixed> $applied
     */
    private static function nextBaselineVersion(array $applied, Manager $manager): int
    {
        $ts = (int) date('YmdHis');
        $used = $applied;
        foreach ($manager->getMigrations('glassadmin') as $migration) {
            $used[(int) $migration->getVersion()] = true;
        }
        while (isset($used[$ts])) {
            $ts++;
        }

        return $ts;
    }

    private static function nextMigrationTimestamp(string $dir): string
    {
        $ts = (int) date('YmdHis');
        while (glob($dir . DIRECTORY_SEPARATOR . $ts . '*.php')) {
            $ts++;
        }

        return (string) $ts;
    }

    private static function migrationStub(string $className): string
    {
        return <<<PHP
<?php
declare(strict_types=1);

use phinx\Migration;

/**
 * {$className} 数据库升级
 */
final class {$className} extends Migration
{
    public function up(): void
    {
        // 逻辑表名不要手写前缀，使用 \$this->q() / prefixDdl() / archiveColumn()
        // \$this->execute('ALTER TABLE ' . \$this->q('system_auth_admin') . ' ADD COLUMN `foo` varchar(64) NULL');
    }

    public function down(): void
    {
    }
}

PHP;
    }
}
