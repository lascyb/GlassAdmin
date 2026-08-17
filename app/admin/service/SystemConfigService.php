<?php

declare(strict_types=1);

namespace app\admin\service;

use app\admin\model\system\Config;
use InvalidArgumentException;
use think\facade\Cache;
use think\facade\Db;
use think\Paginator;

/**
 * SystemConfigService 系统配置服务（typed 缓存 + 写操作编排）
 */
class SystemConfigService
{
    /** 缓存标签 */
    private const string CACHE_TAG = 'system_config';

    /** 单个配置缓存键前缀 */
    private const string CACHE_KEY_PREFIX = 'system_config:';

    /** 全量配置缓存键 */
    private const string CACHE_KEY_ALL = 'system_config:all';

    /** 缓存 TTL（秒），0 表示不过期 */
    private const int CACHE_TTL = 180;

    /** value 落库最大长度 */
    private const int VALUE_MAX_LENGTH = 2000;

    /**
     * castValueByType 按 type 将 DB 字符串转为 typed 值
     */
    public static function castValueByType(string $type, string $value): mixed
    {
        return match ($type) {
            'switch' => in_array(strtolower(trim($value)), ['1', 'true'], true),
            'number' => is_numeric($value) ? (int) round((float) $value) : 0,
            'multi_select' => self::decodeMultiSelect($value),
            default => $value,
        };
    }

    /**
     * normalizeValueByType 将 API typed 值规范为 DB 字符串
     */
    public static function normalizeValueByType(string $type, mixed $value): string
    {
        return match ($type) {
            'switch' => filter_var($value, FILTER_VALIDATE_BOOLEAN) ? '1' : '0',
            'number' => self::normalizeNumberValue($value),
            'multi_select' => self::normalizeMultiSelectValue($value),
            default => is_array($value)
                ? json_encode(array_values($value), JSON_UNESCAPED_UNICODE) ?: '[]'
                : (string) $value,
        };
    }

    /**
     * castRowForApi 管理端单行：value 转为 typed
     *
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    public static function castRowForApi(array $row): array
    {
        if (array_key_exists('value', $row) && array_key_exists('type', $row)) {
            $row['value'] = self::castValueByType((string) $row['type'], (string) $row['value']);
        }

        return $row;
    }

    /**
     * castRowsForApi 批量 cast 列表行
     *
     * @param list<array<string, mixed>> $rows
     * @return list<array<string, mixed>>
     */
    public static function castRowsForApi(array $rows): array
    {
        return array_map(static fn(array $row): array => self::castRowForApi($row), $rows);
    }

    /**
     * getConfig 获取单个配置值（typed，优先读缓存）
     */
    public static function getConfig(string $key, mixed $default = null): mixed
    {
        $cacheKey = self::CACHE_KEY_PREFIX . $key;
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $entry = Config::findEntryByKey($key);
        if ($entry === null) {
            return $default;
        }

        $typed = self::castValueByType($entry['type'], $entry['value']);
        Cache::tag(self::CACHE_TAG)->set($cacheKey, $typed, self::CACHE_TTL);

        return $typed;
    }

    /**
     * getConfigs 批量获取配置值（typed）
     *
     * @param list<string> $keys
     * @return array<string, mixed>
     */
    public static function getConfigs(array $keys): array
    {
        $result = [];
        $miss   = [];
        foreach ($keys as $key) {
            $cacheKey = self::CACHE_KEY_PREFIX . $key;
            if (Cache::has($cacheKey)) {
                $result[$key] = Cache::get($cacheKey);
            } else {
                $miss[] = $key;
            }
        }

        if ($miss !== []) {
            foreach (Config::findEntriesByKeys($miss) as $k => $entry) {
                $typed = self::castValueByType($entry['type'], $entry['value']);
                $result[$k] = $typed;
                Cache::tag(self::CACHE_TAG)->set(self::CACHE_KEY_PREFIX . $k, $typed, self::CACHE_TTL);
            }
        }

        return $result;
    }

    /**
     * getAllConfigs 获取全部配置（按 group 分组，typed）
     *
     * @return array<string, array<string, mixed>>
     */
    public static function getAllConfigs(): array
    {
        $cached = Cache::get(self::CACHE_KEY_ALL);
        if (is_array($cached)) {
            return $cached;
        }

        $result = [];
        foreach (Config::findActiveRows() as $row) {
            $group = (string) $row->getAttr('group');
            if (!isset($result[$group])) {
                $result[$group] = [];
            }
            $type = (string) $row->getAttr('type');
            $result[$group][(string) $row->getAttr('key')] = self::castValueByType(
                $type,
                (string) $row->getAttr('value'),
            );
        }
        Cache::tag(self::CACHE_TAG)->set(self::CACHE_KEY_ALL, $result, self::CACHE_TTL);

        return $result;
    }

    /**
     * getList 配置列表（分页 + 筛选）
     */
    public static function getList(int $page, int $limit, string $group = '', string $keyword = ''): Paginator
    {
        return Config::paginateList($page, $limit, $group, $keyword);
    }

    /**
     * getGroups 获取所有配置分组
     *
     * @return list<string>
     */
    public static function getGroups(): array
    {
        return Config::findGroups();
    }

    /**
     * saveConfig 保存配置（创建或更新，使用事务）
     *
     * @param array<string, mixed> $data
     */
    public static function saveConfig(array $data, ?int $id = null): int
    {
        if (array_key_exists('value', $data)) {
            $type = isset($data['type']) ? (string) $data['type'] : '';
            if ($type === '' && $id !== null && $id > 0) {
                $existing = Config::find($id);
                if ($existing) {
                    $type = (string) $existing->getAttr('type');
                }
            }
            if ($type === '') {
                $type = 'input';
            }
            $data['value'] = self::assertValueLength(
                self::normalizeValueByType($type, $data['value']),
            );
        }

        return Db::transaction(function () use ($data, $id) {
            if ($id !== null && $id > 0) {
                $row = Config::find($id);
                if (!$row) {
                    return 0;
                }
                $oldKey = (string) $row->getAttr('key');
                $row->save($data);
                self::forgetConfigCache($oldKey);
                if (isset($data['key']) && $data['key'] !== $oldKey) {
                    self::forgetConfigCache((string) $data['key']);
                }
            } else {
                $id = Config::createRow($data);
            }
            self::forgetAllConfigCache();

            return $id;
        });
    }

    /**
     * deleteConfig 删除配置（使用事务）
     */
    public static function deleteConfig(int $id): bool
    {
        return Db::transaction(function () use ($id) {
            $row = Config::find($id);
            if (!$row) {
                return false;
            }
            $key = (string) $row->getAttr('key');
            $row->delete();
            self::forgetConfigCache($key);
            self::forgetAllConfigCache();

            return true;
        });
    }

    /**
     * batchSave 批量保存配置（按 group，pairs 值为 typed）
     *
     * @param array<string, mixed> $pairs key => typed value
     */
    public static function batchSave(string $group, array $pairs): void
    {
        Db::transaction(function () use ($group, $pairs) {
            $keys = array_map('strval', array_keys($pairs));
            $types = [];
            foreach (Config::findEntriesByKeys($keys) as $k => $entry) {
                $types[$k] = $entry['type'];
            }

            foreach ($pairs as $key => $value) {
                $key = (string) $key;
                $type = $types[$key] ?? 'input';
                $raw = self::assertValueLength(self::normalizeValueByType($type, $value));
                Config::upsertValueByKey($key, $raw, $group);
                self::forgetConfigCache($key);
            }
            self::forgetAllConfigCache();
        });
    }

    /**
     * forgetConfigCache 清除指定配置缓存
     */
    public static function forgetConfigCache(string $key): void
    {
        if ($key !== '') {
            Cache::delete(self::CACHE_KEY_PREFIX . $key);
        }
    }

    /**
     * forgetAllConfigCache 清除全部配置缓存
     */
    public static function forgetAllConfigCache(): void
    {
        Cache::tag(self::CACHE_TAG)->clear();
    }

    /**
     * decodeMultiSelect 解析 multi_select DB 字符串
     *
     * @return list<string>
     */
    private static function decodeMultiSelect(string $value): array
    {
        $raw = trim($value);
        if ($raw === '') {
            return [];
        }
        if (str_starts_with($raw, '[')) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return array_values(array_map('strval', $decoded));
            }

            return [];
        }

        return array_values(array_filter(array_map('trim', preg_split('/[;；,]/', $raw) ?: [])));
    }

    /**
     * normalizeMultiSelectValue 规范 multi_select 为 JSON 数组字符串
     */
    private static function normalizeMultiSelectValue(mixed $value): string
    {
        if (!is_array($value)) {
            if (is_string($value) && str_starts_with(trim($value), '[')) {
                return self::normalizeMultiSelectValue(self::decodeMultiSelect($value));
            }

            throw new InvalidArgumentException('multi_select 值必须是数组');
        }

        return json_encode(array_values(array_map('strval', $value)), JSON_UNESCAPED_UNICODE) ?: '[]';
    }

    /**
     * normalizeNumberValue 规范 number 为数字字符串
     */
    private static function normalizeNumberValue(mixed $value): string
    {
        if (is_int($value) || is_float($value)) {
            if (!is_finite((float) $value)) {
                throw new InvalidArgumentException('number 值无效');
            }

            return (string) (int) round((float) $value);
        }
        if (is_string($value) && is_numeric($value)) {
            return (string) (int) round((float) $value);
        }

        throw new InvalidArgumentException('number 值必须是数字');
    }

    /**
     * assertValueLength 校验落库字符串长度
     */
    private static function assertValueLength(string $value): string
    {
        if (strlen($value) > self::VALUE_MAX_LENGTH) {
            throw new InvalidArgumentException('配置值最多 ' . self::VALUE_MAX_LENGTH . ' 个字符');
        }

        return $value;
    }
}
