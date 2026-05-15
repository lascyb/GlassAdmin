<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

/**
 * AdminNotification 管理员站内通知
 *
 * @property int         $id
 * @property int         $admin_id
 * @property string      $category {system:系统,friend:好友}
 * @property string      $title
 * @property string|null $content
 * @property string|null $read_at
 * @property string|null $create_time
 */
class AdminNotification extends Model
{
    /** @var string 表名 */
    protected $name = 'admin_notification';

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
