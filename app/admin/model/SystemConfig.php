<?php
declare (strict_types = 1);

namespace app\admin\model;

use think\Model;

/**
 * SystemConfig 系统配置
 *
 * @property int         $id
 * @property string      $key        配置键名（唯一）
 * @property string      $value      配置值（纯文本存储）
 * @property string      $group      配置分组
 * @property string      $name       配置名称
 * @property string|null $description 配置说明
 * @property string      $type       输入类型
 * @property string|null $options    选项（JSON 数组）
 * @property int         $sort       排序
 * @property int         $status     {1:启用,0:禁用}
 * @property int         $create_time
 * @property int         $update_time
 */
class SystemConfig extends Model
{
    /** @var string 表名 */
    protected $name = 'system_config';

    /** @var list<string>|true */
    protected array|bool $autoWriteTimestamp = true;

    /** @var array<string, string> */
    protected $type = [
        'id'     => 'integer',
        'sort'    => 'integer',
        'status'  => 'integer',
    ];
}
