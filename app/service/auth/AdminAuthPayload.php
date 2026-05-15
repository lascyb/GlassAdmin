<?php
declare (strict_types = 1);

namespace app\service\auth;

/**
 * AdminAuthPayload 管理端鉴权解析结果（请求上下文）
 */
final readonly class AdminAuthPayload
{
    public function __construct(
        public int $adminId,
        public int $roleId,
    ) {
    }
}
