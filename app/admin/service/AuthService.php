<?php
declare (strict_types = 1);

namespace app\admin\service;

use app\admin\service\auth\AdminAuthPayload;
use Random\RandomException;

/**
 * AuthService 管理端鉴权：opaque access_token + refresh_token（委托 AdminTokenService）
 */
class AuthService
{
    public static function authenticate(string $accessToken): ?AdminAuthPayload
    {
        return AdminTokenService::authenticateAccess($accessToken);
    }

    /**
     * issue 登录成功后签发令牌
     *
     * @param list<int> $roleIds
     * @return array{access_token: string, refresh_token: string, expire_at: int, refresh_expire_at: int}
     * @throws RandomException
     */
    public static function issue(int $adminId, array $roleIds, bool $remember): array
    {
        return AdminTokenService::issuePair($adminId, $roleIds, $remember);
    }

    /**
     * refresh 用 refresh_token 换发新令牌
     *
     * @return array{access_token: string, refresh_token: string, expire_at: int, refresh_expire_at: int}|null
     */
    public static function refresh(string $refreshToken): ?array
    {
        return AdminTokenService::rotate($refreshToken);
    }
}
