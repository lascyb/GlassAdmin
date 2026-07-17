<?php
declare (strict_types = 1);

namespace app\admin\model\test\ft;

use think\Model;

/**
 * MultiSelect 下拉多选 Model（CRUD 生成）
 */
class MultiSelect extends Model
{
    /** @var string 数据表名称 */
    protected $name = 'test_ft_multi_select';

    /** @var list<string>|true|false */
    protected array|bool $autoWriteTimestamp = false;

    /** @var array<string, string> */
    protected array $type = [
        'id' => 'integer',
        'extra' => 'json',
    ];
}
