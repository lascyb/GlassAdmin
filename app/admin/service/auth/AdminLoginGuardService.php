<?php

declare(strict_types=1);

namespace app\admin\service\auth;

use think\facade\Cache;
use think\facade\Config;
use think\facade\Log;
use think\facade\Request;

/**
 * AdminLoginGuardService 登录防暴破（Cache）与登录文件日志
 *
 * 账号+IP：连续失败达阈值后冷却，时长按失败次数指数翻倍。
 * IP 维：密码 / 飞书 / Refresh 失败共用；成功不清 IP 计数。
 * 业务审计日志由 record* / assert* 内部写入，调用方勿再写 writeLog。
 */
final class AdminLoginGuardService
{
    private const string KEY_PREFIX = 'admin_login:';

    /**
     * assertAttemptAllowed 密码登录前：IP 与账号+IP 均须未在冷却中
     *
     * @throws LoginGuardException
     */
    public static function assertAttemptAllowed(string $username, string $ip): void
    {
        if (!self::enabled()) {
            return;
        }
        self::assertIpAllowed($ip);

        $user = self::normalizeUsername($username);
        self::flushBlockedSummaryIfNeeded('account', $user, $ip);

        $remain = self::lockRemainSeconds(self::accountLockKey($user, $ip));
        if ($remain > 0) {
            self::incBlockCount(self::accountBlockKey($user, $ip));
            throw new LoginGuardException('account_locked', $remain);
        }
    }

    /**
     * assertIpAllowed 飞书 / Refresh 等仅 IP 维入口
     *
     * @throws LoginGuardException
     */
    public static function assertIpAllowed(string $ip): void
    {
        if (!self::enabled()) {
            return;
        }
        self::flushBlockedSummaryIfNeeded('ip', '', $ip);

        $remain = self::lockRemainSeconds(self::ipLockKey($ip));
        if ($remain > 0) {
            self::incBlockCount(self::ipBlockKey($ip));
            throw new LoginGuardException('ip_locked', $remain);
        }
    }

    /**
     * recordFailure 密码登录失败：写审计日志 + 账号/IP 两维计数（可能上锁）
     *
     * @param string   $reason  wrong_password | no_user | disabled
     * @param int|null $adminId 已知管理员 id，可空
     */
    public static function recordFailure(
        string $username,
        string $ip,
        string $reason,
        ?int $adminId = null,
    ): void {
        $user = self::normalizeUsername($username);
        self::writeLog([
            'username' => $user,
            'admin_id' => $adminId,
            'ip'       => $ip,
            'result'   => 0,
            'reason'   => $reason,
        ]);

        if (!self::enabled()) {
            return;
        }

        self::bumpIpFailure($ip);

        $max = max(1, (int) Config::get('admin_login.account_ip_max', 5));
        $n   = self::incFailCount(self::accountFailKey($user, $ip), self::accountFailTtl());
        if ($n < $max) {
            return;
        }

        $base    = max(1, (int) Config::get('admin_login.account_ip_lock', 900));
        $lockMax = max(1, (int) Config::get('admin_login.lock_max', 86400));
        $seconds = self::cooldownSeconds($base, $n, $max, $lockMax);
        self::setLock(self::accountLockKey($user, $ip), $seconds);
        self::writeLog([
            'username'     => $user,
            'admin_id'     => $adminId,
            'ip'           => $ip,
            'result'       => 0,
            'reason'       => 'account_locked',
            'lock_seconds' => $seconds,
        ]);
    }

    /**
     * recordIpFailure 仅 IP 维失败：写审计日志 + IP 计数（飞书 / Refresh）
     *
     * @param string   $reason   oauth_fail | refresh_invalid | no_user | disabled
     * @param int|null $adminId  已知管理员 id，可空
     * @param string   $username 可选，仅写入日志
     */
    public static function recordIpFailure(
        string $ip,
        string $reason,
        ?int $adminId = null,
        string $username = '',
    ): void {
        self::writeLog([
            'username' => $username !== '' ? self::normalizeUsername($username) : '',
            'admin_id' => $adminId,
            'ip'       => $ip,
            'result'   => 0,
            'reason'   => $reason,
        ]);

        if (!self::enabled()) {
            return;
        }
        self::bumpIpFailure($ip);
    }

    /**
     * recordSuccess 密码登录成功：清零账号+IP 计数并写 success 日志（不清 IP 维）
     */
    public static function recordSuccess(
        string $username,
        string $ip,
        ?int $adminId = null,
    ): void {
        $user = self::normalizeUsername($username);
        if (self::enabled()) {
            self::deleteKeys([
                self::accountFailKey($user, $ip),
                self::accountLockKey($user, $ip),
                self::accountBlockKey($user, $ip),
            ]);
        }
        self::writeLog([
            'username' => $user,
            'admin_id' => $adminId,
            'ip'       => $ip,
            'result'   => 1,
            'reason'   => 'success',
        ]);
    }

    /**
     * recordIpSuccess 飞书 / Refresh 成功：仅写 success 日志（不清任何计数）
     */
    public static function recordIpSuccess(
        string $ip,
        ?int $adminId = null,
        string $username = '',
    ): void {
        self::writeLog([
            'username' => $username !== '' ? self::normalizeUsername($username) : $username,
            'admin_id' => $adminId,
            'ip'       => $ip,
            'result'   => 1,
            'reason'   => 'success',
        ]);
    }

    /**
     * normalizeUsername 用户名 trim + 小写
     */
    private static function normalizeUsername(string $username): string
    {
        return strtolower(trim($username));
    }

    /**
     * bumpIpFailure IP 维失败 +1，达阈则上锁并写 ip_locked
     */
    private static function bumpIpFailure(string $ip): void
    {
        $max    = max(1, (int) Config::get('admin_login.ip_max', 20));
        $window = max(1, (int) Config::get('admin_login.ip_window', 600));
        $n      = self::incFailCount(self::ipFailKey($ip), $window);
        if ($n < $max) {
            return;
        }

        $base    = max(1, (int) Config::get('admin_login.ip_lock', 1800));
        $lockMax = max(1, (int) Config::get('admin_login.lock_max', 86400));
        $seconds = self::cooldownSeconds($base, $n, $max, $lockMax);
        self::setLock(self::ipLockKey($ip), $seconds);
        self::writeLog([
            'username'     => '',
            'admin_id'     => null,
            'ip'           => $ip,
            'result'       => 0,
            'reason'       => 'ip_locked',
            'lock_seconds' => $seconds,
        ]);
    }

    /**
     * writeLog 写登录审计文件日志；失败不影响登录
     *
     * @param array{
     *   username?: string,
     *   admin_id?: int|null,
     *   ip?: string,
     *   result?: int,
     *   reason: string,
     *   blocked_count?: int,
     *   lock_seconds?: int,
     *   ua?: string
     * } $row
     */
    private static function writeLog(array $row): void
    {
        try {
            $channel = (string) Config::get('admin_login.log_channel', 'login');
            $payload = [
                'time'     => date('Y-m-d H:i:s'),
                'username' => (string) ($row['username'] ?? ''),
                'admin_id' => $row['admin_id'] ?? null,
                'ip'       => (string) ($row['ip'] ?? ''),
                'ua'       => (string) ($row['ua'] ?? self::currentUserAgent()),
                'result'   => (int) ($row['result'] ?? 0),
                'reason'   => (string) ($row['reason'] ?? ''),
            ];
            if (isset($row['blocked_count'])) {
                $payload['blocked_count'] = (int) $row['blocked_count'];
            }
            if (isset($row['lock_seconds'])) {
                $payload['lock_seconds'] = (int) $row['lock_seconds'];
            }
            $line = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($line === false) {
                return;
            }
            Log::channel($channel)->info($line);
        } catch (\Throwable) {
        }
    }

    /**
     * enabled 是否启用限流
     */
    private static function enabled(): bool
    {
        return (bool) Config::get('admin_login.enable', true);
    }

    /**
     * cooldownSeconds 冷却秒数 = min(base * 2^(n-max), lockMax)
     */
    private static function cooldownSeconds(int $base, int $n, int $max, int $lockMax): int
    {
        $exp = max(0, $n - $max);
        if ($exp >= 31) {
            return $lockMax;
        }
        $seconds = $base * (2 ** $exp);

        return (int) min($seconds, $lockMax);
    }

    /**
     * accountFailTtl 账号失败计数保留时长（至少覆盖最长冷却）
     */
    private static function accountFailTtl(): int
    {
        return max(86400, (int) Config::get('admin_login.lock_max', 86400));
    }

    /**
     * incFailCount 失败次数 +1（结构化 [c,e]，兼容 file 驱动过期）
     */
    private static function incFailCount(string $key, int $ttl): int
    {
        $now   = time();
        $raw   = Cache::get($key);
        $count = 0;
        if (is_array($raw) && isset($raw['c'], $raw['e']) && (int) $raw['e'] > $now) {
            $count = (int) $raw['c'];
        }
        $count++;
        $expireAt = $now + $ttl;
        Cache::set($key, ['c' => $count, 'e' => $expireAt], $ttl);

        return $count;
    }

    /**
     * setLock 写入冷却截止时间
     */
    private static function setLock(string $key, int $seconds): void
    {
        $seconds = max(1, $seconds);
        $until   = time() + $seconds;
        Cache::set($key, ['until' => $until], $seconds);
    }

    /**
     * lockRemainSeconds 剩余冷却秒数；0 表示未锁或已过期
     */
    private static function lockRemainSeconds(string $key): int
    {
        $raw = Cache::get($key);
        if (!is_array($raw) || !isset($raw['until'])) {
            return 0;
        }
        $remain = (int) $raw['until'] - time();

        return $remain > 0 ? $remain : 0;
    }

    /**
     * flushBlockedSummaryIfNeeded 冷却已过期且有拦截累计时写 blocked_summary
     */
    private static function flushBlockedSummaryIfNeeded(string $dim, string $user, string $ip): void
    {
        $lockKey = $dim === 'account'
            ? self::accountLockKey($user, $ip)
            : self::ipLockKey($ip);
        if (self::lockRemainSeconds($lockKey) > 0) {
            return;
        }
        self::writeBlockedSummaryIfAny($dim, $user, $ip);
        Cache::delete($lockKey);
    }

    /**
     * writeBlockedSummaryIfAny 若有 blockcnt 则写日志并清零
     */
    private static function writeBlockedSummaryIfAny(string $dim, string $user, string $ip): void
    {
        $blockKey = $dim === 'account'
            ? self::accountBlockKey($user, $ip)
            : self::ipBlockKey($ip);
        $raw   = Cache::get($blockKey);
        $count = 0;
        if (is_array($raw) && isset($raw['c'])) {
            $count = (int) $raw['c'];
        } elseif (is_numeric($raw)) {
            $count = (int) $raw;
        }
        if ($count <= 0) {
            Cache::delete($blockKey);

            return;
        }
        self::writeLog([
            'username'      => $user,
            'admin_id'      => null,
            'ip'            => $ip,
            'result'        => 0,
            'reason'        => 'blocked_summary',
            'blocked_count' => $count,
        ]);
        Cache::delete($blockKey);
    }

    /**
     * incBlockCount 冷却期内拦截次数 +1
     */
    private static function incBlockCount(string $key): void
    {
        $ttl   = self::accountFailTtl();
        $raw   = Cache::get($key);
        $count = 0;
        if (is_array($raw) && isset($raw['c'])) {
            $count = (int) $raw['c'];
        }
        $count++;
        Cache::set($key, ['c' => $count], $ttl);
    }

    /**
     * @param list<string> $keys
     */
    private static function deleteKeys(array $keys): void
    {
        foreach ($keys as $key) {
            Cache::delete($key);
        }
    }

    private static function accountFailKey(string $user, string $ip): string
    {
        return self::KEY_PREFIX . 'fail:' . $user . ':' . $ip;
    }

    private static function accountLockKey(string $user, string $ip): string
    {
        return self::KEY_PREFIX . 'lock:' . $user . ':' . $ip;
    }

    private static function accountBlockKey(string $user, string $ip): string
    {
        return self::KEY_PREFIX . 'blockcnt:account:' . $user . ':' . $ip;
    }

    private static function ipFailKey(string $ip): string
    {
        return self::KEY_PREFIX . 'ipfail:' . $ip;
    }

    private static function ipLockKey(string $ip): string
    {
        return self::KEY_PREFIX . 'iplock:' . $ip;
    }

    private static function ipBlockKey(string $ip): string
    {
        return self::KEY_PREFIX . 'blockcnt:ip:' . $ip;
    }

    private static function currentUserAgent(): string
    {
        try {
            return (string) Request::header('user-agent', '');
        } catch (\Throwable) {
            return '';
        }
    }
}
