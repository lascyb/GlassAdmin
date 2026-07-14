<?php
declare (strict_types = 1);

namespace app\admin\model\demo;

use app\admin\model\example\Category;
use think\Model;
use think\model\relation\BelongsTo;

/**
 * Item 演示条目 Model（CRUD 生成）
 */
class Item extends Model
{
    /** @var string 数据表名称 */
    protected $name = 'demo_item';

    /** @var list<string>|true */
    protected array|bool $autoWriteTimestamp = true;

    /** @var array<string, string> */
    protected array $type = [
        'id' => 'integer',
        'category_id' => 'integer',
        'quantity' => 'integer',
        'is_top' => 'integer',
        'status' => 'integer',
        'publish_time' => 'integer',
        'sort' => 'integer',
        'create_time' => 'integer',
        'update_time' => 'integer',
    ];

    /** category belongs_to 分类 */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
}
