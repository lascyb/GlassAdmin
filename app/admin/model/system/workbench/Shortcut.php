<?php

declare(strict_types=1);

namespace app\admin\model\system\workbench;

use app\admin\model\auth\Rule as RuleModel;
use think\facade\Db;
use think\Model;

/**
 * Shortcut 工作台快捷入口（按管理员持久化；含列表默认填充与全量保存）
 *
 * @property int $id
 * @property int $admin_id
 * @property int $rule_id
 * @property int $sort
 * @property int $create_time
 * @property int $update_time
 */
class Shortcut extends Model
{
    /** @var string 表名 */
    protected $name = 'system_workbench_shortcut';

    /** @var list<string>|true */
    protected array|bool $autoWriteTimestamp = true;

    /** @var array<string, string> */
    protected $type = [
        'id'          => 'integer',
        'admin_id'    => 'integer',
        'rule_id'     => 'integer',
        'sort'        => 'integer',
        'create_time' => 'integer',
        'update_time' => 'integer',
    ];

    /** 未自定义时默认展示条数 */
    private const int DEFAULT_LIMIT = 8;

    /** 可作为快捷入口的菜单类型 */
    private const array SHORTCUT_TYPES = ['view', 'link', 'iframe'];

    /**
     * listForAdmin 当前管理员快捷入口
     *
     * @param list<int> $roleIds
     * @return array{list: list<array<string,mixed>>, source: string}
     */
    public static function listForAdmin(int $adminId, array $roleIds): array
    {
        $menus = RuleModel::menusForRoles($roleIds);
        $flat  = self::flattenShortcutLeaves($menus);
        $byId  = [];
        foreach ($flat as $item) {
            $byId[(int) $item['id']] = $item;
        }

        $rows = self::where('admin_id', $adminId)
            ->order('sort', 'asc')
            ->order('id', 'asc')
            ->select()
            ->toArray();

        if ($rows === []) {
            $list = [];
            $i    = 0;
            foreach ($flat as $item) {
                if ($i >= self::DEFAULT_LIMIT) {
                    break;
                }
                $list[] = self::toListItem($item, $i);
                $i++;
            }

            return ['list' => $list, 'source' => 'default'];
        }

        $list = [];
        $i    = 0;
        foreach ($rows as $row) {
            $ruleId = (int) ($row['rule_id'] ?? 0);
            if ($ruleId <= 0 || !isset($byId[$ruleId])) {
                continue;
            }
            $list[] = self::toListItem($byId[$ruleId], $i);
            $i++;
        }

        return ['list' => $list, 'source' => 'custom'];
    }

    /**
     * saveForAdmin 全量替换；空数组仅删除（恢复默认）
     *
     * @param list<int|string> $ruleIds
     */
    public static function saveForAdmin(int $adminId, array $ruleIds): void
    {
        $ids = [];
        foreach ($ruleIds as $id) {
            $rid = (int) $id;
            if ($rid > 0 && !isset($ids[$rid])) {
                $ids[$rid] = true;
            }
        }
        $ordered = array_map('intval', array_keys($ids));

        Db::transaction(static function () use ($adminId, $ordered): void {
            self::where('admin_id', $adminId)->delete();
            if ($ordered === []) {
                return;
            }
            $now  = time();
            $rows = [];
            foreach ($ordered as $sort => $ruleId) {
                $rows[] = [
                    'admin_id'    => $adminId,
                    'rule_id'     => $ruleId,
                    'sort'        => $sort,
                    'create_time' => $now,
                    'update_time' => $now,
                ];
            }
            Db::name('system_workbench_shortcut')->insertAll($rows);
        });
    }

    /**
     * flattenShortcutLeaves DFS 扁平化可作快捷入口的叶子
     *
     * @param list<array<string,mixed>> $menus
     * @return list<array<string,mixed>>
     */
    private static function flattenShortcutLeaves(array $menus): array
    {
        $out = [];
        self::dfsLeaves($menus, $out);

        return $out;
    }

    /**
     * @param list<array<string,mixed>> $menus
     * @param list<array<string,mixed>> $out
     */
    private static function dfsLeaves(array $menus, array &$out): void
    {
        foreach ($menus as $menu) {
            $nav = [];
            $children = $menu['children'] ?? [];
            if (is_array($children)) {
                foreach ($children as $child) {
                    if (!is_array($child)) {
                        continue;
                    }
                    if ((string) ($child['type'] ?? '') === 'btn') {
                        continue;
                    }
                    $nav[] = $child;
                }
            }
            if ($nav !== []) {
                self::dfsLeaves($nav, $out);
                continue;
            }
            $type = (string) ($menu['type'] ?? '');
            if (!in_array($type, self::SHORTCUT_TYPES, true)) {
                continue;
            }
            if ((int) ($menu['visibility'] ?? 0) === RuleModel::VISIBILITY_HIDDEN) {
                continue;
            }
            $out[] = $menu;
        }
    }

    /**
     * toListItem 转为 API 列表项
     *
     * @param array<string,mixed> $menu
     * @return array<string,mixed>
     */
    private static function toListItem(array $menu, int $sort): array
    {
        return [
            'id'   => (int) $menu['id'],
            'name' => (string) ($menu['name'] ?? ''),
            'path' => (string) ($menu['path'] ?? ''),
            'icon' => $menu['icon'] ?? null,
            'type' => (string) ($menu['type'] ?? ''),
            'sort' => $sort,
        ];
    }
}
