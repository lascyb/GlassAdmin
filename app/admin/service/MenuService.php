<?php
declare (strict_types=1);

namespace app\admin\service;

use app\admin\model\auth\Rule;
use think\facade\Cache;
use think\facade\Db;

/**
 * MenuService 侧栏菜单：按 role_ids 关联 auth_role_rule 取前端规则、组树、按角色组合缓存（对外均为 static）
 */
class MenuService
{
    /** 超级管理员 role_id，拥有全部启用的前端菜单 */
    private const int SUPER_ROLE_ID = 1;

    /** 菜单树缓存键前缀，与 role_ids 拼接 */
    private const string CACHE_KEY_PREFIX = 'admin_frontend_menu_tree:roles:';

    /** 菜单树缓存全局标签（仅用于整批失效；单组合仍按 key delete） */
    private const string CACHE_TAG_GLOBAL = 'admin_frontend_menu';

    /** 缓存有效期（秒），0 表示不过期（依赖 forget* 清理） */
    private const int CACHE_TTL = 60;

    /**
     * getMenus 返回角色组合可见菜单树（读缓存或计算后写入）
     *
     * @param list<int> $roleIds
     * @return list<array<string,mixed>>
     */
    public static function getMenus(array $roleIds): array
    {
        $roleIds = array_values(array_unique(array_filter(array_map('intval', $roleIds), static fn (int $id): bool => $id > 0)));
        if ($roleIds === []) {
            return [];
        }

        $key = self::menuCacheKey($roleIds);
        $cached = Cache::get($key);
        if (is_array($cached)) {
            return $cached;
        }

        $menus = self::buildMenusForRoles($roleIds);
        Cache::tag(self::CACHE_TAG_GLOBAL)->set($key, $menus, self::CACHE_TTL);

        return $menus;
    }

    /**
     * forgetRoleMenuCache 清除包含指定 role_id 的菜单树缓存（角色规则分配变更后调用）
     */
    public static function forgetRoleMenuCache(int $roleId): void
    {
        if ($roleId > 0) {
            self::forgetAllRoleMenuCaches();
        }
    }

    /**
     * forgetAllRoleMenuCaches 清除所有已存在角色的菜单树缓存（前端规则结构变更后调用）
     */
    public static function forgetAllRoleMenuCaches(): void
    {
        Cache::tag(self::CACHE_TAG_GLOBAL)->clear();
    }

    /**
     * menuCacheKey 生成菜单缓存键
     *
     * @param list<int> $roleIds
     */
    private static function menuCacheKey(array $roleIds): string
    {
        $sorted = $roleIds;
        sort($sorted);

        return self::CACHE_KEY_PREFIX . implode(',', $sorted);
    }

    /**
     * buildMenusForRoles 根据多角色查询 auth_role_rule 得到前端 rule_id 并集，补全祖先节点后组树
     *
     * @param list<int> $roleIds
     * @return list<array<string,mixed>>
     */
    private static function buildMenusForRoles(array $roleIds): array
    {
        $pool = Rule::where('scope', 'frontend')
            ->where('status', 1)
            ->order('sort', 'asc')
            ->order('id', 'asc')
            ->select()
            ->toArray();

        if ($pool === []) {
            return [];
        }

        if (in_array(self::SUPER_ROLE_ID, $roleIds, true)) {
            $filteredRows = $pool;
        } else {
            $ruleIds = [];
            foreach ($roleIds as $roleId) {
                $ids = Db::name('auth_role_rule')->where('role_id', $roleId)->column('rule_id');
                foreach ($ids as $id) {
                    $ruleIds[(int) $id] = true;
                }
            }
            $seedIds = array_map('intval', array_keys($ruleIds));
            if ($seedIds === []) {
                return [];
            }
            $allowed = self::expandWithAncestors($pool, $seedIds);
            $filteredRows = array_values(array_filter(
                $pool,
                static fn (array $r): bool => in_array((int) $r['id'], $allowed, true)
            ));
        }

        $tree = self::buildTreeFromRows($filteredRows, 0);

        return self::menuNodes($tree);
    }

    /**
     * expandWithAncestors 将已授权节点 id 集合并入其祖先 id（在前端规则池中向上追溯 pid）
     *
     * @param list<array<string,mixed>> $rows
     * @param list<int> $seedIds
     * @return list<int>
     */
    private static function expandWithAncestors(array $rows, array $seedIds): array
    {
        $byId = [];
        foreach ($rows as $r) {
            $byId[(int) $r['id']] = $r;
        }
        $keep = [];
        foreach ($seedIds as $sid) {
            $cur = $sid;
            while ($cur > 0 && isset($byId[$cur])) {
                $keep[$cur] = true;
                $cur = (int) $byId[$cur]['pid'];
            }
        }

        return array_map('intval', array_keys($keep));
    }

    /**
     * buildTreeFromRows 按 pid 组装嵌套 children
     *
     * @param list<array<string,mixed>> $rows
     * @return list<array<string,mixed>>
     */
    private static function buildTreeFromRows(array $rows, int $pid): array
    {
        $out = [];
        foreach ($rows as $r) {
            if ((int) $r['pid'] !== $pid) {
                continue;
            }
            $r['children'] = self::buildTreeFromRows($rows, (int) $r['id']);
            $out[]         = $r;
        }

        return $out;
    }

    /**
     * menuNodes 转为前端 Menu 结构（去掉数据库字段）
     *
     * @param list<array<string,mixed>> $tree
     * @return list<array<string,mixed>>
     */
    private static function menuNodes(array $tree): array
    {
        $out = [];
        foreach ($tree as $n) {
            $item = [
                'id'   => $n['id'],
                'name' => $n['name'],
                'type' => $n['type'],
                'path' => $n['path'] ?? '',
            ];
            if (!empty($n['component'])) {
                $item['component'] = $n['component'];
            }
            if (!empty($n['icon'])) {
                $item['icon'] = $n['icon'];
            }
            $children = $n['children'] ?? [];
            if ($children !== []) {
                $item['children'] = self::menuNodes($children);
            }
            $out[] = $item;
        }

        return $out;
    }
}
