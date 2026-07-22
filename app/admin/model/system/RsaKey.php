<?php
declare (strict_types = 1);

namespace app\admin\model\system;

use think\Model;

class RsaKey extends Model
{
    protected $name = 'system_rsa_key';

    protected bool $autoWriteTimestamp = true;

    protected array $type = [
        "create_time" => "int",
        "update_time" => "int",
    ];
}