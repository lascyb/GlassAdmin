<?php
declare (strict_types = 1);

namespace app\admin\model\test\product;

use think\Model;

/**
 * Tags Tags Model（CRUD 生成）
 */
class Tags extends Model
{
    /** @var string 数据表名称 */
    protected $name = 'test_product_tags';

    /** @var list<string>|true */
    protected array|bool $autoWriteTimestamp = true;

    /** @var array<string, string> */
    protected array $type = [
        'id' => 'integer',
    ];
}
