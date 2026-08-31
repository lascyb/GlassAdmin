<?php
declare (strict_types = 1);

namespace app\admin\model\system\auth;

use app\admin\model\system\Attachment;
use think\Model;
use think\model\relation\BelongsToMany;

/**
 * Admin 后台管理员
 *
 * @property int         $id
 * @property string      $username
 * @property string      $password
 * @property string|null $nickname
 * @property string|null $avatar 读取时为完整 URL；库内为相对 public/storage 路径，如 avatar/xxx.jpg
 * @property int         $status  {1:正常,0:禁用}
 * @property int|null    $last_login_time 上次登录时间（Unix 秒）
 */
class Admin extends Model
{
    /** @var string 数据表名称（无前缀时会自动拼接配置中的表前缀） */
    protected $name = 'system_auth_admin';

    /** @var list<string> 隐藏字段 JSON 序列化时排除 */
    protected array $hidden = ['password'];

    /** @var list<string>|true 字段类型自动转换（true 为自动写入时间戳字段） */
    protected array|bool $autoWriteTimestamp = true;

    /** @var array<string, string> */
    protected array $type = [
        'id'              => 'integer',
        'status'          => 'integer',
        'last_login_time' => 'integer',
        'create_time'     => 'integer',
        'update_time'     => 'integer',
    ];

    /**
     * roles 管理员绑定的角色（system_auth_admin_role）
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'system_auth_admin_role', 'role_id', 'admin_id');
    }

    /**
     * getAvatarAttr 读取时拼接可访问 URL
     *
     * @param string|null         $value 库内原始相对路径
     * @param array<string,mixed> $data
     */
    public function getAvatarAttr(?string $value, array $data): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Attachment::publicUrl($value);
    }
}
