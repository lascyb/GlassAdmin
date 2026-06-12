<?php
declare (strict_types = 1);

namespace app\admin\model;

use think\Model;

class RsaKey extends Model
{
    protected $name = 'rsa_key';

    protected bool $autoWriteTimestamp = true;

    protected array $type = [
        "create_time" => "int",
        "update_time" => "int",
    ];
}