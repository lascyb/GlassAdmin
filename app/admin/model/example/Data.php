<?php
declare (strict_types = 1);

namespace app\admin\model\example;

use think\facade\Db;
use think\Model;
use think\model\relation\BelongsTo;
use think\model\relation\BelongsToMany;

/**
 * Data 示例数据（CRUD 全字段类型模板实体）
 *
 * @property int|null         $category_id
 * @property string|null      $content
 * @property float            $amount
 * @property int              $quantity
 * @property int              $is_top    {1:是,0:否}
 * @property int              $level     {1:普通,2:重要,3:紧急}
 * @property string|null      $color
 * @property string|null      $cover
 * @property int|null         $publish_time
 * @property string|null      $expire_date
 * @property list<string>|null $extra
 */
class Data extends Model
{
    /** @var string 数据表名称 */
    protected $name = 'example_data';

    /** @var list<string>|true */
    protected array|bool $autoWriteTimestamp = true;

    /** @var array<string, string> */
    protected array $type = [
        'id'           => 'integer',
        'category_id'  => 'integer',
        'amount'       => 'float',
        'quantity'     => 'integer',
        'is_top'       => 'integer',
        'status'       => 'integer',
        'level'        => 'integer',
        'publish_time' => 'integer',
        'sort'         => 'integer',
        'extra'        => 'json',
        'create_time'  => 'integer',
        'update_time'  => 'integer',
    ];

    /** category belongs_to 分类 */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /** tags belongs_to_many 标签 */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'example_data_tag', 'data_id', 'tag_id');
    }

    /**
     * syncTags 同步标签关联
     *
     * @param list<int> $tagIds
     */
    public static function syncTags(int $dataId, array $tagIds): void
    {
        $tagIds = array_values(array_unique(array_filter(array_map('intval', $tagIds), static fn (int $id): bool => $id > 0)));
        Db::name('example_data_tag')->where('data_id', $dataId)->delete();
        if ($tagIds === []) {
            return;
        }
        $rows = [];
        foreach ($tagIds as $tagId) {
            $rows[] = ['data_id' => $dataId, 'tag_id' => $tagId];
        }
        Db::name('example_data_tag')->insertAll($rows);
    }

    /**
     * tagIdsFor 读取已绑定标签 id
     *
     * @return list<int>
     */
    public static function tagIdsFor(int $dataId): array
    {
        return array_map('intval', Db::name('example_data_tag')->where('data_id', $dataId)->column('tag_id'));
    }
}
