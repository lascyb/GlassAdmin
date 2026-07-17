<?php
declare (strict_types = 1);

namespace app\admin\model\test\ft;

use think\Model;

/**
 * UpdateTime 更新时间 Model（CRUD 生成）
 */
class UpdateTime extends Model
{
    /** @var string 数据表名称 */
    protected $name = 'test_ft_update_time';

    /** @var list<string>|true|false */
    protected array|bool $autoWriteTimestamp = true;

    /** @var array<string, string> */
    protected array $type = [
        'id' => 'integer',
        'update_time' => 'integer',
    ];
}
