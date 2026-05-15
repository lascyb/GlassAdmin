<?php
declare (strict_types = 1);

namespace app\model\auth;

use think\Model;

/**
 * Rule 权限规则（后端树 menu/api + 前端树 menu/view/btn）
 *
 * @property int         $id
 * @property string      $scope   {backend:后端,frontend:前端}
 * @property string      $type
 * @property int         $pid
 * @property string      $name
 * @property string|null $path
 * @property string|null $method
 * @property string|null $component
 * @property string|null $icon      el:组件名、url:图片地址、local:stem
 * @property int         $sort
 * @property int         $status  {1:启用,0:禁用}
 */
class Rule extends Model
{
    /** @var string 表名 */
    protected $name = 'auth_rule';

    /** @var list<string>|true */
    protected array|bool $autoWriteTimestamp = true;

    /** @var array<string, string> */
    protected $type = [
        'id' => 'integer',
        'pid' => 'integer',
        'sort' => 'integer',
        'status' => 'integer',
    ];

    /**
     * children 直接子节点
     *
     * @return \think\model\relation\HasMany
     */
    public function children()
    {
        return $this->hasMany(self::class, 'pid', 'id')->order('sort', 'asc');
    }

    /**
     * parent 父节点
     *
     * @return \think\model\relation\BelongsTo
     */
    public function parent()
    {
        return $this->belongsTo(self::class, 'pid', 'id');
    }
}
