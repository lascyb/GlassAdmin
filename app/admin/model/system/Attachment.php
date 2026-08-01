<?php
declare (strict_types = 1);

namespace app\admin\model\system;

use app\admin\model\auth\Admin;
use InvalidArgumentException;
use Random\RandomException;
use RuntimeException;
use think\exception\FileException;
use think\facade\Config;
use think\file\UploadedFile;
use think\Model;
use think\model\relation\BelongsTo;

/**
 * Attachment 系统附件元数据；统一上传落盘逻辑供各业务接口调用
 *
 * @property int         $id
 * @property string      $name        原始文件名
 * @property string      $path        相对存储盘根路径
 * @property string      $disk        存储磁盘名
 * @property string|null $mime        MIME 类型
 * @property string|null $ext         扩展名
 * @property int         $size        字节大小
 * @property string      $scene       上传接口路径（如 account.Profile/uploadAvatar）
 * @property string|null $sha1        文件哈希
 * @property int|null    $admin_id     上传人 auth_admin.id
 * @property int         $file_deleted {0:文件在盘,1:物理文件已删除}
 * @property int|null    $create_time
 * @property int|null    $update_time
 * @property string|null $url          访问 URL（追加字段；文件已删时为 null）
 */
class Attachment extends Model
{
    /** @var string 表名 */
    protected $name = 'system_attachment';

    /** @var list<string>|true */
    protected array|bool $autoWriteTimestamp = true;

    /** @var list<string> 追加访问 URL */
    protected array $append = ['url'];

    /** @var array<string, string> */
    protected $type = [
        'id'           => 'integer',
        'size'         => 'integer',
        'admin_id'     => 'integer',
        'file_deleted' => 'integer',
        'create_time'  => 'integer',
        'update_time'  => 'integer',
    ];

    /** 默认磁盘（仅 URL/删除回退用） */
    private const string DISK_DEFAULT = 'public';

    /** 物理文件在盘 */
    public const int FILE_PRESENT = 0;

    /** 物理文件已删除 */
    public const int FILE_DELETED = 1;

    /**
     * admin 上传人
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    /**
     * upload 校验并落盘，写入附件表（各业务接口直接调用，不经统一 HTTP）
     *
     * @throws InvalidArgumentException 校验失败
     * @throws RuntimeException|RandomException         落盘或写库失败
     */
    public static function upload(UploadedFile $file, AttachmentUploadOptions $options): self
    {
        if (!$file->isValid()) {
            throw new InvalidArgumentException('文件无效或过大');
        }

        $api = trim($options->api);
        $disk = trim($options->disk);
        $dirRel = trim(str_replace('\\', '/', (string) ($options->dir ?? '')), '/');
        if ($dirRel === '' || str_contains($dirRel, '..') || preg_match('#^[a-zA-Z0-9_/-]+$#', $dirRel) !== 1) {
            throw new InvalidArgumentException('存储目录无效');
        }
        $maxBytes = $options->maxBytes;
        /** @var list<string> $allowedExt */
        $allowedExt = array_values($options->allowedExt);
        $allowedMime = $options->allowedMime;

        $size = (int) $file->getSize();
        if ($size <= 0 || $size > $maxBytes) {
            throw new InvalidArgumentException('文件大小超出限制');
        }

        $ext = strtolower($file->extension());
        if ($ext === 'jpeg') {
            $ext = 'jpg';
        }
        if ($allowedExt !== [] && !in_array($ext, $allowedExt, true)) {
            throw new InvalidArgumentException('不支持的文件扩展名');
        }

        $mime = $file->getMime();
        if (is_array($allowedMime) && $allowedMime !== [] && !in_array($mime, $allowedMime, true)) {
            throw new InvalidArgumentException('文件类型与扩展名不符');
        }

        $root = rtrim((string) Config::get("filesystem.disks.{$disk}.root", ''), DIRECTORY_SEPARATOR);
        if ($root === '') {
            throw new RuntimeException('存储磁盘未配置');
        }
        $absDir = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $dirRel);
        $baseName = bin2hex(random_bytes(16)) . '.' . $ext;

        $pathname = $file->getPathname();
        $sha1 = is_file($pathname) ? (string) sha1_file($pathname) : null;
        $originalName = $file->getOriginalName();

        try {
            $saved = $file->move($absDir, $baseName);
        } catch (FileException) {
            throw new RuntimeException('保存失败');
        }

        $filename = $saved->getFilename();
        $path = $dirRel . '/' . $filename;
        $name = $originalName !== '' ? $originalName : $filename;
        $adminId = $options->adminId !== null && $options->adminId > 0 ? $options->adminId : null;

        $row = new self();
        try {
            $row->save([
                'name'         => mb_substr($name, 0, 255),
                'path'         => $path,
                'disk'         => $disk,
                'mime'         => $mime !== '' ? mb_substr($mime, 0, 128) : null,
                'ext'          => $ext,
                'size'         => $size,
                'scene'        => mb_substr($api, 0, 128),
                'sha1'         => $sha1 !== '' ? $sha1 : null,
                'admin_id'     => $adminId,
                'file_deleted' => self::FILE_PRESENT,
            ]);
        } catch (\Throwable $e) {
            $full = $absDir . DIRECTORY_SEPARATOR . $filename;
            if (is_file($full)) {
                @unlink($full);
            }
            throw new RuntimeException('附件记录写入失败', 0, $e);
        }

        return $row;
    }

    /**
     * deleteById 删除附件记录并尝试删除磁盘文件
     *
     * @return string|null 失败原因，成功返回 null
     */
    public static function deleteById(int $id): ?string
    {
        if ($id <= 0) {
            return 'id 无效';
        }
        $row = self::find($id);
        if (!$row) {
            return '不存在';
        }
        $disk = (string) $row->getAttr('disk');
        $path = (string) $row->getAttr('path');
        self::deleteStoredFile($disk !== '' ? $disk : self::DISK_DEFAULT, $path);
        $row->delete();

        return null;
    }

    /**
     * deleteByPath 删除物理文件并保留库记录，标记 file_deleted=1
     */
    public static function deleteByPath(string $path, string $disk = self::DISK_DEFAULT): void
    {
        $rel = self::normalizeStoragePath($path);
        if ($rel === '') {
            return;
        }
        $disk = $disk !== '' ? $disk : self::DISK_DEFAULT;
        self::deleteStoredFile($disk, $rel);
        self::where('disk', $disk)
            ->where('path', $rel)
            ->where('file_deleted', self::FILE_PRESENT)
            ->update([
                'file_deleted' => self::FILE_DELETED,
                'update_time'  => time(),
            ]);
    }

    /**
     * normalizeStoragePath 规范为相对盘根路径
     */
    public static function normalizeStoragePath(string $path): string
    {
        $rel = str_replace('\\', '/', trim($path));
        if (preg_match('#^https?://#i', $rel)) {
            $parsed = parse_url($rel);
            $rel = ltrim((string) ($parsed['path'] ?? ''), '/');
        } else {
            $rel = ltrim($rel, '/');
        }
        if (str_starts_with($rel, 'storage/')) {
            $rel = substr($rel, strlen('storage/'));
        }

        return $rel;
    }

    /**
     * deleteStoredFile 删除存储盘上的相对路径文件（拒绝路径穿越）
     */
    public static function deleteStoredFile(string $disk, string $path): void
    {
        $rel = self::normalizeStoragePath($path);
        if ($rel === '' || str_contains($rel, '..')) {
            return;
        }
        $root = rtrim((string) Config::get("filesystem.disks.{$disk}.root", ''), DIRECTORY_SEPARATOR);
        if ($root === '') {
            return;
        }
        $full = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
        $realRoot = realpath($root);
        $realFile = realpath($full);
        if ($realRoot === false || $realFile === false) {
            return;
        }
        if (!str_starts_with($realFile, $realRoot . DIRECTORY_SEPARATOR) && $realFile !== $realRoot) {
            return;
        }
        if (is_file($realFile)) {
            @unlink($realFile);
        }
    }

    /**
     * getUrlAttr 由 path/disk 生成可访问 URL
     *
     * @param mixed               $value
     * @param array<string,mixed> $data
     */
    public function getUrlAttr(mixed $value, array $data): ?string
    {
        if ((int) ($data['file_deleted'] ?? self::FILE_PRESENT) === self::FILE_DELETED) {
            return null;
        }

        return self::publicUrl(
            (string) ($data['path'] ?? ''),
            (string) ($data['disk'] ?? self::DISK_DEFAULT),
        );
    }

    /**
     * publicUrl 由相对路径生成可访问 URL
     */
    public static function publicUrl(?string $path, string $disk = self::DISK_DEFAULT): ?string
    {
        if ($path === null) {
            return null;
        }
        $path = trim($path);
        if ($path === '') {
            return null;
        }
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }
        $rel = self::normalizeStoragePath($path);
        if ($rel === '') {
            return null;
        }
        $urlBase = rtrim((string) Config::get("filesystem.disks.{$disk}.url", '/storage'), '/');
        $pathOnly = $urlBase . '/' . $rel;
        $host = rtrim((string) env('APP_URL', ''), '/');
        if ($host !== '') {
            return $host . $pathOnly;
        }
        try {
            $domain = rtrim(request()->domain(), '/');
            if ($domain !== '') {
                return $domain . $pathOnly;
            }
        } catch (\Throwable) {
        }

        return $pathOnly;
    }
}
