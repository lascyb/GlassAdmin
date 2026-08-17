<?php

declare(strict_types=1);

namespace app\admin\model\auth;

use think\facade\Cache;
use think\Model;

/**
 * Rule 权限规则（后端树 menu/api + 前端树；含侧栏菜单组树与描述）
 *
 * @property int         $id
 * @property string      $scope   {backend:后端,frontend:前端}
 * @property string      $type
 * @property int         $pid
 * @property string      $name
 * @property string|null $path
 * @property string|null $method
 * @property string|null $component
 * @property string|null $icon
 * @property int         $sort
 * @property int         $status  {1:启用,0:禁用}
 * @property int         $visibility {0:侧栏可见,1:展示全部时可发现,2:不入侧栏}
 * @property string|null $description
 */
class Rule extends Model
{
    /** @var string 表名 */
    protected $name = 'auth_rule';

    /** @var list<string>|true */
    protected array|bool $autoWriteTimestamp = true;

    /** @var string 与 datetime 列一致（全局 datetime_format=false 时须显式指定） */
    protected string $dateFormat = 'Y-m-d H:i:s';

    /** @var array<string, string> */
    protected $type = [
        'id'         => 'integer',
        'pid'        => 'integer',
        'sort'       => 'integer',
        'status'     => 'integer',
        'visibility' => 'integer',
    ];

    /** 授权后侧栏可见（默认） */
    public const int VISIBILITY_SIDEBAR = 0;

    /** 未授权时也可出现在「展示全部」目录中 */
    public const int VISIBILITY_CATALOG = 1;

    /** 不入侧栏；子节点提升到同级（用于目录） */
    public const int VISIBILITY_HIDDEN = 2;

    /** 可见性取值 */
    public const array VISIBILITIES = [self::VISIBILITY_SIDEBAR, self::VISIBILITY_CATALOG, self::VISIBILITY_HIDDEN];

    /** 可配置可见性的前端类型；其余固定为 VISIBILITY_SIDEBAR */
    public const array VISIBILITY_TYPES = ['menu', 'view', 'link', 'iframe'];

    /** 可设为 VISIBILITY_HIDDEN 的前端类型；页面不入侧栏请用 type=route */
    public const array HIDDEN_TYPES = ['menu'];

    /** 可关联后端 api 的前端类型 */
    public const array API_BINDABLE_TYPES = ['view', 'route', 'btn'];

    /** 超级管理员 role_id */
    private const int SUPER_ROLE_ID = 1;

    /** 菜单树缓存键前缀 */
    private const string CACHE_KEY_PREFIX = 'admin_frontend_menu_tree:roles:';

    /** 菜单树缓存全局标签 */
    private const string CACHE_TAG_GLOBAL = 'admin_frontend_menu';

    /** 缓存有效期（秒） */
    private const int CACHE_TTL = 60;

    /** 可出现在侧栏的前端规则类型 */
    private const array MENU_DISPLAY_TYPES = ['menu', 'view', 'page', 'route', 'link', 'iframe'];

    /**
     * children 直接子节点
     *
     * @return \think\model\relation\HasMany
     */
    public function children()
    {
        return $this->hasMany(self::class, 'pid', 'id')->order('sort', 'asc');
    }

    /**
     * parent 父节点
     *
     * @return \think\model\relation\BelongsTo
     */
    public function parent()
    {
        return $this->belongsTo(self::class, 'pid', 'id');
    }

    /**
     * menusForRoles 角色可见菜单树（catalog=1 时含可发现未授权项，带 authorized）
     *
     * @param list<int> $roleIds
     * @return list<array<string,mixed>>
     */
    public static function menusForRoles(array $roleIds, bool $catalog = false): array
    {
        $roleIds = array_values(array_unique(array_filter(array_map('intval', $roleIds), static fn(int $id): bool => $id > 0)));
        if ($roleIds === []) {
            return [];
        }

        $key = self::menuCacheKey($roleIds, $catalog);
        $cached = Cache::get($key);
        if (is_array($cached)) {
            return $cached;
        }

        $menus = self::buildMenusForRoles($roleIds, $catalog);
        Cache::tag(self::CACHE_TAG_GLOBAL)->set($key, $menus, self::CACHE_TTL);

        return $menus;
    }

    /**
     * describeForAdmin 无权限页描述；仅授权或可发现可读
     *
     * @param list<int> $roleIds
     * @return array<string,mixed>|null
     */
    public static function describeForAdmin(int $ruleId, array $roleIds): ?array
    {
        if ($ruleId <= 0) {
            return null;
        }
        $row = self::where('id', $ruleId)
            ->where('scope', 'frontend')
            ->where('status', 1)
            ->field('id,name,type,path,icon,description,visibility,pid')
            ->find();
        if (!$row) {
            return null;
        }

        $authorized = self::isRuleAuthorized($ruleId, $roleIds);
        $visibility = (int) $row->getAttr('visibility');
        // 无权限页：仅授权或 catalog 可发现可读；hidden 未授权不可读
        if (!$authorized && $visibility !== self::VISIBILITY_CATALOG) {
            return null;
        }

        $data = $row->toArray();
        $pool = self::where('scope', 'frontend')
            ->where('status', 1)
            ->field('id,pid,path,type')
            ->select()
            ->toArray();
        $byId = [];
        foreach ($pool as $r) {
            $byId[(int) $r['id']] = $r;
        }
        $absPath = self::absolutePath($data, $byId);

        return [
            'id'          => (int) $data['id'],
            'name'        => (string) ($data['name'] ?? ''),
            'type'        => (string) ($data['type'] ?? ''),
            'path'        => $absPath,
            'icon'        => $data['icon'] ?? null,
            'description' => $data['description'] ?? null,
        ];
    }

    /**
     * forgetMenuCaches 清除全部菜单树缓存
     */
    public static function forgetMenuCaches(): void
    {
        Cache::tag(self::CACHE_TAG_GLOBAL)->clear();
    }

    /**
     * forgetRoleMenuCache 角色规则变更后清缓存（实现为全清）
     */
    public static function forgetRoleMenuCache(int $roleId): void
    {
        if ($roleId > 0) {
            self::forgetMenuCaches();
        }
    }

    /**
     * @param list<int> $roleIds
     */
    private static function menuCacheKey(array $roleIds, bool $catalog): string
    {
        $sorted = $roleIds;
        sort($sorted);

        return self::CACHE_KEY_PREFIX . implode(',', $sorted) . ($catalog ? ':catalog' : '');
    }

    /**
     * @param list<int> $roleIds
     * @return list<array<string,mixed>>
     */
    private static function buildMenusForRoles(array $roleIds, bool $catalog): array
    {
        $pool = self::where('scope', 'frontend')
            ->where('status', 1)
            ->order('sort', 'asc')
            ->order('id', 'asc')
            ->select()
            ->toArray();

        if ($pool === []) {
            return [];
        }

        $byId = [];
        foreach ($pool as $r) {
            $byId[(int) $r['id']] = $r;
        }

        $isSuper = in_array(self::SUPER_ROLE_ID, $roleIds, true);
        $granted = $isSuper ? null : self::grantedRuleIdMap($roleIds);

        if (!$isSuper && $granted === [] && !$catalog) {
            return [];
        }

        $filteredRows = self::filterRowsForMenuTree($pool, $granted, $catalog, $isSuper);
        if ($filteredRows === []) {
            return [];
        }

        $filteredRows = self::applyAbsolutePaths($filteredRows, $byId);
        if ($isSuper) {
            $tree = self::buildTreeFromRows($filteredRows, 0);

            return self::menuNodes($tree, true);
        }

        $tree = self::buildTreePromotingOrphans($filteredRows, $pool);

        return self::menuNodes($tree, false, $granted);
    }

    /**
     * filterRowsForMenuTree 侧栏可见项 + 已授权 btn（供 v-auth 收集 path）
     *
     * @param list<array<string,mixed>> $pool
     * @param array<int, true>|null $granted null=超管
     * @return list<array<string,mixed>>
     */
    private static function filterRowsForMenuTree(
        array $pool,
        ?array $granted,
        bool $catalog,
        bool $isSuper,
    ): array {
        $filteredRows = array_values(array_filter(
            $pool,
            static function (array $r) use ($granted, $catalog, $isSuper): bool {
                $id   = (int) $r['id'];
                $type = (string) ($r['type'] ?? '');
                if ($type === 'btn') {
                    return $isSuper || ($granted !== null && isset($granted[$id]));
                }
                if (!in_array($type, self::MENU_DISPLAY_TYPES, true)) {
                    return false;
                }
                if ($isSuper) {
                    return true;
                }
                if ($granted !== null && isset($granted[$id])) {
                    return true;
                }

                return $catalog && (int) ($r['visibility'] ?? 0) === self::VISIBILITY_CATALOG;
            }
        ));

        return self::pruneOrphanBtnRows($filteredRows, $pool);
    }

    /**
     * pruneOrphanBtnRows 丢弃父页面不在可见集合内的 btn，避免被提升为侧栏根节点
     *
     * @param list<array<string,mixed>> $filteredRows
     * @param list<array<string,mixed>> $pool
     * @return list<array<string,mixed>>
     */
    private static function pruneOrphanBtnRows(array $filteredRows, array $pool): array
    {
        $byId = [];
        foreach ($pool as $r) {
            $byId[(int) $r['id']] = $r;
        }
        $nonBtnIds = [];
        foreach ($filteredRows as $r) {
            if ((string) ($r['type'] ?? '') !== 'btn') {
                $nonBtnIds[(int) $r['id']] = true;
            }
        }

        return array_values(array_filter(
            $filteredRows,
            static function (array $r) use ($byId, $nonBtnIds): bool {
                if ((string) ($r['type'] ?? '') !== 'btn') {
                    return true;
                }
                $pid   = (int) ($r['pid'] ?? 0);
                $guard = 0;
                while ($pid > 0 && $guard++ < 64) {
                    if (isset($nonBtnIds[$pid])) {
                        return true;
                    }
                    $pid = isset($byId[$pid]) ? (int) $byId[$pid]['pid'] : 0;
                }

                return false;
            }
        ));
    }

    /**
     * @param list<int> $roleIds
     * @return array<int, true>
     */
    private static function grantedRuleIdMap(array $roleIds): array
    {
        $ruleIds = [];
        foreach ($roleIds as $roleId) {
            $ids = RoleRule::where('role_id', $roleId)->column('rule_id');
            foreach ($ids as $id) {
                $ruleIds[(int) $id] = true;
            }
        }

        return $ruleIds;
    }

    /**
     * @param list<int> $roleIds
     */
    private static function isRuleAuthorized(int $ruleId, array $roleIds): bool
    {
        $roleIds = array_values(array_unique(array_filter(array_map('intval', $roleIds), static fn(int $id): bool => $id > 0)));
        if ($roleIds === []) {
            return false;
        }
        if (in_array(self::SUPER_ROLE_ID, $roleIds, true)) {
            return true;
        }
        $granted = self::grantedRuleIdMap($roleIds);

        return isset($granted[$ruleId]);
    }

    /**
     * @param list<array<string,mixed>> $rows
     * @param array<int, array<string,mixed>> $byId
     * @return list<array<string,mixed>>
     */
    private static function applyAbsolutePaths(array $rows, array $byId): array
    {
        $out = [];
        foreach ($rows as $r) {
            $type = (string) ($r['type'] ?? '');
            if (in_array($type, self::MENU_DISPLAY_TYPES, true)) {
                $r['path'] = self::absolutePath($r, $byId);
            }
            $out[] = $r;
        }

        return $out;
    }

    /**
     * @param array<string,mixed> $row
     * @param array<int, array<string,mixed>> $byId
     */
    private static function absolutePath(array $row, array $byId): string
    {
        $path = (string) ($row['path'] ?? '');
        if (self::isExternalPath($path)) {
            return $path;
        }
        if (str_starts_with($path, '/')) {
            return self::normalizeAbsPath($path);
        }

        $segments = [];
        $cur      = $row;
        $guard    = 0;
        while ($cur !== null && $guard++ < 64) {
            $p = (string) ($cur['path'] ?? '');
            if (self::isExternalPath($p)) {
                return $p;
            }
            if (str_starts_with($p, '/')) {
                $base = self::normalizeAbsPath($p);

                return $segments === []
                    ? $base
                    : self::normalizeAbsPath($base . '/' . implode('/', $segments));
            }
            if ($p !== '') {
                array_unshift($segments, $p);
            }
            $pid = (int) ($cur['pid'] ?? 0);
            if ($pid <= 0 || !isset($byId[$pid])) {
                break;
            }
            $cur = $byId[$pid];
        }

        return $segments === [] ? '/' : '/' . implode('/', $segments);
    }

    private static function isExternalPath(string $path): bool
    {
        return (bool) preg_match('#^(https?:)?//#i', $path) || str_starts_with($path, '://');
    }

    private static function normalizeAbsPath(string $path): string
    {
        $parts = array_values(array_filter(explode('/', $path), static fn(string $s): bool => $s !== ''));

        return '/' . implode('/', $parts);
    }

    /**
     * @param list<array<string,mixed>> $visibleRows
     * @param list<array<string,mixed>> $pool
     * @return list<array<string,mixed>>
     */
    private static function buildTreePromotingOrphans(array $visibleRows, array $pool): array
    {
        $byId = [];
        foreach ($pool as $r) {
            $byId[(int) $r['id']] = $r;
        }
        $visible = [];
        foreach ($visibleRows as $r) {
            $visible[(int) $r['id']] = true;
        }

        $adjusted = [];
        foreach ($visibleRows as $r) {
            $pid = (int) ($r['pid'] ?? 0);
            while ($pid > 0 && !isset($visible[$pid])) {
                $pid = isset($byId[$pid]) ? (int) $byId[$pid]['pid'] : 0;
            }
            $r['pid'] = $pid;
            $adjusted[] = $r;
        }

        return self::buildTreeFromRows($adjusted, 0);
    }

    /**
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
     * menuNodes 转为前端 Menu（不含 description）
     *
     * @param list<array<string,mixed>> $tree
     * @param array<int, true>|null $granted null=超管全授权
     * @return list<array<string,mixed>>
     */
    private static function menuNodes(array $tree, bool $allAuthorized, ?array $granted = null): array
    {
        $out = [];
        foreach ($tree as $n) {
            $id = (int) $n['id'];
            $item = [
                'id'         => $id,
                'name'       => $n['name'],
                'type'       => $n['type'],
                'path'       => $n['path'] ?? '',
                'visibility' => (int) ($n['visibility'] ?? self::VISIBILITY_SIDEBAR),
                'authorized' => $allAuthorized || ($granted !== null && isset($granted[$id])),
            ];
            if (!empty($n['component'])) {
                $item['component'] = $n['component'];
            }
            if (!empty($n['icon'])) {
                $item['icon'] = $n['icon'];
            }
            $children = $n['children'] ?? [];
            if ($children !== []) {
                $item['children'] = self::menuNodes($children, $allAuthorized, $granted);
            }
            $out[] = $item;
        }

        return $out;
    }
}
