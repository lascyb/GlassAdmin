<?php
declare (strict_types = 1);

namespace app\admin\model\test\ft;

use think\Model;

/**
 * Select 下拉单选 Model（CRUD 生成）
 */
class Select extends Model
{
    /** @var string 数据表名称 */
    protected $name = 'test_ft_select';

    /** @var list<string>|true|false */
    protected array|bool $autoWriteTimestamp = false;

    /** @var array<string, string> */
    protected array $type = [
        'id' => 'integer',
        'level' => 'integer',
    ];
}
