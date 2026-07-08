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
}
