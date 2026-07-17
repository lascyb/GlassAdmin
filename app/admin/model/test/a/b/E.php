<?php
declare (strict_types = 1);

namespace app\admin\model\test\a\b;

use think\Model;

/**
 * E E Model（CRUD 生成）
 */
class E extends Model
{
    /** @var string 数据表名称 */
    protected $name = 'test_a_b_e';

    /** @var list<string>|true */
    protected array|bool $autoWriteTimestamp = true;

    /** @var array<string, string> */
    protected array $type = [
        'id' => 'integer',
        'age' => 'integer',
    ];
}
