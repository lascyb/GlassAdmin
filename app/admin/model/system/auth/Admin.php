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
 * @property int               $id
 * @property string            $username
 * @property string            $password
 * @property string|null       $nickname
 * @property string|null       $avatar 读取时为完整 URL；库内为相对 public/storage 路径，如 avatar/xxx.jpg
 * @property int               $status  {1:正常,0:禁用}
 * @property list<string>|null $login_methods NULL=跟随系统默认；否则为允许的登录方式
 * @property int|null          $last_login_time 上次登录时间（Unix 秒）
 */
class Admin extends Model
{
    /** 密码登录 */
    public const string METHOD_PASSWORD = 'password';

    /** 飞书登录 */
    public const string METHOD_FEISHU = 'feishu';

    /** 系统配置：账号未单独配置时的默认登录方式 */
    public const string CONFIG_KEY_DEFAULT_LOGIN_METHODS = 'admin_default_login_methods';

    /** @var list<string> 已知登录方式 */
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
     * normalizeLoginMethods 规范化登录方式集合（只保留已知项、去重；允许空数组）
     *
     * @return list<string>
     */
    public static function normalizeLoginMethods(mixed $raw): array
    {
        if ($raw === null || $raw === '') {
            return [];
        }
        if (is_string($raw)) {
            $trimmed = trim($raw);
            if ($trimmed === '') {
                return [];
            }
            if (str_starts_with($trimmed, '[')) {
                $decoded = json_decode($trimmed, true);
                $raw = is_array($decoded) ? $decoded : [];
            } else {
                $raw = preg_split('/[;；,]/', $trimmed) ?: [];
            }
        }
        if (!is_array($raw)) {
            return [];
        }

        $known = array_fill_keys(self::KNOWN_LOGIN_METHODS, true);
        $out = [];
        foreach ($raw as $item) {
            $method = is_string($item) ? trim($item) : '';
            if ($method === '' || !isset($known[$method]) || isset($out[$method])) {
                continue;
            }
            $out[$method] = true;
        }

        return array_keys($out);
    }

    /**
     * defaultLoginMethods 系统默认登录方式（配置非法/空 → []，不回退）
     *
     * @return list<string>
     */
    public static function defaultLoginMethods(): array
    {
        $raw = SystemConfigService::getConfig(self::CONFIG_KEY_DEFAULT_LOGIN_METHODS, null);

        return self::normalizeLoginMethods($raw);
    }

    /**
     * encodeLoginMethodsForDb 写入库值：null 表示跟随默认；非 null 须规范化后非空
     *
     * @param mixed $raw 请求原值；null 表示跟随默认
     * @return list<string>|null
     * @throws InvalidArgumentException
     */
    public static function encodeLoginMethodsForDb(mixed $raw): ?array
    {
        if ($raw === null) {
            return null;
        }
        $methods = self::normalizeLoginMethods($raw);
        if ($methods === []) {
            throw new InvalidArgumentException('登录方式不能为空');
        }

        return $methods;
    }

    /**
     * resolveEffectiveLoginMethods 解析生效集合（账号非 null 用账号，否则用系统默认）
     *
     * @param mixed $stored 库内 login_methods（null/array/string）
     * @return list<string>
     */
    public static function resolveEffectiveLoginMethods(mixed $stored): array
    {
        if ($stored === null) {
            return self::defaultLoginMethods();
        }

        return self::normalizeLoginMethods($stored);
    }

    /**
     * effectiveLoginMethods 当前账号生效的登录方式
     *
     * @return list<string>
     */
    public function effectiveLoginMethods(): array
    {
        return self::resolveEffectiveLoginMethods($this->getAttr('login_methods'));
    }

    /**
     * allowsLoginMethod 是否允许指定登录方式
     */
    public function allowsLoginMethod(string $method): bool
    {
        return in_array($method, $this->effectiveLoginMethods(), true);
    }

    /**
     * assertAllowsLoginMethod 断言允许指定登录方式；失败抛 InvalidArgumentException
     *
     * @throws InvalidArgumentException
     */
    public function assertAllowsLoginMethod(string $method): void
    {
        if ($this->allowsLoginMethod($method)) {
            return;
        }
        $label = match ($method) {
            self::METHOD_PASSWORD => '密码登录',
            self::METHOD_FEISHU => '飞书登录',
            default => '该登录方式',
        };

        throw new InvalidArgumentException('未开通' . $label);
    }

    /**
     * loginMethodsPayload 接口附加字段：库值 + 生效集合
     *
     * @return array{login_methods: list<string>|null, login_methods_effective: list<string>}
     */
    public function loginMethodsPayload(): array
    {
        $stored = $this->getAttr('login_methods');
        $normalizedStored = $stored === null ? null : self::normalizeLoginMethods($stored);

        return [
            'login_methods'            => $normalizedStored,
            'login_methods_effective'  => self::resolveEffectiveLoginMethods($stored),
        ];
    }
}
