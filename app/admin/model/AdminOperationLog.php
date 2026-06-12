<?php
declare (strict_types = 1);

namespace app\admin\model;

use think\Model;

/**
 * AdminOperationLog 管理员操作日志
 *
 * @property int         $id
 * @property int         $admin_id
 * @property string      $action
 * @property string      $summary
 * @property string|null $ip
 * @property string|null $create_time
 */
class AdminOperationLog extends Model
{
    /** @var string 表名 */
    protected $name = 'admin_operation_log';

    /** @var bool|array<string>|string 自动时间戳 */
    protected $autoWriteTimestamp = 'datetime';

    /** @var string 创建时间字段 */
    protected $createTime = 'create_time';

    /** @var bool|string 更新时间字段（无） */
    protected $updateTime = false;

    /** @var array<string, string> */
    protected $type = [
        'id'       => 'integer',
        'admin_id' => 'integer',
    ];
}
