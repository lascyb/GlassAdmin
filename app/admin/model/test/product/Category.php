<?php
declare (strict_types = 1);

namespace app\admin\model\test\product;

use think\Model;

/**
 * Category Category Model（CRUD 生成）
 */
class Category extends Model
{
    /** @var string 数据表名称 */
    protected $name = 'test_product_category';

    /** @var list<string>|true */
    protected array|bool $autoWriteTimestamp = true;

    /** @var array<string, string> */
    protected array $type = [
        'id' => 'integer',
    ];
}
