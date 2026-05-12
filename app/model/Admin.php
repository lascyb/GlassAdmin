<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

/**
 * Admin 后台管理员
 */
class Admin extends Model
{
    /** @var string 数据表名称（无前缀时会自动拼接配置中的表前缀） */
    protected $name = 'admin';

    /** @var list<string> 隐藏字段 JSON 序列化时排除 */
    protected array $hidden = ['password'];

    /** @var list<string>|true 字段类型自动转换（true 为自动写入时间戳字段） */
    protected array|bool $autoWriteTimestamp = true;
}
