<?php
declare (strict_types = 1);

namespace app\admin\model\test\z;

use think\Model;

/**
 * Probe SchemaProbe Model（CRUD 生成）
 */
class Probe extends Model
{
    /** @var string 数据表名称 */
    protected $name = 'z_crud_schema_probe';

    /** @var list<string>|true|false */
    protected array|bool $autoWriteTimestamp = false;

    /** @var array<string, string> */
    protected array $type = [
        'id' => 'integer',
    ];
}
