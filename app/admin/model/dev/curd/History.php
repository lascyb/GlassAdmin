<?php
declare (strict_types = 1);

namespace app\admin\model\dev\curd;

use think\Model;

/**
 * History CRUD 生成历史
 *
 * 表结构对齐 glass_admin.sql：dev_crud_history(id,name,config,fields,create_time)
 */
class History extends Model
{
    protected $name = 'dev_crud_history';

    protected $autoWriteTimestamp = false;
}
