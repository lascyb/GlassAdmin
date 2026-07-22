<?php
declare (strict_types = 1);

namespace app\admin\model\auth;

use think\facade\Config;
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
    protected $name = 'auth_admin';

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
     * roles 管理员绑定的角色（auth_admin_role）
     *
     * @return BelongsToMany
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'auth_admin_role', 'role_id', 'admin_id');
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
