<?php
declare (strict_types = 1);

namespace app\admin\model;

use app\admin\model\auth\Role;
use think\facade\Config;
use think\facade\Db;
use think\Model;
use think\model\relation\BelongsToMany;

/**
 * Admin 后台管理员
 *
 * @property int         $id
 * @property string      $username
 * @property string      $password
 * @property string|null $nickname
 * @property string|null $avatar 序列化/读取时为完整 URL；数据库存相对 public/storage 盘根的路径，如 avatar/xxx.jpg
 * @property int         $status  {1:正常,0:禁用}
 * @property int|null    $last_login_time 上次登录时间（Unix 秒）
 */
class Admin extends Model
{
    /** @var string 数据表名称（无前缀时会自动拼接配置中的表前缀） */
    protected $name = 'admin';

    /** @var list<string> 隐藏字段 JSON 序列化时排除 */
    protected array $hidden = ['password'];

    /** @var list<string>|true 字段类型自动转换（true 为自动写入时间戳字段） */
    protected array|bool $autoWriteTimestamp = true;

    /** @var array<string, string> */
    protected array $type = [
        'id'      => 'integer',
        'status'  => 'integer',
        'last_login_time' => 'integer',
        'create_time' => 'integer',
        'update_time' => 'integer',
    ];

    /**
     * roles 管理员绑定的角色（admin_role）
     *
     * @return BelongsToMany
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'admin_role', 'role_id', 'admin_id');
    }

    /**
     * boundRoleIdsFor 查询管理员已分配的全部角色 id
     *
     * @return list<int>
     */
    public static function boundRoleIdsFor(int $adminId): array
    {
        if ($adminId <= 0) {
            return [];
        }

        return array_values(array_unique(array_map(
            'intval',
            Db::name('admin_role')->where('admin_id', $adminId)->column('role_id')
        )));
    }

    /**
     * activeRoleIdsFor 查询管理员当前启用的角色 id（enabled=1）
     *
     * @return list<int>
     */
    public static function activeRoleIdsFor(int $adminId): array
    {
        if ($adminId <= 0) {
            return [];
        }

        return array_values(array_unique(array_map(
            'intval',
            Db::name('admin_role')->where('admin_id', $adminId)->where('enabled', 1)->column('role_id')
        )));
    }

    /**
     * roleIdsFor 查询管理员当前启用的角色 id（登录鉴权用，同 activeRoleIdsFor）
     *
     * @return list<int>
     */
    public static function roleIdsFor(int $adminId): array
    {
        return self::activeRoleIdsFor($adminId);
    }

    /**
     * rolesPayload 当前用户角色列表（含 enabled）与生效 role_ids
     *
     * @return array{roles: list<array{id:int,name:string,enabled:int}>, role_ids: list<int>}
     */
    public static function rolesPayload(int $adminId): array
    {
        $rows = Db::name('admin_role')
            ->alias('ar')
            ->leftJoin('auth_role r', 'r.id = ar.role_id')
            ->where('ar.admin_id', $adminId)
            ->field('ar.role_id,ar.enabled,r.name')
            ->order('ar.role_id', 'asc')
            ->select()
            ->toArray();

        $roles = [];
        $roleIds = [];
        foreach ($rows as $row) {
            $enabled = (int) ($row['enabled'] ?? 0);
            $roles[] = [
                'id'      => (int) $row['role_id'],
                'name'    => (string) ($row['name'] ?? ''),
                'enabled' => $enabled,
            ];
            if ($enabled === 1) {
                $roleIds[] = (int) $row['role_id'];
            }
        }

        return ['roles' => $roles, 'role_ids' => $roleIds];
    }

    /**
     * syncActiveRoles 更新用户启用的角色（全量置 0 后启用 role_ids）
     *
     * @param list<int> $roleIds
     */
    public static function syncActiveRoles(int $adminId, array $roleIds): void
    {
        $roleIds = array_values(array_unique(array_filter(array_map('intval', $roleIds), static fn (int $id): bool => $id > 0)));
        Db::name('admin_role')->where('admin_id', $adminId)->update(['enabled' => 0]);
        if ($roleIds !== []) {
            Db::name('admin_role')->where('admin_id', $adminId)->whereIn('role_id', $roleIds)->update(['enabled' => 1]);
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
        $existing = Db::name('admin_role')->where('admin_id', $adminId)->column('enabled', 'role_id');
        Db::name('admin_role')->where('admin_id', $adminId)->delete();
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
        Db::name('admin_role')->insertAll($rows);
    }

    /**
     * normalizeAvatarStoragePath 将库内或 URL 中的路径规范为相对 public/storage 盘根的路径（如 avatar/xxx.jpg）
     */
    public static function normalizeAvatarStoragePath(string $path): string
    {
        $p = str_replace('\\', '/', trim($path));
        if (preg_match('#^https?://#i', $p)) {
            $parsed = parse_url($p);
            $p       = ltrim((string) ($parsed['path'] ?? ''), '/');
        } else {
            $p = ltrim($p, '/');
        }
        if (str_starts_with($p, 'storage/')) {
            $p = substr($p, strlen('storage/'));
        }

        return $p;
    }

    /**
     * avatarPublicUrl 由相对 public/storage 的路径生成可访问的完整 URL（优先 APP_URL，否则当前请求域名）
     */
    public static function avatarPublicUrl(?string $storedUnderPublicStorageRoot): ?string
    {
        if ($storedUnderPublicStorageRoot === null) {
            return null;
        }
        $trimmed = trim((string) $storedUnderPublicStorageRoot);
        if ($trimmed === '') {
            return null;
        }
        if (preg_match('#^https?://#i', $trimmed)) {
            return $trimmed;
        }
        $rel = self::normalizeAvatarStoragePath($trimmed);
        if ($rel === '') {
            return null;
        }
        $urlBase = rtrim((string) Config::get('filesystem.disks.public.url', '/storage'), '/');
        $pathOnly = $urlBase . '/' . str_replace('\\', '/', $rel);
        $host = rtrim((string) env('APP_URL', ''), '/');
        if ($host !== '') {
            return $host . $pathOnly;
        }
        try {
            $domain = rtrim(request()->domain(), '/');
            if ($domain !== '') {
                return $domain . $pathOnly;
            }
        } catch (\Throwable) {
        }

        return $pathOnly;
    }

    /**
     * deletePublicStorageAvatar 删除 public/storage 下头像文件（仅允许 avatar/ 下安全文件名）
     */
    public static function deletePublicStorageAvatar(?string $dbStored): void
    {
        if ($dbStored === null || $dbStored === '') {
            return;
        }
        $rel = self::normalizeAvatarStoragePath((string) $dbStored);
        if ($rel === '' || preg_match('#^avatar/[a-zA-Z0-9_.-]+$#', $rel) !== 1) {
            return;
        }
        $root = rtrim((string) Config::get('filesystem.disks.public.root'), DIRECTORY_SEPARATOR);
        $full = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
        if (is_file($full)) {
            @unlink($full);
        }
    }

    /**
     * getAvatarAttr 读取时拼接域名，返回可直接用于 img/src 的地址
     *
     * @param string|null $value 库内原始相对路径
     */
    public function getAvatarAttr(?string $value, array $data): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return self::avatarPublicUrl($value);
    }
}
