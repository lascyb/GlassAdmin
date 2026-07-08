<?php
declare (strict_types = 1);

namespace app\admin\model\example;

use think\Model;

/**
 * Category 示例分类（belongs_to 关联目标）
 */
class Category extends Model
{
    /** @var string 数据表名称 */
    protected $name = 'example_category';

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
