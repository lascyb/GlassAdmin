<?php
declare (strict_types = 1);

namespace app\admin\model\test\product;

use think\Model;

/**
 * Attrs Attrs Model（CRUD 生成）
 */
class Attrs extends Model
{
    /** @var string 数据表名称 */
    protected $name = 'test_product_attrs';

    /** @var string 主键 */
    protected $pk = 'key';

    /** @var list<string>|true|false */
    protected array|bool $autoWriteTimestamp = false;

    /** @var array<string, string> */
    protected array $type = [

    ];
}
