<?php
declare (strict_types = 1);

namespace app\controller;

use app\BaseController;
use app\model\Admin;
use app\model\auth\Rule;
use think\facade\Db;
use think\Response;

/**
 * Menu 动态侧栏菜单（按管理员角色过滤前端规则树）
 */
class Menu extends BaseController
{
    /**
     * menus 返回当前用户可见菜单树
     *
     * @return Response JSON {code,msg,data:{menus:[]}}
     */
    public function menus(): Response
    {
        $roleId = (int) $this->request->role_id;

        $rows    = Rule::where('scope', 'frontend')
            ->where('status', 1)
            ->order('sort', 'asc')
            ->order('id', 'asc')
            ->select()
            ->toArray();

        $filteredRows = $rows;
        if ($roleId !== 1) {
            if ($roleId <= 0) {
                return json(['code' => 0, 'msg' => 'ok', 'data' => ['menus' => []]]);
            }
            $ruleIds = Db::name('auth_role_rule')->where('role_id', $roleId)->column('rule_id');
            $ruleIds = array_map('intval', $ruleIds);
            if ($ruleIds === []) {
                return json(['code' => 0, 'msg' => 'ok', 'data' => ['menus' => []]]);
            }
            $allowed = $this->expandWithAncestors($rows, $ruleIds);
            $filteredRows = array_values(array_filter($rows, static fn (array $r): bool => in_array((int) $r['id'], $allowed, true)));
        }

        $tree = $this->buildTreeFromRows($filteredRows, 0);

        return json([
            'code' => 0,
            'msg'  => 'ok',
            'data' => ['menus' => $this->menuNodes($tree)],
        ]);
    }

    /**
     * expandWithAncestors 将已授权节点 id 集合并入其祖先 id
     *
     * @param  list<array<string,mixed>> $rows
     * @param  list<int>                 $seedIds
     * @return list<int>
     */
    private function expandWithAncestors(array $rows, array $seedIds): array
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
     * @param  list<array<string,mixed>> $rows
     * @return list<array<string,mixed>>
     */
    private function buildTreeFromRows(array $rows, int $pid): array
    {
        $out = [];
        foreach ($rows as $r) {
            if ((int) $r['pid'] !== $pid) {
                continue;
            }
            $r['children'] = $this->buildTreeFromRows($rows, (int) $r['id']);
            $out[]         = $r;
        }

        return $out;
    }

    /**
     * menuNodes 转为前端 Menu 结构（去掉数据库字段）
     *
     * @param  list<array<string,mixed>> $tree
     * @return list<array<string,mixed>>
     */
    private function menuNodes(array $tree): array
    {
        $out = [];
        foreach ($tree as $n) {
            $item = [
                'id'   => (int) $n['id'],
                'name' => (string) $n['name'],
                'path' => (string) ($n['path'] ?? ''),
                'type' => (string) $n['type'],
            ];
            if (!empty($n['component'])) {
                $item['component'] = (string) $n['component'];
            }
            if (!empty($n['icon'])) {
                $item['icon'] = (string) $n['icon'];
            }
            $children = $n['children'] ?? [];
            if ($children !== []) {
                $item['children'] = $this->menuNodes($children);
            }
            $out[] = $item;
        }

        return $out;
    }
}
