<?php
declare (strict_types = 1);

namespace app\admin\model\auth;

use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\Model;

/**
 * AdminRole 管理员与角色关联（auth_admin_role）
 */
class AdminRole extends Model
{
    /** @var string 表名 */
    protected $name = 'auth_admin_role';

    /**
     * allRoleIds 查询管理员已分配的全部角色 id
     *
     * @return list<int>
     */
    public static function allRoleIds(int $adminId): array
    {
        if ($adminId <= 0) {
            return [];
        }

        return self::where('admin_id', $adminId)->column('role_id');
    }

    /**
     * activeRoleIds 查询管理员当前启用的角色 id（enabled=1）
     *
     * @return list<int>
     */
    public static function activeRoleIds(int $adminId): array
    {
        if ($adminId <= 0) {
            return [];
        }

        return self::where('admin_id', $adminId)->where('enabled', 1)->column('role_id');
    }

    /**
     * rolesPayload 当前用户角色列表（含 enabled）
     *
     * @return list<array{id:int,code:string,name:string,enabled:int}>
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function rolesPayload(int $adminId): array
    {
        $rows = self::alias('ar')
            ->leftJoin('auth_role r', 'r.id = ar.role_id')
            ->where('ar.admin_id', $adminId)
            ->field('ar.role_id,ar.enabled,r.code,r.name')
            ->order('ar.role_id', 'asc')
            ->select()
            ->toArray();

        $roles = [];
        foreach ($rows as $row) {
            $enabled = (int) ($row['enabled'] ?? 0);
            $roles[] = [
                'id'      => (int) $row['role_id'],
                'code'    => (string) ($row['code'] ?? ''),
                'name'    => (string) ($row['name'] ?? ''),
                'enabled' => $enabled,
            ];
        }

        return $roles;
    }

    /**
     * syncActiveRoles 更新用户启用的角色（全量置 0 后启用 role_ids）
     *
     * @param list<int> $roleIds
     */
    public static function syncActiveRoles(int $adminId, array $roleIds): void
    {
        $roleIds = array_values(array_unique(array_filter(array_map('intval', $roleIds), static fn (int $id): bool => $id > 0)));
        self::where('admin_id', $adminId)->update(['enabled' => 0]);
        if ($roleIds !== []) {
            self::where('admin_id', $adminId)->whereIn('role_id', $roleIds)->update(['enabled' => 1]);
        }
    }

    /**
     * syncRoles 同步管理员角色绑定（保留仍有关联行的 enabled）
     *
     * @param list<int> $roleIds
     */
    public static function syncRoles(int $adminId, array $roleIds): void
    {
        $roleIds = array_values(array_unique(array_filter(array_map('intval', $roleIds), static fn (int $id): bool => $id > 0)));
        $existing = self::where('admin_id', $adminId)->column('enabled', 'role_id');
        self::where('admin_id', $adminId)->delete();
        if ($roleIds === []) {
            return;
        }
        $rows = [];
        foreach ($roleIds as $roleId) {
            $enabled = isset($existing[$roleId]) ? (int) $existing[$roleId] : 1;
            if ($enabled !== 0) {
                $enabled = 1;
            }
            $rows[] = ['admin_id' => $adminId, 'role_id' => $roleId, 'enabled' => $enabled];
        }
        self::insertAll($rows);
    }
}
