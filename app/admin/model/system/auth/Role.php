<?php
declare (strict_types = 1);

namespace app\admin\model\system\auth;

use think\Model;

/**
 * Role 权限角色（树形 pid 仅展示组织，无权限继承）
 *
 * @property int         $id
 * @property int         $pid
 * @property string      $name
 * @property string|null $desc
 * @property int         $sort
 * @property int         $status {1:启用,0:禁用}
 */
class Role extends Model
{
    /** @var string 表名 */
    protected $name = 'system_auth_role';

    /** @var list<string>|true */
    protected array|bool $autoWriteTimestamp = true;

    /** @var string 与 datetime 列一致（全局 datetime_format=false 时须显式指定） */
    protected string $dateFormat = 'Y-m-d H:i:s';

    /** @var array<string, string> */
    protected array $type = [
        'id' => 'integer',
        'pid' => 'integer',
        'sort' => 'integer',
        'status' => 'integer',
    ];

    /**
     * children 子角色（仅组织结构）
     *
     * @return \think\model\relation\HasMany
     */
    public function children()
    {
        return $this->hasMany(self::class, 'pid', 'id')->order('sort', 'asc');
    }

    /**
     * frontendRules 已勾选的前端规则（扁平，无父子继承）
     *
     * @return \think\model\relation\BelongsToMany
     */
    public function frontendRules()
    {
        return $this->belongsToMany(Rule::class, 'system_auth_role_rule', 'rule_id', 'role_id');
    }
}
