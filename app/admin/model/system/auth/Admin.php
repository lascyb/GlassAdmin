<?php
declare (strict_types = 1);

namespace app\admin\model\system\auth;

use app\admin\model\system\Attachment;
use app\admin\service\SystemConfigService;
use InvalidArgumentException;
use think\Model;
use think\model\relation\BelongsToMany;

/**
 * Admin 后台管理员
 *
 * 登录方式：`login_methods` 为 NULL 时跟随系统配置 `admin_default_login_methods`；
 * 非 NULL 为账号覆盖（可为 []，表示显式不开通任何方式）。登录校验一律看生效集合，
 * 空或不含对应方式则拒绝（配置异常不回退双开）。飞书用户多由 OAuth JIT 创建并显式写入 ["feishu"]。
 *
 * @property int               $id
 * @property string            $username
 * @property string            $password
 * @property string|null       $nickname
 * @property string|null       $avatar 读取时为完整 URL；库内为相对 public/storage 路径，如 avatar/xxx.jpg
 * @property int               $status  {1:正常,0:禁用}
 * @property list<string>|null $login_methods NULL=跟随系统默认；数组=账号覆盖（可 []）
 * @property int|null          $last_login_time 上次登录时间（Unix 秒）
 */
class Admin extends Model
{
    /** 密码登录方式标识 */
    public const string METHOD_PASSWORD = 'password';

    /** 飞书登录方式标识 */
    public const string METHOD_FEISHU = 'feishu';

    /** 系统配置 key：账号未单独配置时的默认登录方式（multi_select） */
    public const string CONFIG_KEY_DEFAULT_LOGIN_METHODS = 'admin_default_login_methods';

    /**
     * 已知登录方式（normalize 时只保留这些值）
     *
     * @var list<string>
     */
    public const array KNOWN_LOGIN_METHODS = [
        self::METHOD_PASSWORD,
        self::METHOD_FEISHU,
    ];

    /** @var string 数据表名称（无前缀时会自动拼接配置中的表前缀） */
    protected $name = 'system_auth_admin';

    /** @var list<string> 隐藏字段 JSON 序列化时排除 */
    protected array $hidden = ['password'];

    /** @var list<string>|true 字段类型自动转换（true 为自动写入时间戳字段） */
    protected array|bool $autoWriteTimestamp = true;

    /** @var array<string, string> */
    protected array $type = [
        'id'              => 'integer',
        'status'          => 'integer',
        'login_methods'   => 'json',
        'last_login_time' => 'integer',
        'create_time'     => 'integer',
        'update_time'     => 'integer',
    ];

    /**
     * roles 管理员绑定的角色（system_auth_admin_role）
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'system_auth_admin_role', 'role_id', 'admin_id');
    }

    /**
     * getAvatarAttr 读取时拼接可访问 URL
     *
     * @param string|null         $value 库内原始相对路径
     * @param array<string,mixed> $data
     */
    public function getAvatarAttr(?string $value, array $data): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Attachment::publicUrl($value);
    }

    /**
     * normalizeLoginMethods 规范化登录方式集合
     *
     * 非数组 → []；只保留 {@see KNOWN_LOGIN_METHODS}、去重保序；允许返回空数组（不偷偷补默认）。
     *
     * @param mixed $raw 请求体数组或库内 json 解码结果
     * @return list<string>
     */
    public static function normalizeLoginMethods(mixed $raw): array
    {
        if (!is_array($raw)) {
            return [];
        }
        $known = array_fill_keys(self::KNOWN_LOGIN_METHODS, true);
        $out = [];
        foreach ($raw as $item) {
            if (!is_string($item)) {
                continue;
            }
            $method = trim($item);
            if ($method === '' || !isset($known[$method]) || isset($out[$method])) {
                continue;
            }
            $out[$method] = true;
        }

        return array_keys($out);
    }

    /**
     * defaultLoginMethods 读取系统默认登录方式
     *
     * 来源：{@see CONFIG_KEY_DEFAULT_LOGIN_METHODS}；缺 key / 非法 / 过滤后为空 → []（登录侧将全部拒绝）。
     *
     * @return list<string>
     */
    public static function defaultLoginMethods(): array
    {
        return self::normalizeLoginMethods(
            SystemConfigService::getConfig(self::CONFIG_KEY_DEFAULT_LOGIN_METHODS, null),
        );
    }

    /**
     * effectiveLoginMethods 当前账号生效的登录方式
     *
     * `login_methods === null` → {@see defaultLoginMethods()}；否则 normalize 账号值（含 []）。
     *
     * @return list<string>
     */
    public function effectiveLoginMethods(): array
    {
        $stored = $this->getAttr('login_methods');
        if ($stored === null) {
            return self::defaultLoginMethods();
        }

        return self::normalizeLoginMethods($stored);
    }

    /**
     * assertAllowsLoginMethod 断言生效集合含指定方式
     *
     * 供密码 / 飞书登录、个人中心改密等调用；失败抛业务异常，由控制器转 403。
     *
     * @param string $method {@see METHOD_PASSWORD} / {@see METHOD_FEISHU}
     * @throws InvalidArgumentException 未开通或生效集合为空
     */
    public function assertAllowsLoginMethod(string $method): void
    {
        if (in_array($method, $this->effectiveLoginMethods(), true)) {
            return;
        }
        $label = match ($method) {
            self::METHOD_PASSWORD => '密码登录',
            self::METHOD_FEISHU => '飞书登录',
            default => '该登录方式',
        };

        throw new InvalidArgumentException('未开通' . $label);
    }
}
