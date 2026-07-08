<?php
declare (strict_types = 1);

namespace app\admin\model;

use think\Model;

/**
 * AdminRole 管理员与角色关联（admin_role）
 */
class AdminRole extends Model
{
    /** @var string 表名 */
    protected $name = 'admin_role';
}
