<?php
declare (strict_types = 1);

namespace crud;

use think\db\ConnectionInterface;
use think\facade\Config;
use think\facade\Db;

/**
 * DbConnection CRUD 目标数据库连接（对应 config/database.php 的 connections）
 *
 * 约定：connection 为空串表示应用默认连接，生成的 Model 不写 $connection
 */
class DbConnection
{
    /** NAME_PATTERN 连接名合法字符 */
    private const string NAME_PATTERN = '/^[A-Za-z][A-Za-z0-9_\-]*$/';

    /** MYSQL_TYPES CRUD 支持的数据库类型（生成器使用 SHOW TABLES 等 MySQL 语法） */
    private const array MYSQL_TYPES = ['mysql', 'mysqli'];

    /** defaultName 默认连接名 */
    public static function defaultName(): string
    {
        self::assertRuntime();
        $name = Config::get('database.default');

        return is_string($name) && $name !== '' ? $name : 'mysql';
    }

    /**
     * defaultCrudName CRUD 默认选用的连接名（应用默认若非 MySQL 则取首个 MySQL 连接）
     */
    public static function defaultCrudName(): string
    {
        $default = self::defaultName();
        if (self::isMySqlType($default)) {
            return $default;
        }
        $names = self::names();

        return $names[0] ?? $default;
    }

    /**
     * names 已配置的 MySQL 连接名
     *
     * @return list<string>
     */
    public static function names(): array
    {
        $names = [];
        foreach (array_keys(self::connections()) as $name) {
            if (is_string($name) && $name !== '' && self::isMySqlType($name)) {
                $names[] = $name;
            }
        }
        sort($names);

        return $names;
    }

    /**
     * options 可选连接元数据（不含账号密码）
     *
     * @return list<array{name: string, type: string, database: string, hostname: string, prefix: string, default: bool}>
     */
    public static function options(): array
    {
        $default = self::defaultCrudName();
        $out = [];
        foreach (self::names() as $name) {
            $cfg = self::connectionConfig($name);
            $out[] = [
                'name'     => $name,
                'type'     => (string) ($cfg['type'] ?? 'mysql'),
                'database' => (string) ($cfg['database'] ?? ''),
                'hostname' => (string) ($cfg['hostname'] ?? ''),
                'prefix'   => (string) ($cfg['prefix'] ?? ''),
                'default'  => $name === $default,
            ];
        }

        return $out;
    }

    /**
     * normalize 校验并规范化连接名；空值 → 默认连接
     */
    public static function normalize(mixed $name): string
    {
        if (!is_string($name)) {
            return '';
        }
        $name = trim($name);
        if ($name === '') {
            return '';
        }
        if (!preg_match(self::NAME_PATTERN, $name)) {
            throw new \InvalidArgumentException("connection 无效: {$name}");
        }
        if (!in_array($name, self::names(), true)) {
            throw new \InvalidArgumentException("connection 未在 config/database.php 中配置: {$name}");
        }

        return $name;
    }

    /** db 连接实例；空名取默认连接 */
    public static function db(string $name = ''): ConnectionInterface
    {
        self::assertRuntime();

        return $name === '' ? Db::connect() : Db::connect($name);
    }

    /** prefix 连接表前缀 */
    public static function prefix(string $name = ''): string
    {
        $cfg = self::connectionConfig($name === '' ? self::defaultName() : $name);

        return (string) ($cfg['prefix'] ?? '');
    }

    /**
     * query 在指定连接上执行查询
     *
     * @param list<mixed> $bind
     * @return list<array<string, mixed>>
     */
    public static function query(string $connection, string $sql, array $bind = []): array
    {
        /** @var \think\db\PDOConnection $conn */
        $conn = self::db($connection);

        return $conn->query($sql, $bind);
    }

    /** execute 在指定连接上执行写语句 */
    public static function execute(string $connection, string $sql): int
    {
        /** @var \think\db\PDOConnection $conn */
        $conn = self::db($connection);

        return $conn->execute($sql);
    }

    /**
     * tableNames 列出连接内的表（原始全名，含前缀）
     *
     * @return list<string>
     */
    public static function tableNames(string $connection): array
    {
        $rows = self::query($connection, 'SHOW TABLES');
        $names = [];
        foreach ($rows as $row) {
            $full = (string) reset($row);
            if ($full !== '') {
                $names[] = $full;
            }
        }

        return $names;
    }

    /** tableExists 表是否存在（传入无前缀表名） */
    public static function tableExists(string $connection, string $table): bool
    {
        $full = self::prefix($connection) . $table;
        $like = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $full);
        $like = str_replace("'", "''", $like);

        return self::query($connection, "SHOW TABLES LIKE '{$like}'") !== [];
    }

    /**
     * showFullColumns 读取表列信息（传入无前缀表名）
     *
     * @return list<array<string, mixed>>
     */
    public static function showFullColumns(string $connection, string $table): array
    {
        $full = self::prefix($connection) . $table;
        $quoted = '`' . str_replace('`', '``', $full) . '`';

        return self::query($connection, "SHOW FULL COLUMNS FROM {$quoted}");
    }

    /**
     * connections config/database.php 的 connections 原始配置
     *
     * @return array<string, mixed>
     */
    private static function connections(): array
    {
        self::assertRuntime();
        $connections = Config::get('database.connections');

        return is_array($connections) ? $connections : [];
    }

    /**
     * connectionConfig 单个连接的原始配置
     *
     * @return array<string, mixed>
     */
    private static function connectionConfig(string $name): array
    {
        $cfg = self::connections()[$name] ?? null;

        return is_array($cfg) ? $cfg : [];
    }

    /** isMySqlType 连接是否为 MySQL 类型 */
    private static function isMySqlType(string $name): bool
    {
        $type = strtolower((string) (self::connectionConfig($name)['type'] ?? 'mysql'));

        return in_array($type, self::MYSQL_TYPES, true);
    }

    /**
     * assertRuntime 独立入口（extend/crud/cli.php）未加载框架时给出可读提示
     */
    private static function assertRuntime(): void
    {
        if (!class_exists(Config::class)) {
            throw new \RuntimeException(
                '读取数据库连接配置需要 ThinkPHP 运行环境，请改用 php think crud:generate',
            );
        }
    }
}
