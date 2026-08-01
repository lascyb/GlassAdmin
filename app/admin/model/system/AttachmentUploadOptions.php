<?php
declare (strict_types = 1);

namespace app\admin\model\system;

use app\admin\service\SystemConfigService;
use InvalidArgumentException;

/**
 * AttachmentUploadOptions Attachment::upload 参数（disk、api 必填）
 * 新业务通过新增静态快捷方法定义规则；api 为上传接口路径，写入附件表 scene 字段
 */
final readonly class AttachmentUploadOptions
{
    /** 系统配置：默认上传大小上限（字节） */
    public const string CONFIG_MAX_BYTES = 'upload_max_bytes';

    /** 系统配置：默认允许扩展名（逗号分隔或 JSON 数组） */
    public const string CONFIG_ALLOWED_EXT = 'upload_allowed_ext';

    /** 系统配置：默认允许 MIME（逗号分隔或 JSON 数组；空串表示不校验） */
    public const string CONFIG_ALLOWED_MIME = 'upload_allowed_mime';

    /** 兜底：最大 10MB */
    private const int FALLBACK_MAX_BYTES = 10 * 1024 * 1024;

    /** @var list<string> 兜底扩展名 */
    private const array FALLBACK_ALLOWED_EXT = [
        'jpg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'zip', 'txt',
    ];

    /**
     * @param string            $disk        存储磁盘名（必填，如 public）
     * @param string            $api         上传接口路径（必填，如 account.Profile/uploadAvatar）
     * @param int|null          $adminId     上传人 auth_admin.id
     * @param string|null       $dir         盘根下子目录
     * @param int               $maxBytes    最大字节
     * @param list<string>      $allowedExt  允许扩展名，[] 表示不校验扩展名
     * @param list<string>|null $allowedMime 允许 MIME，null/[] 表示不校验 MIME
     */
    public function __construct(
        public string $disk,
        public string $api,
        public ?int $adminId = null,
        public ?string $dir = null,
        public int $maxBytes = self::FALLBACK_MAX_BYTES,
        public array $allowedExt = [],
        public ?array $allowedMime = null,
    ) {
        if (trim($this->disk) === '') {
            throw new InvalidArgumentException('disk 必填');
        }
        if (trim($this->api) === '' || preg_match('#^[a-zA-Z0-9_./-]+$#', $this->api) !== 1) {
            throw new InvalidArgumentException('api 无效');
        }
    }

    /**
     * avatar 头像上传（2MB，jpg/png/gif/webp；默认目录 avatar）
     */
    public static function avatar(string $disk, string $api, ?int $adminId = null, ?string $dir = null): self
    {
        return new self(
            disk: $disk,
            api: $api,
            adminId: $adminId,
            dir: $dir ?? 'avatar',
            maxBytes: 2 * 1024 * 1024,
            allowedExt: ['jpg', 'png', 'gif', 'webp'],
            allowedMime: ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
        );
    }

    /**
     * image 图片上传（5MB，jpg/png/gif/webp；默认目录 image）
     */
    public static function image(string $disk, string $api, ?int $adminId = null, ?string $dir = null): self
    {
        return new self(
            disk: $disk,
            api: $api,
            adminId: $adminId,
            dir: $dir ?? 'image',
            maxBytes: 5 * 1024 * 1024,
            allowedExt: ['jpg', 'png', 'gif', 'webp'],
            allowedMime: ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
        );
    }

    /**
     * default 通用文件上传（优先读系统配置；默认目录 default）
     */
    public static function default(string $disk, string $api, ?int $adminId = null, ?string $dir = null): self
    {
        $limits = self::resolveDefaultLimits();

        return new self(
            disk: $disk,
            api: $api,
            adminId: $adminId,
            dir: $dir ?? 'default',
            maxBytes: $limits['max_bytes'],
            allowedExt: $limits['allowed_ext'],
            allowedMime: $limits['allowed_mime'],
        );
    }

    /**
     * resolveDefaultLimits 从系统配置读取通用上传限制，无效/缺失时用代码兜底
     *
     * @return array{max_bytes:int, allowed_ext:list<string>, allowed_mime:?list<string>}
     */
    private static function resolveDefaultLimits(): array
    {
        $cfg = SystemConfigService::getConfigs([
            self::CONFIG_MAX_BYTES,
            self::CONFIG_ALLOWED_EXT,
            self::CONFIG_ALLOWED_MIME,
        ]);

        $max = (int) ($cfg[self::CONFIG_MAX_BYTES] ?? 0);
        if ($max <= 0) {
            $max = self::FALLBACK_MAX_BYTES;
        }

        $ext = self::parseList((string) ($cfg[self::CONFIG_ALLOWED_EXT] ?? ''));
        if ($ext === []) {
            $ext = self::FALLBACK_ALLOWED_EXT;
        }

        $mime = null;
        if (array_key_exists(self::CONFIG_ALLOWED_MIME, $cfg)) {
            $mimeRaw = trim($cfg[self::CONFIG_ALLOWED_MIME]);
            $mime = $mimeRaw === '' ? null : self::parseList($mimeRaw);
            if ($mime === []) {
                $mime = null;
            }
        }

        return [
            'max_bytes'    => $max,
            'allowed_ext'  => $ext,
            'allowed_mime' => $mime,
        ];
    }

    /**
     * parseList 解析逗号分隔或 JSON 数组配置
     *
     * @return list<string>
     */
    private static function parseList(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return [];
        }
        if (str_starts_with($raw, '[')) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $items = [];
                foreach ($decoded as $v) {
                    $s = strtolower(trim((string) $v));
                    if ($s !== '') {
                        $items[] = $s;
                    }
                }

                return array_values(array_unique($items));
            }
        }
        $items = [];
        foreach (explode(',', $raw) as $part) {
            $s = strtolower(trim($part));
            if ($s !== '') {
                $items[] = $s;
            }
        }

        return array_values(array_unique($items));
    }
}
