<?php
declare (strict_types = 1);

namespace app\admin\model\test\ft;

use think\Model;

/**
 * CreateTime 创建时间 Model（CRUD 生成）
 */
class CreateTime extends Model
{
    /** @var string 数据表名称 */
    protected $name = 'test_ft_create_time';

    /** @var list<string>|true|false */
    protected array|bool $autoWriteTimestamp = true;

    /** @var array<string, string> */
    protected array $type = [
        'id' => 'integer',
        'create_time' => 'integer',
    ];
}
