<?php
declare (strict_types = 1);

namespace app\admin\model\example;

use think\Model;

/**
 * Tag 示例标签（belongs_to_many 关联目标）
 */
class Tag extends Model
{
    /** @var string 数据表名称 */
    protected $name = 'example_tag';

    /** @var list<string>|true */
    protected array|bool $autoWriteTimestamp = true;

    /** @var array<string, string> */
    protected array $type = [
        'id'     => 'integer',
        'sort'   => 'integer',
        'status' => 'integer',
        'create_time' => 'integer',
        'update_time' => 'integer',
    ];
}
