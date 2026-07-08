<?php
declare (strict_types = 1);

namespace app\admin\service\auth;

/**
 * AdminAuthPayload 管理端鉴权解析结果（请求上下文）
 */
final readonly class AdminAuthPayload
{
    /**
     * @param list<int> $roleIds 管理员绑定的角色 id 列表
     */
    public function __construct(
        public int $adminId,
        public array $roleIds,
    ) {
    }

    /**
     * isSuper 是否拥有超级管理员角色（role_id=1）
     */
    public function isSuper(): bool
    {
        return in_array(1, $this->roleIds, true);
    }

    /**
     * bindForRequest 绑定当前请求的鉴权载荷（供限流、操作日志等无控制器上下文处读取）
     */
    public static function bindForRequest(self $payload): void
    {
        app()->instance(self::class, $payload);
    }

    /**
     * fromRequest 读取当前请求已绑定的鉴权载荷
     */
    public static function fromRequest(): ?self
    {
        return app()->bound(self::class) ? app(self::class) : null;
    }
}
