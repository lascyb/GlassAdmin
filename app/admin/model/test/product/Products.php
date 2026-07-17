<?php
declare (strict_types = 1);

namespace app\admin\model\test\product;

use think\Model;
use think\model\relation\BelongsTo;

/**
 * Products Products Model（CRUD 生成）
 */
class Products extends Model
{
    /** @var string 数据表名称 */
    protected $name = 'test_product_products';

    /** @var list<string>|true|false */
    protected array|bool $autoWriteTimestamp = false;

    /** @var array<string, string> */
    protected array $type = [
        'id' => 'integer',
        'category_id' => 'integer',
    ];

    /** category belongs_to 类型 */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
}
