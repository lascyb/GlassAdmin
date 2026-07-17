<?php
declare (strict_types = 1);

namespace app\admin\model\test;

use think\Model;

/**
 * Product Product Model（CRUD 生成）
 */
class Product extends Model
{
    /** @var string 数据表名称 */
    protected $name = 'test_product';

    /** @var list<string>|true */
    protected array|bool $autoWriteTimestamp = true;

    /** @var array<string, string> */
    protected array $type = [
        'id' => 'integer',
    ];
}
