<?php
declare (strict_types = 1);

namespace app\admin\model\test\ft;

use think\Model;
use think\model\relation\BelongsTo;

/**
 * RemoteSelect 远程下拉 Model（CRUD 生成）
 */
class RemoteSelect extends Model
{
    /** @var string 数据表名称 */
    protected $name = 'test_ft_remote_select';

    /** @var list<string>|true|false */
    protected array|bool $autoWriteTimestamp = false;

    /** @var array<string, string> */
    protected array $type = [
        'id' => 'integer',
        'category_id' => 'integer',
    ];

    /** category belongs_to 分类 */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Text::class, 'category_id');
    }
}
