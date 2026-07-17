<?php
declare (strict_types = 1);

namespace app\admin\model\test\ft;

use think\Model;

/**
 * Datetime 日期时间 Model（CRUD 生成）
 */
class Datetime extends Model
{
    /** @var string 数据表名称 */
    protected $name = 'test_ft_datetime';

    /** @var list<string>|true|false */
    protected array|bool $autoWriteTimestamp = false;

    /** @var array<string, string> */
    protected array $type = [
        'id' => 'integer',
        'publish_time' => 'integer',
    ];
}
