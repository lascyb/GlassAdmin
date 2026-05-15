<?php
declare (strict_types = 1);

namespace app\service;

use app\model\Admin;
use app\service\auth\AdminAuthPayload;
use Random\RandomException;
use think\facade\Cache;

/**
 * AdminTokenService 管理端 opaque access_token + refresh_token（均存 Cache，非 JWT）
 *
 * access 键 admin_access_token:{token}；refresh 键 admin_refresh_token:{token}
 */
class AdminTokenService
{
    private const string ACCESS_CACHE_PREFIX = 'admin_access_token:';

    private const string REFRESH_CACHE_PREFIX = 'admin_refresh_token:';

    /**
     * issuePair 登录成功后签发 access、refresh 并写入缓存
     *
     * @param int $adminId
     * @param int $roleId
     * @param bool $remember
     * @return array{access_token: string, refresh_token: string, expire_at: int, refresh_expire_at: int}
     * @throws RandomException
     */
    public static function issuePair(int $adminId, int $roleId, bool $remember): array
    {
        $accessTtl = max(60, (int) env('ACCESS_TOKEN_TTL', 7200));
        $refreshTtl = $remember
            ? max(300, (int) env('REFRESH_TOKEN_TTL_REMEMBER', 2592000))
            : max(300, (int) env('REFRESH_TOKEN_TTL_SESSION', 604800));

        $accessRaw = bin2hex(random_bytes(32));
        $refreshRaw = bin2hex(random_bytes(32));
        $now = time();

        Cache::set(
            self::accessCacheKey($accessRaw),
            [
                'admin_id' => $adminId,
                'role_id'  => $roleId,
            ],
            $accessTtl
        );
        Cache::set(
            self::refreshCacheKey($refreshRaw),
            [
                'admin_id'   => $adminId,
                'role_id'    => $roleId,
                'remember'   => $remember,
            ],
            $refreshTtl
        );

        return [
            'access_token'         => $accessRaw,
            'refresh_token'        => $refreshRaw,
            'expire_at'            => $now + $accessTtl,
            'refresh_expire_at'    => $now + $refreshTtl,
        ];
    }

    /**
     * authenticateAccess 校验 access_token 并返回载荷
     */
    public static function authenticateAccess(string $accessToken): ?AdminAuthPayload
    {
        $accessToken = trim($accessToken);
        if ($accessToken === '') {
            return null;
        }

        $payload = Cache::get(self::accessCacheKey($accessToken));
        if (!is_array($payload) || empty($payload['admin_id'])) {
            return null;
        }

        return new AdminAuthPayload((int) $payload['admin_id'], (int) ($payload['role_id'] ?? 0));
    }

    /**
     * rotate 用 refresh_token 换发新的 access + refresh（旋转 refresh，旧 refresh 删除；旧 access 依赖 TTL 自然过期）
     *
     * @return array{access_token: string, refresh_token: string, expire_at: int, refresh_expire_at: int}|null
     */
    public static function rotate(string $refreshToken): ?array
    {
        $refreshToken = trim($refreshToken);
        if ($refreshToken === '') {
            return null;
        }

        $key = self::refreshCacheKey($refreshToken);
        $payload = Cache::get($key);
        if (!is_array($payload) || empty($payload['admin_id'])) {
            return null;
        }

        $adminId = (int) $payload['admin_id'];
        $roleId  = (int) ($payload['role_id'] ?? 0);
        $remember = !empty($payload['remember']);

        $user = Admin::find($adminId);
        if (!$user || (int) $user->getAttr('status') !== 1) {
            Cache::delete($key);

            return null;
        }

        Cache::delete($key);

        return self::issuePair($adminId, $roleId, $remember);
    }

    /**
     * revokeAccess 作废 access（登出等）
     */
    public static function revokeAccess(string $accessToken): void
    {
        $accessToken = trim($accessToken);
        if ($accessToken !== '') {
            Cache::delete(self::accessCacheKey($accessToken));
        }
    }

    /**
     * revokeRefresh 作废 refresh
     */
    public static function revokeRefresh(string $refreshToken): void
    {
        $refreshToken = trim($refreshToken);
        if ($refreshToken !== '') {
            Cache::delete(self::refreshCacheKey($refreshToken));
        }
    }

    private static function accessCacheKey(string $token): string
    {
        return self::ACCESS_CACHE_PREFIX . $token;
    }

    private static function refreshCacheKey(string $refreshToken): string
    {
        return self::REFRESH_CACHE_PREFIX . $refreshToken;
    }
}
