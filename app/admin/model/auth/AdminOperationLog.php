<?php
declare (strict_types = 1);

namespace app\admin\model\auth;

use think\Model;
use think\model\relation\BelongsTo;

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
    protected $name = 'auth_admin_operation_log';

    /** @var bool|array<string>|string 自动时间戳 */
    protected $autoWriteTimestamp = 'datetime';

    /** @var string 与 datetime 列一致（全局 datetime_format=false 时须显式指定） */
    protected string $dateFormat = 'Y-m-d H:i:s';

    /** @var string 创建时间字段 */
    protected $createTime = 'create_time';

    /** @var bool|string 更新时间字段（无） */
    protected $updateTime = false;

    /** @var array<string, string> */
    protected $type = [
        'id'       => 'integer',
        'admin_id' => 'integer',
    ];

    /**
     * admin 关联操作人
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }
}
