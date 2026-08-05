<?php
declare (strict_types = 1);

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
 * @property int         $discoverable {1:展示全部时可发现,0:仅已授权可见}
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
        'id'           => 'integer',
        'pid'          => 'integer',
        'sort'         => 'integer',
        'status'       => 'integer',
        'discoverable' => 'integer',
    ];

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
        $roleIds = array_values(array_unique(array_filter(array_map('intval', $roleIds), static fn (int $id): bool => $id > 0)));
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
            ->field('id,name,type,path,icon,description,discoverable,pid')
            ->find();
        if (!$row) {
            return null;
        }

        $authorized = self::isRuleAuthorized($ruleId, $roleIds);
        $discoverable = (int) $row->getAttr('discoverable') === 1;
        if (!$authorized && !$discoverable) {
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

        if ($isSuper) {
            $filteredRows = array_values(array_filter(
                $pool,
                static fn (array $r): bool => in_array((string) ($r['type'] ?? ''), self::MENU_DISPLAY_TYPES, true)
            ));
            $filteredRows = self::applyAbsolutePaths($filteredRows, $byId);
            $tree         = self::buildTreeFromRows($filteredRows, 0);

            return self::menuNodes($tree, true);
        }

        if ($granted === [] && !$catalog) {
            return [];
        }

        $filteredRows = array_values(array_filter(
            $pool,
            static function (array $r) use ($granted, $catalog): bool {
                $id = (int) $r['id'];
                $type = (string) ($r['type'] ?? '');
                if (!in_array($type, self::MENU_DISPLAY_TYPES, true)) {
                    return false;
                }
                if (isset($granted[$id])) {
                    return true;
                }
                if ($catalog && (int) ($r['discoverable'] ?? 0) === 1) {
                    return true;
                }

                return false;
            }
        ));

        if ($filteredRows === []) {
            return [];
        }

        $filteredRows = self::applyAbsolutePaths($filteredRows, $byId);
        // 提升时：可见集合内的节点可作为父级（已授权或可发现）
        $tree = self::buildTreePromotingOrphans($filteredRows, $pool);

        return self::menuNodes($tree, false, $granted);
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
        $roleIds = array_values(array_unique(array_filter(array_map('intval', $roleIds), static fn (int $id): bool => $id > 0)));
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
        $parts = array_values(array_filter(explode('/', $path), static fn (string $s): bool => $s !== ''));

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
                'authorized' => $allAuthorized || ($granted !== null && isset($granted[$id])),
            ];
            $method = $n['method'] ?? null;
            if ($method !== null && $method !== '') {
                $item['method'] = $method;
            }
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
