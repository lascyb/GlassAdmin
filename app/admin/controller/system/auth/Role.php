<?php
declare (strict_types=1);

namespace app\admin\controller\system\auth;

use app\admin\model\system\auth\Admin as AdminModel;
use app\admin\model\system\auth\AdminRole;
use app\admin\model\system\auth\Role as RoleModel;
use app\admin\model\system\auth\RoleRule;
use app\admin\model\system\auth\Rule as RuleModel;
use app\admin\service\AdminRbacService;
use app\common\controller\AdminController;
use think\facade\Db;
use think\Request;
use think\Response;

/**
 * Role 权限角色管理（树形仅组织；权限 assignRules 扁平无继承）
 */
class Role extends AdminController
{
    /** 超级管理员角色 id；批量绑管理员会直接提权，须拦截 */
    private const int SUPER_ROLE_ID = 1;

    /** 单次批量绑定管理员上限 */
    private const int ASSIGN_ADMINS_MAX = 1000;

    /**
     * tree 角色树（含 children）
     *
     * @return Response
     */
    public function tree(): Response
    {
        $rows = RoleModel::order('sort', 'asc')->order('id', 'asc')->select()->toArray();
        $countRows = AdminRole::field('role_id, COUNT(*) AS cnt')
            ->group('role_id')
            ->select()
            ->toArray();
        $countByRole = [];
        foreach ($countRows as $cr) {
            $countByRole[(int) $cr['role_id']] = (int) $cr['cnt'];
        }
        foreach ($rows as &$row) {
            $row['admin_count'] = $countByRole[(int) $row['id']] ?? 0;
        }
        unset($row);
        $tree = $this->buildTree($rows, 0);

        return $this->ok(['list' => $tree]);
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
            return $this->fail(400, 'id 无效');
        }
        $row = RoleModel::find($id);
        if (!$row) {
            return $this->fail(404, '不存在');
        }
        $data = $row->toArray();
        $data['rule_ids'] = array_map('intval', RoleRule::where('role_id', $id)->column('rule_id'));

        return $this->ok($data);
    }

    /**
     * save 新建角色
     *
     * @return Response
     */
    public function save(Request $request): Response
    {
        if (!$request->isPost()) {
            return $this->fail(405, '仅支持 POST');
        }
        $post = $request->post();
        $code = $request->post("code/s", "", "trim");
        $name = $request->post("name/s", "", "trim");
        if ($name == "") {
            return $this->fail(400, '角色名必填');
        }
        $m = new RoleModel();
        $m->save([
            'pid' => (int)($post['pid'] ?? 0),
            'code' => $code,
            'name' => $name,
            'desc' => isset($post['desc']) ? (string)$post['desc'] : null,
            'sort' => (int)($post['sort'] ?? 0),
            'status' => (int)($post['status'] ?? 1),
        ]);

        return $this->ok(['id' => (int)$m->getAttr('id')]);
    }

    /**
     * update 更新角色
     *
     * @return Response
     */
    public function update(Request $request): Response
    {
        if (!$request->isPut()) {
            return $this->fail(405, '仅支持 PUT');
        }
        $id = (int)$request->get('id', 0);
        if ($id <= 0) {
            return $this->fail(400, 'id 无效');
        }
        $row = RoleModel::find($id);
        if (!$row) {
            return $this->fail(404, '不存在');
        }
        $put = array_merge($row->toArray(), $request->put());
        if (trim((string)($put['name'] ?? '')) === '') {
            return $this->fail(400, '角色名必填');
        }
        $row->save([
            'pid' => (int)($put['pid'] ?? $row->getAttr('pid')),
            'code' => trim((string)$put['code']),
            'name' => trim((string)$put['name']),
            'desc' => array_key_exists('desc', $put) ? (string)$put['desc'] : $row->getAttr('desc'),
            'sort' => (int)($put['sort'] ?? $row->getAttr('sort')),
            'status' => (int)($put['status'] ?? $row->getAttr('status')),
        ]);

        return $this->ok();
    }

    /**
     * delete 删除角色（有子角色禁止）
     *
     * @return Response
     */
    public function delete(Request $request): Response
    {
        if (!$request->isDelete()) {
            return $this->fail(405, '仅支持 DELETE');
        }
        $id = (int)$request->get('id', 0);
        if ($id <= 0) {
            return $this->fail(400, 'id 无效');
        }
        if (RoleModel::where('pid', $id)->count() > 0) {
            return $this->fail(400, '存在子角色，禁止删除');
        }
        if (AdminRole::where('role_id', $id)->count() > 0) {
            return $this->fail(400, '仍有管理员绑定该角色');
        }
        RoleRule::where('role_id', $id)->delete();
        RoleModel::destroy($id);
        RuleModel::forgetRoleMenuCache($id);
        AdminRbacService::forgetRoleRbacCache($id);

        return $this->ok();
    }

    /**
     * assignRules 全量覆盖角色与前端规则的关联（无父子继承语义）
     *
     * @return Response
     */
    public function assignRules(Request $request): Response
    {
        if (!$request->isPut()) {
            return $this->fail(405, '仅支持 PUT');
        }
        $id = (int)$request->get('id', 0);
        if ($id <= 0) {
            return $this->fail(400, 'id 无效');
        }
        if (!RoleModel::find($id)) {
            return $this->fail(404, '角色不存在');
        }
        $ruleIds = $request->put('rule_ids/a', []);
        $ruleIds = is_array($ruleIds) ? array_unique(array_map('intval', $ruleIds)) : [];
        $ruleIds = $ruleIds === [] ? [] : RuleModel::where([['id', 'in', $ruleIds], ['scope', '=', 'frontend']])->column('id');
        $insert = [];
        foreach ($ruleIds as $rid) {
            $insert[] = ['role_id' => $id, 'rule_id' => $rid];
        }
        try {
            RoleRule::where('role_id', $id)->delete();
            RoleRule::insertAll($insert);
        } catch (\Exception $e) {
            return $this->fail(400, $e->getMessage());
        }

        RuleModel::forgetRoleMenuCache($id);
        AdminRbacService::forgetRoleRbacCache($id);

        return $this->ok();
    }

    /**
     * assignAdmins 批量追加绑定角色到管理员（只新增关联，不改动已有角色）
     *
     * 与 Admin/update 的「全量覆盖」语义不同：这里只补缺失的 (admin_id, role_id) 行，
     * 已拥有该角色的管理员跳过，不会解除其任何已有角色。
     *
     * 超级管理员角色（id=1）等同全量授权，必须走 Admin/update 的显式单条流程，禁止批量绑定。
     *
     * @return Response
     */
    public function assignAdmins(Request $request): Response
    {
        if (!$request->isPut()) {
            return $this->fail(405, '仅支持 PUT');
        }
        $id = (int)$request->get('id', 0);
        if ($id <= 0) {
            return $this->fail(400, 'id 无效');
        }
        if (!RoleModel::find($id)) {
            return $this->fail(404, '角色不存在');
        }
        if ($id === self::SUPER_ROLE_ID) {
            return $this->fail(400, '超级管理员角色不支持批量绑定');
        }
        $adminIds = $request->put('admin_ids/a', []);
        $adminIds = is_array($adminIds) ? array_map('intval', $adminIds) : [];
        $adminIds = array_values(array_unique(array_filter($adminIds, static fn(int $aid): bool => $aid > 0)));
        if ($adminIds === []) {
            return $this->fail(400, '请选择管理员');
        }
        if (count($adminIds) > self::ASSIGN_ADMINS_MAX) {
            return $this->fail(400, '单次最多绑定 ' . self::ASSIGN_ADMINS_MAX . ' 个管理员');
        }
        // 只保留真实存在的管理员，避免脏 id 写入关联表
        $adminIds = array_map('intval', AdminModel::whereIn('id', $adminIds)->column('id'));
        if ($adminIds === []) {
            return $this->fail(400, '管理员不存在');
        }
        $existing = array_map(
            'intval',
            AdminRole::where('role_id', $id)->whereIn('admin_id', $adminIds)->column('admin_id')
        );
        $missing = array_values(array_diff($adminIds, $existing));
        if ($missing !== []) {
            $rows = [];
            foreach ($missing as $adminId) {
                $rows[] = ['admin_id' => $adminId, 'role_id' => $id, 'enabled' => 1];
            }
            try {
                Db::transaction(static function () use ($rows): void {
                    AdminRole::insertAll($rows);
                });
            } catch (\Exception $e) {
                return $this->fail(400, $e->getMessage());
            }
        }

        return $this->ok([
            'added'   => count($missing),
            'skipped' => count($existing),
        ]);
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
