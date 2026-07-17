<?php
declare (strict_types = 1);

namespace app\admin\model\test\ft;

use think\Model;
use think\facade\Db;
use think\model\relation\BelongsToMany;

/**
 * RemoteMultiSelect 远程多选 Model（CRUD 生成）
 */
class RemoteMultiSelect extends Model
{
    /** @var string 数据表名称 */
    protected $name = 'test_ft_remote_multi_select';

    /** @var list<string>|true|false */
    protected array|bool $autoWriteTimestamp = false;

    /** @var array<string, string> */
    protected array $type = [
        'id' => 'integer',
    ];

    /** tags belongs_to_many 标签 */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Text::class, 'test_ft_remote_multi_select_tag', 'remote_multi_select_id', 'tag_id');
    }

    /**
     * syncTags 同步标签关联
     *
     * @param list<int> $ids
     */
    public static function syncTags(int $ownerId, array $ids): void
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn (int $id): bool => $id > 0)));
        Db::name('test_ft_remote_multi_select_tag')->where('remote_multi_select_id', $ownerId)->delete();
        if ($ids === []) {
            return;
        }
        $rows = [];
        foreach ($ids as $rid) {
            $rows[] = ['remote_multi_select_id' => $ownerId, 'tag_id' => $rid];
        }
        Db::name('test_ft_remote_multi_select_tag')->insertAll($rows);
    }

    /**
     * tagIdsFor 读取已绑定标签 id
     *
     * @return list<int>
     */
    public static function tagIdsFor(int $ownerId): array
    {
        return array_map('intval', Db::name('test_ft_remote_multi_select_tag')->where('remote_multi_select_id', $ownerId)->column('tag_id'));
    }
}
