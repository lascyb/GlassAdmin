<?php
declare (strict_types = 1);

namespace app\admin\model\test\ft;

use think\Model;

/**
 * Textarea 多行文本 Model（CRUD 生成）
 */
class Textarea extends Model
{
    /** @var string 数据表名称 */
    protected $name = 'test_ft_textarea';

    /** @var list<string>|true|false */
    protected array|bool $autoWriteTimestamp = false;

    /** @var array<string, string> */
    protected array $type = [
        'id' => 'integer',
    ];
}
