<?php
declare (strict_types=1);

namespace app\controller\auth;

use app\BaseController;
use app\model\auth\Role as RoleModel;
use app\model\auth\RoleRule;
use app\model\auth\Rule as RuleModel;
use think\facade\Db;
use think\Request;
use think\Response;

/**
 * Role 权限角色管理（树形仅组织；权限 assignRules 扁平无继承）
 */
class Role extends BaseController
{
    /**
     * tree 角色树（含 children）
     *
     * @return Response
     */
    public function tree(): Response
    {
        $rows = RoleModel::order('sort', 'asc')->order('id', 'asc')->select()->toArray();
        $tree = $this->buildTree($rows, 0);

        return json(['code' => 0, 'msg' => 'ok', 'data' => ['list' => $tree]]);
    }

    /**
     * read 角色详情 + 已分配的前端 rule_ids（扁平）
     *
     * @return Response
     */
    public function read(Request $request): Response
    {
        $id = (int)$request->get('id', 0);
        if ($id <= 0) {
            return json(['code' => 400, 'msg' => 'id 无效', 'data' => null]);
        }
        $row = RoleModel::find($id);
        if (!$row) {
            return json(['code' => 404, 'msg' => '不存在', 'data' => null]);
        }
        $data = $row->toArray();
        $data['rule_ids'] = array_map('intval', Db::name('auth_role_rule')->where('role_id', $id)->column('rule_id'));

        return json(['code' => 0, 'msg' => 'ok', 'data' => $data]);
    }

    /**
     * save 新建角色
     *
     * @return Response
     */
    public function save(Request $request): Response
    {
        if (!$request->isPost()) {
            return json(['code' => 405, 'msg' => '仅支持 POST', 'data' => null]);
        }
        $post = $request->post();
        $name = $request->post("name/s", "", "trim");
        if ($name == "") {
            return json(['code' => 400, 'msg' => '角色名必填', 'data' => null]);
        }
        $m = new RoleModel();
        $m->save([
            'pid' => (int)($post['pid'] ?? 0),
            'name' => $name,
            'desc' => isset($post['desc']) ? (string)$post['desc'] : null,
            'sort' => (int)($post['sort'] ?? 0),
            'status' => (int)($post['status'] ?? 1),
        ]);

        return json(['code' => 0, 'msg' => 'ok', 'data' => ['id' => (int)$m->getAttr('id')]]);
    }

    /**
     * update 更新角色
     *
     * @return Response
     */
    public function update(Request $request): Response
    {
        if (!$request->isPut()) {
            return json(['code' => 405, 'msg' => '仅支持 PUT', 'data' => null]);
        }
        $id = (int)$request->get('id', 0);
        if ($id <= 0) {
            return json(['code' => 400, 'msg' => 'id 无效', 'data' => null]);
        }
        $row = RoleModel::find($id);
        if (!$row) {
            return json(['code' => 404, 'msg' => '不存在', 'data' => null]);
        }
        $put = array_merge($row->toArray(), $request->put());
        if (trim((string)($put['name'] ?? '')) === '') {
            return json(['code' => 400, 'msg' => '角色名必填', 'data' => null]);
        }
        $row->save([
            'pid' => (int)($put['pid'] ?? $row->getAttr('pid')),
            'name' => trim((string)$put['name']),
            'desc' => array_key_exists('desc', $put) ? (string)$put['desc'] : $row->getAttr('desc'),
            'sort' => (int)($put['sort'] ?? $row->getAttr('sort')),
            'status' => (int)($put['status'] ?? $row->getAttr('status')),
        ]);

        return json(['code' => 0, 'msg' => 'ok', 'data' => null]);
    }

    /**
     * delete 删除角色（有子角色禁止）
     *
     * @return Response
     */
    public function delete(Request $request): Response
    {
        if (!$request->isDelete()) {
            return json(['code' => 405, 'msg' => '仅支持 DELETE', 'data' => null]);
        }
        $id = (int)$request->get('id', 0);
        if ($id <= 0) {
            return json(['code' => 400, 'msg' => 'id 无效', 'data' => null]);
        }
        if (RoleModel::where('pid', $id)->count() > 0) {
            return json(['code' => 400, 'msg' => '存在子角色，禁止删除', 'data' => null]);
        }
        Db::name('auth_role_rule')->where('role_id', $id)->delete();
        RoleModel::destroy($id);

        return json(['code' => 0, 'msg' => 'ok', 'data' => null]);
    }

    /**
     * assignRules 全量覆盖角色与前端规则的关联（无父子继承语义）
     *
     * @return Response
     */
    public function assignRules(Request $request): Response
    {
        if (!$request->isPut()) {
            return json(['code' => 405, 'msg' => '仅支持 PUT', 'data' => null]);
        }
        $id = (int)$request->get('id', 0);
        if ($id <= 0) {
            return json(['code' => 400, 'msg' => 'id 无效', 'data' => null]);
        }
        if (!RoleModel::find($id)) {
            return json(['code' => 404, 'msg' => '角色不存在', 'data' => null]);
        }
        $ruleIds = $request->put("rule_ids/a", [], "is_int");
//        $ruleIds = array_unique(array_map('intval', $ruleIds));
        $ruleIds = RuleModel::where([['id', "in", $id], ["scope", "=", "frontend"]])->column('id');
        $insert = [];
        foreach ($ruleIds as $rid) {
            $insert[] = ['role_id' => $id, 'rule_id' => $rid];
        }
        try {
            RoleRule::where('role_id', $id)->delete();
            RoleRule::insertAll($insert);
        } catch (\Exception $e) {
            return json(['code' => 400, 'msg' => $e->getMessage()]);
        }

        return json(['code' => 0, 'msg' => 'ok', 'data' => null]);
    }

    /**
     * parseJsonBody 读取 JSON 请求体
     *
     * @return array<string,mixed>
     */
    private function parseJsonBody(Request $request): array
    {
        $ct = (string)$request->header('Content-Type', '');
        if (!str_contains($ct, 'application/json')) {
            return [];
        }
        $raw = (string)$request->getContent();
        if ($raw === '') {
            return [];
        }
        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * buildTree 组装角色树
     *
     * @param list<array<string,mixed>> $rows
     * @return list<array<string,mixed>>
     */
    private function buildTree(array $rows, int $pid): array
    {
        $out = [];
        foreach ($rows as $r) {
            if ((int)$r['pid'] !== $pid) {
                continue;
            }
            $r['children'] = $this->buildTree($rows, (int)$r['id']);
            $out[] = $r;
        }

        return $out;
    }
}
