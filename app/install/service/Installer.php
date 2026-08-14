<?php

declare(strict_types=1);

namespace app\install\service;

use PDO;
use PDOException;
use phinx\InstallState;
use phinx\Runner;

/**
 * Installer 网页安装核心（表单 PDO：测连、建库、SQL 初始化、超管、RSA、lock）
 */
final class Installer
{
    public const CHARSET = 'utf8mb4';
    public const COLLATION = 'utf8mb4_0900_ai_ci';

    /**
     * testConnection 测连账号与主机，不创建数据库
     *
     * @param array{hostname:string,hostport:string,database:string,username:string,password:string,prefix?:string} $db
     * @return array{exists: bool, occupied: bool}
     */
    public function testConnection(array $db): array
    {
        $this->assertDbForm($db);
        $server = $this->connect($db, null);
        $exists = $this->databaseExists($server, $db['database']);
        $occupied = false;
        if ($exists) {
            $occupied = $this->hasAnyTable($this->connect($db, $db['database']));
        }

        return ['exists' => $exists, 'occupied' => $occupied];
    }

    /**
     * ensureDatabase 库不存在则创建（仅安装时调用）
     *
     * @param array{hostname:string,hostport:string,database:string,username:string,password:string,prefix?:string} $db
     */
    public function ensureDatabase(array $db): void
    {
        $this->assertDbForm($db);
        $server = $this->connect($db, null);
        if ($this->databaseExists($server, $db['database'])) {
            return;
        }
        try {
            $server->exec(
                'CREATE DATABASE IF NOT EXISTS ' . $this->quoteIdent($db['database'])
                    . ' DEFAULT CHARACTER SET ' . self::CHARSET
                    . ' COLLATE ' . self::COLLATION
            );
        } catch (PDOException $e) {
            throw new \RuntimeException(
                '无法创建数据库 `' . $db['database'] . '`，请确认账号有 CREATE 权限：' . $this->pdoDetail($e),
                0,
                $e,
            );
        }
    }

    /**
     * run 完整安装
     *
     * @param array{hostname:string,hostport:string,database:string,username:string,password:string,prefix:string} $db
     * @return array{sql_log: list<string>}
     */
    public function run(array $db, string $adminUsername, string $adminPassword, string $adminNickname, string $siteTitle, string $publicOrigin = ''): array
    {
        $this->assertDbForm($db);
        $adminUsername = trim($adminUsername);
        $adminNickname = trim($adminNickname) !== '' ? trim($adminNickname) : $adminUsername;
        $siteTitle = trim($siteTitle) !== '' ? trim($siteTitle) : 'GlassAdmin';
        $publicOrigin = rtrim($publicOrigin, '/');
        if (!preg_match('/^[A-Za-z0-9_]{3,32}$/', $adminUsername)) {
            throw new \InvalidArgumentException('管理员用户名须为 3–32 位字母、数字或下划线');
        }
        if (strlen($adminPassword) < 6) {
            throw new \InvalidArgumentException('管理员密码至少 6 位');
        }

        $this->ensureDatabase($db);
        $pdo = $this->connect($db, $db['database']);
        if ($this->hasAnyTable($pdo)) {
            throw new \InvalidArgumentException('目标数据库已有数据表，请使用空库或清空后再安装');
        }

        $root = rtrim(root_path(), '/\\');
        $envValues = [
            'DB_TYPE'    => 'mysql',
            'DB_HOST'    => $db['hostname'],
            'DB_NAME'    => $db['database'],
            'DB_USER'    => $db['username'],
            'DB_PASS'    => $db['password'],
            'DB_PORT'    => $db['hostport'],
            'DB_CHARSET' => self::CHARSET,
            'DB_PREFIX'  => $db['prefix'],
        ];
        if ($publicOrigin !== '') {
            $envValues['APP_URL'] = $publicOrigin;
        }
        $writer = new EnvWriter();
        $writer->write(
            $root . DIRECTORY_SEPARATOR . '.env',
            $root . DIRECTORY_SEPARATOR . '.example.env',
            $envValues,
        );
        if ($publicOrigin !== '') {
            $writer->writeViteDevelopment(
                $root . DIRECTORY_SEPARATOR . 'web' . DIRECTORY_SEPARATOR . '.env.development',
                $publicOrigin . '/admin',
            );
        }
        InstallState::applyDatabaseEnv($db);

        $log = SqlLoader::run($pdo, $db['prefix']);
        $log[] = Runner::baseline($pdo, $db['database'], $db['prefix'], self::CHARSET);
        $this->ensureAdmin($pdo, $db['prefix'], $adminUsername, $adminPassword, $adminNickname);
        $this->upsertConfig($pdo, $db['prefix'], 'site_title', $siteTitle);
        $this->ensureRsaKey($pdo, $db['prefix']);
        InstallState::writeLock();

        return [
            'sql_log' => $log,
        ];
    }

    /**
     * envCheck 环境检测
     *
     * @return list<array{name:string,ok:bool,detail:string}>
     */
    public function envCheck(): array
    {
        $root = rtrim(root_path(), '/\\');
        $items = [];
        $items[] = [
            'name'   => 'PHP 版本 ≥ 8.3',
            'ok'     => version_compare(PHP_VERSION, '8.3.0', '>='),
            'detail' => PHP_VERSION,
        ];
        foreach (['pdo_mysql' => 'PDO MySQL', 'openssl' => 'OpenSSL'] as $ext => $label) {
            $on = extension_loaded($ext);
            $items[] = [
                'name'   => '扩展 ' . $label,
                'ok'     => $on,
                'detail' => $on ? '已加载' : '未加载',
            ];
        }
        $paths = [
            $root . DIRECTORY_SEPARATOR . '.env' => '.env',
            $root . DIRECTORY_SEPARATOR . 'runtime' => 'runtime/',
            $root . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'storage' => 'public/storage/',
            $root . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'install.lock' => 'public/install.lock',
            $root . DIRECTORY_SEPARATOR . 'web' . DIRECTORY_SEPARATOR . '.env.development' => 'web/.env.development',
        ];
        foreach ($paths as $path => $label) {
            $ok = $this->isWritableTarget($path);
            $items[] = [
                'name'   => '可写 ' . $label,
                'ok'     => $ok,
                'detail' => $ok ? '可写' : '不可写',
            ];
        }

        return $items;
    }

    /**
     * removeInstallApp 删除 app/install（须已安装）
     */
    public function removeInstallApp(): void
    {
        if (!InstallState::isInstalled()) {
            throw new \RuntimeException('系统尚未安装完成，不能删除安装程序');
        }
        $dir = rtrim(root_path(), '/\\') . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'install';
        if (!is_dir($dir)) {
            return;
        }
        $this->deleteDirectory($dir);
        if (is_dir($dir)) {
            throw new \RuntimeException('未能删除 app/install，请手动执行：rm -rf app/install');
        }
    }

    /**
     * @param array{hostname:string,hostport:string,database:string,username:string,password:string,prefix?:string} $db
     */
    private function connect(array $db, ?string $database): PDO
    {
        $dsn = 'mysql:host=' . $db['hostname'] . ';port=' . $db['hostport'] . ';charset=' . self::CHARSET;
        if ($database !== null && $database !== '') {
            $dsn .= ';dbname=' . $database;
        }
        try {
            $pdo = new PDO($dsn, $db['username'], $db['password'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            throw new \RuntimeException('数据库连接失败：' . $this->pdoDetail($e), 0, $e);
        }

        return $pdo;
    }

    /**
     * @param array{hostname?:string,hostport?:string,database?:string,username?:string,password?:mixed,prefix?:string} $db
     */
    private function assertDbForm(array &$db): void
    {
        $db['hostname'] = trim((string) ($db['hostname'] ?? ''));
        $db['hostport'] = trim((string) ($db['hostport'] ?? '3306'));
        $db['database'] = trim((string) ($db['database'] ?? ''));
        $db['username'] = trim((string) ($db['username'] ?? ''));
        $db['password'] = (string) ($db['password'] ?? '');
        $db['prefix'] = trim((string) ($db['prefix'] ?? ''));
        if ($db['hostname'] === '' || $db['database'] === '' || $db['username'] === '') {
            throw new \InvalidArgumentException('请填写数据库主机、库名和用户名');
        }
        if (!preg_match('/^[0-9]{1,5}$/', $db['hostport']) || (int) $db['hostport'] < 1 || (int) $db['hostport'] > 65535) {
            throw new \InvalidArgumentException('数据库端口无效');
        }
        if (!preg_match('/^[A-Za-z0-9_]+$/', $db['database'])) {
            throw new \InvalidArgumentException('数据库名仅允许字母、数字和下划线');
        }
        if ($db['prefix'] !== '' && !preg_match('/^[A-Za-z0-9_]+$/', $db['prefix'])) {
            throw new \InvalidArgumentException('表前缀仅允许字母、数字和下划线');
        }
        if ($db['prefix'] !== '' && !str_ends_with($db['prefix'], '_')) {
            $db['prefix'] .= '_';
        }
    }

    private function databaseExists(PDO $pdo, string $name): bool
    {
        $st = $pdo->prepare('SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ? LIMIT 1');
        $st->execute([$name]);

        return $st->fetchColumn() !== false;
    }

    /** hasAnyTable 当前库是否已有任意基表 */
    private function hasAnyTable(PDO $pdo): bool
    {
        $st = $pdo->query(
            "SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_type = 'BASE TABLE' LIMIT 1",
        );

        return $st !== false && $st->fetchColumn() !== false;
    }

    private function pdoDetail(PDOException $e): string
    {
        $msg = preg_replace('/SQLSTATE\[[^\]]+\]\s*/', '', $e->getMessage()) ?? $e->getMessage();

        return trim($msg);
    }

    private function quoteIdent(string $name): string
    {
        return '`' . str_replace('`', '', $name) . '`';
    }

    private function table(string $prefix, string $name): string
    {
        return $this->quoteIdent($prefix . $name);
    }

    private function ensureAdmin(PDO $pdo, string $prefix, string $username, string $password, string $nickname): void
    {
        $table = $this->table($prefix, 'auth_admin');
        $st = $pdo->prepare('SELECT `id` FROM ' . $table . ' WHERE `username` = ? LIMIT 1');
        $st->execute([$username]);
        $id = $st->fetchColumn();
        $now = time();
        $hash = password_hash($password, PASSWORD_BCRYPT);
        if ($id !== false && $id !== null) {
            $adminId = (int) $id;
            $upd = $pdo->prepare('UPDATE ' . $table . ' SET `password` = ?, `nickname` = ?, `status` = 1, `update_time` = ? WHERE `id` = ?');
            $upd->execute([$hash, $nickname, $now, $adminId]);
        } else {
            $ins = $pdo->prepare(
                'INSERT INTO ' . $table . ' (`username`,`password`,`nickname`,`status`,`create_time`,`update_time`) VALUES (?,?,?,1,?,?)',
            );
            $ins->execute([$username, $hash, $nickname, $now, $now]);
            $adminId = (int) $pdo->lastInsertId();
        }
        $pivot = $this->table($prefix, 'auth_admin_role');
        $pdo->prepare('DELETE FROM ' . $pivot . ' WHERE `admin_id` = ?')->execute([$adminId]);
        $pdo->prepare('INSERT INTO ' . $pivot . ' (`admin_id`,`role_id`,`enabled`) VALUES (?,1,1)')->execute([$adminId]);
    }

    private function upsertConfig(PDO $pdo, string $prefix, string $key, string $value): void
    {
        $table = $this->table($prefix, 'system_config');
        $st = $pdo->prepare('SELECT `id` FROM ' . $table . ' WHERE `key` = ? LIMIT 1');
        $st->execute([$key]);
        $id = $st->fetchColumn();
        $now = time();
        if ($id !== false && $id !== null) {
            $pdo->prepare('UPDATE ' . $table . ' SET `value` = ?, `update_time` = ? WHERE `id` = ?')
                ->execute([$value, $now, (int) $id]);

            return;
        }
        $pdo->prepare(
            'INSERT INTO ' . $table . ' (`key`,`value`,`group`,`name`,`type`,`sort`,`status`,`create_time`,`update_time`)'
                . ' VALUES (?,?,?,?,?,?,1,?,?)',
        )->execute([$key, $value, '全局配置', '网站名', 'input', 0, $now, $now]);
    }

    private function ensureRsaKey(PDO $pdo, string $prefix): void
    {
        $table = $this->table($prefix, 'system_rsa_key');
        $now = time();
        $st = $pdo->query('SELECT COUNT(*) FROM ' . $table . ' WHERE `status` = 1 AND `expire_time` > ' . ($now + 600));
        $count = $st !== false ? (int) $st->fetchColumn() : 0;
        if ($count > 0) {
            return;
        }
        $resource = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);
        if ($resource === false) {
            throw new \RuntimeException('生成 RSA 密钥失败');
        }
        $privateKey = '';
        if (!openssl_pkey_export($resource, $privateKey)) {
            throw new \RuntimeException('导出 RSA 私钥失败');
        }
        $details = openssl_pkey_get_details($resource);
        if (!is_array($details) || !isset($details['key'])) {
            throw new \RuntimeException('导出 RSA 公钥失败');
        }
        $ins = $pdo->prepare(
            'INSERT INTO ' . $table . ' (`serial`,`public_key`,`private_key`,`expire_time`,`status`,`create_time`,`update_time`)'
                . ' VALUES (?,?,?,?,1,?,?)',
        );
        $ins->execute([uniqid('', true), (string) $details['key'], $privateKey, $now + 7200, $now, $now]);
    }

    private function isWritableTarget(string $path): bool
    {
        if (is_file($path) || is_dir($path)) {
            return is_writable($path);
        }
        $parent = dirname($path);

        return is_dir($parent) && is_writable($parent);
    }

    private function deleteDirectory(string $dir): void
    {
        $items = @scandir($dir);
        if ($items === false) {
            return;
        }
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path) && !is_link($path)) {
                $this->deleteDirectory($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }
}
