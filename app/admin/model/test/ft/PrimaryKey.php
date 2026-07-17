<?php
declare (strict_types = 1);

namespace app\admin\model\test\ft;

use think\Model;

/**
 * PrimaryKey 自增主键 Model（CRUD 生成）
 */
class PrimaryKey extends Model
{
    /** @var string 数据表名称 */
    protected $name = 'test_ft_primary_key';

    /** @var list<string>|true|false */
    protected array|bool $autoWriteTimestamp = false;

    /** @var array<string, string> */
    protected array $type = [
        'id' => 'integer',
    ];
}
