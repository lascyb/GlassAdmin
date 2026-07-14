<?php
declare (strict_types = 1);

namespace app\admin\model\demo;

use app\admin\model\example\Category;
use app\admin\model\example\Tag;
use think\Model;
use think\facade\Db;
use think\model\relation\BelongsTo;
use think\model\relation\BelongsToMany;

/**
 * AllType 全字段类型 Model（CRUD 生成）
 */
class AllType extends Model
{
    /** @var string 数据表名称 */
    protected $name = 'demo_all_type';

    /** @var list<string>|true */
    protected array|bool $autoWriteTimestamp = true;

    /** @var array<string, string> */
    protected array $type = [
        'id' => 'integer',
        'category_id' => 'integer',
        'amount' => 'float',
        'quantity' => 'integer',
        'is_top' => 'integer',
        'status' => 'integer',
        'level' => 'integer',
        'publish_time' => 'integer',
        'extra' => 'json',
        'sort' => 'integer',
        'create_time' => 'integer',
        'update_time' => 'integer',
    ];

    /** category belongs_to 分类 */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /** tags belongs_to_many 标签 */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'demo_all_type_tag', 'all_type_id', 'tag_id');
    }

    /**
     * syncTags 同步标签关联
     *
     * @param list<int> $ids
     */
    public static function syncTags(int $ownerId, array $ids): void
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn (int $id): bool => $id > 0)));
        Db::name('demo_all_type_tag')->where('all_type_id', $ownerId)->delete();
        if ($ids === []) {
            return;
        }
        $rows = [];
        foreach ($ids as $rid) {
            $rows[] = ['all_type_id' => $ownerId, 'tag_id' => $rid];
        }
        Db::name('demo_all_type_tag')->insertAll($rows);
    }

    /**
     * tagIdsFor 读取已绑定标签 id
     *
     * @return list<int>
     */
    public static function tagIdsFor(int $ownerId): array
    {
        return array_map('intval', Db::name('demo_all_type_tag')->where('all_type_id', $ownerId)->column('tag_id'));
    }
}
