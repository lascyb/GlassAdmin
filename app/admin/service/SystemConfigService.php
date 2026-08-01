<?php
declare (strict_types = 1);

namespace app\admin\service;

use app\admin\model\system\Config;
use think\facade\Cache;
use think\facade\Db;
use think\Paginator;

/**
 * SystemConfigService 系统配置服务（缓存 + 写操作编排）
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
    private const int CACHE_TTL = 0;

    /**
     * getConfig 获取单个配置值（优先读缓存）
     */
    public static function getConfig(string $key): ?string
    {
        $cacheKey = self::CACHE_KEY_PREFIX . $key;
        $cached   = Cache::get($cacheKey);
        if (is_string($cached)) {
            return $cached;
        }
        $value = Config::findValueByKey($key);
        if ($value === null) {
            return null;
        }
        Cache::tag(self::CACHE_TAG)->set($cacheKey, $value, self::CACHE_TTL);

        return $value;
    }

    /**
     * getConfigs 批量获取配置值
     *
     * @return array<string, string>
     */
    public static function getConfigs(array $keys): array
    {
        $result = [];
        $miss   = [];
        foreach ($keys as $key) {
            $cached = Cache::get(self::CACHE_KEY_PREFIX . $key);
            if (is_string($cached)) {
                $result[$key] = $cached;
            } else {
                $miss[] = $key;
            }
        }
        if ($miss !== []) {
            foreach (Config::findValuesByKeys($miss) as $k => $v) {
                $result[$k] = $v;
                Cache::tag(self::CACHE_TAG)->set(self::CACHE_KEY_PREFIX . $k, $v, self::CACHE_TTL);
            }
        }

        return $result;
    }

    /**
     * getAllConfigs 获取全部配置（按 group 分组）
     *
     * @return array<string, array<string, string>>
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
            $result[$group][(string) $row->getAttr('key')] = (string) $row->getAttr('value');
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
     * batchSave 批量保存配置（按 group）
     *
     * @param array<string, string> $pairs key => value 键值对
     */
    public static function batchSave(string $group, array $pairs): void
    {
        Db::transaction(function () use ($group, $pairs) {
            foreach ($pairs as $key => $value) {
                Config::upsertValueByKey((string) $key, (string) $value, $group);
                self::forgetConfigCache((string) $key);
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
}
