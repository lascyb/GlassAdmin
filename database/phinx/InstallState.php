<?php

declare(strict_types=1);

namespace phinx;

use think\facade\Config as ThinkConfig;
use think\facade\Db;
use think\facade\Env;

/**
 * InstallState 安装锁与「已有库」探测（删除 app/install 后仍可用）
 */
final class InstallState
{
    /** lockPath public/install.lock */
    public static function lockPath(): string
    {
        return rtrim(root_path(), '/\\') . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'install.lock';
    }

    /** isInstalled 是否已写入安装锁 */
    public static function isInstalled(): bool
    {
        return is_file(self::lockPath());
    }

    /** writeLock 写入安装锁 */
    public static function writeLock(): void
    {
        $dir = dirname(self::lockPath());
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        file_put_contents(self::lockPath(), date('c') . "\n");
    }

    /**
     * ensureLockIfAlreadyProvisioned 无 lock 但当前库已有任意表时补写 lock（不允许再装）
     */
    public static function ensureLockIfAlreadyProvisioned(): void
    {
        if (self::isInstalled()) {
            return;
        }
        try {
            $rows = Db::query(
                "SELECT 1 AS ok FROM information_schema.tables WHERE table_schema = DATABASE() AND table_type = 'BASE TABLE' LIMIT 1",
            );
            if ($rows === []) {
                return;
            }
            self::writeLock();
        } catch (\Throwable) {
            // 未配置或连不上库：视为未安装
        }
    }

    /** installAppExists 安装应用目录是否还在 */
    public static function installAppExists(): bool
    {
        return is_dir(rtrim(root_path(), '/\\') . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'install');
    }

    /**
     * applyDatabaseEnv 把新库配置写入当前进程 env/config（安装请求内立刻可用）
     *
     * @param array{hostname:string,hostport:string,database:string,username:string,password:string,prefix:string,charset?:string} $db
     */
    public static function applyDatabaseEnv(array $db): void
    {
        $charset = $db['charset'] ?? 'utf8mb4';
        $pairs = [
            'DB_TYPE'    => 'mysql',
            'DB_HOST'    => $db['hostname'],
            'DB_NAME'    => $db['database'],
            'DB_USER'    => $db['username'],
            'DB_PASS'    => $db['password'],
            'DB_PORT'    => $db['hostport'],
            'DB_CHARSET' => $charset,
            'DB_PREFIX'  => $db['prefix'],
        ];
        foreach ($pairs as $key => $value) {
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
            Env::set($key, $value);
        }
        $database = ThinkConfig::get('database');
        if (!is_array($database)) {
            $database = [];
        }
        $mysql = (array) ($database['connections']['mysql'] ?? []);
        $database['connections']['mysql'] = array_merge($mysql, [
            'type'     => 'mysql',
            'hostname' => $db['hostname'],
            'database' => $db['database'],
            'username' => $db['username'],
            'password' => $db['password'],
            'hostport' => $db['hostport'],
            'charset'  => $charset,
            'prefix'   => $db['prefix'],
        ]);
        ThinkConfig::set($database, 'database');
        try {
            Db::connect('mysql', true);
        } catch (\Throwable) {
            // 安装过程仍用表单 PDO
        }
    }
}
