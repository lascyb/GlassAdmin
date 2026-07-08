<?php
declare (strict_types = 1);

namespace app\admin\controller\auth;

use app\admin\model\auth\Rule as RuleModel;
use app\admin\model\auth\RuleFrontendApi;
use app\admin\service\AdminRbacService;
use app\admin\service\MenuService;
use app\common\controller\AdminController;
use Exception;
use think\facade\Db;
use think\Request;
use think\Response;

/**
 * Rule 权限规则管理
 */
class Rule extends AdminController
{
    /**
     * tree 按 scope 返回树
     *
     * @return Response
     */
    public function tree(Request $request): Response
    {
        $scope = (string) $request->get('scope', '');
        if (!in_array($scope, ['backend', 'frontend'], true)) {
            return $this->fail(400, 'scope 无效');
        }
        $rows = RuleModel::where('scope', $scope)
            ->order('sort', 'asc')
            ->order('id', 'asc')
            ->select()
            ->toArray();
        $tree = $this->buildTree($rows, 0);

        return $this->ok(['list' => $tree]);
    }

    /**
     * read 单条规则；前端 view/btn 附带已绑定的 backend_rule_id 列表（字段名 backend_rule_ids）
     *
     * @return Response
     */
    public function read(Request $request): Response
    {
        $id = (int) $request->get('id', 0);
        if ($id <= 0) {
            return $this->fail(400, 'id 无效');
        }
        $row = RuleModel::find($id);
        if (!$row) {
            return $this->fail(404, '不存在');
        }
        $data = $row->toArray();
        if ($data['scope'] === 'frontend' && in_array($data['type'], ['view', 'btn'], true)) {
            $data['backend_rule_ids'] = RuleFrontendApi::where('frontend_rule_id', $id)->column('backend_rule_id');
            $data['backend_rule_ids'] = array_map('intval', $data['backend_rule_ids']);
        }

        return $this->ok($data);
    }

    /**
     * save 新建规则；frontend view/btn 可同时提交 backend_rule_ids 绑定后端 api
     *
     * @return Response
     */
    public function save(Request $request): Response
    {
        if (!$request->isPost()) {
            return $this->fail(405, '仅支持 POST');
        }
        $payload = $request->post();
        $err     = $this->validatePayload($payload, true);
        if ($err !== null) {
            return $this->fail(400, $err);
        }
        $scope = (string) ($payload['scope'] ?? '');
        $type  = (string) ($payload['type'] ?? '');
        try {
            $id = (int) Db::transaction(function () use ($payload, $scope, $type) {
                $m = new RuleModel();
                $m->save($this->normalizePayload($payload));
                $id = (int) $m->getAttr('id');
                if ($scope === 'frontend' && in_array($type, ['view', 'btn'], true) && array_key_exists('backend_rule_ids', $payload)) {
                    $bindErr = $this->syncBackendApis($id, $payload['backend_rule_ids']);
                    if ($bindErr !== null) {
                        throw new Exception($bindErr);
                    }
                }

                return $id;
            });
        } catch (Exception $e) {
            return $this->fail(400, $e->getMessage());
        }
        if ($scope === 'frontend') {
            MenuService::forgetAllRoleMenuCaches();
        }
        if ($scope === 'backend') {
            AdminRbacService::forgetAllRoleRbacCaches();
        }

        return $this->ok(['id' => $id]);
    }

    /**
     * update 更新规则；frontend view/btn 可同时提交 backend_rule_ids
     *
     * @return Response
     */
    public function update(Request $request): Response
    {
        if (!$request->isPut()) {
            return $this->fail(405, '仅支持 PUT');
        }
        $id = (int) $request->get('id', 0);
        if ($id <= 0) {
            return $this->fail(400, 'id 无效');
        }
        $row = RuleModel::find($id);
        if (!$row) {
            return $this->fail(404, '不存在');
        }
        $payload = array_merge($row->toArray(), $request->put());
        $err     = $this->validatePayload($payload, false);
        if ($err !== null) {
            return $this->fail(400, $err);
        }
        $oldScope = (string) $row->getAttr('scope');
        try {
            Db::transaction(function () use ($row, $payload, $id) {
                $row->save($this->normalizePayload($payload));
                $newScope = (string) $row->getAttr('scope');
                $newType  = (string) $row->getAttr('type');
                if ($newScope === 'frontend') {
                    if (in_array($newType, ['view', 'btn'], true) && array_key_exists('backend_rule_ids', $payload)) {
                        $bindErr = $this->syncBackendApis($id, $payload['backend_rule_ids']);
                        if ($bindErr !== null) {
                            throw new Exception($bindErr);
                        }
                    } elseif ($newType === 'menu') {
                        RuleFrontendApi::where('frontend_rule_id', $id)->delete();
                        AdminRbacService::forgetAllRoleRbacCaches();
                    }
                }
            });
        } catch (Exception $e) {
            return $this->fail(400, $e->getMessage());
        }
        $newScope = (string) $row->getAttr('scope');
        if ($oldScope === 'frontend' || $newScope === 'frontend') {
            MenuService::forgetAllRoleMenuCaches();
        }
        if ($oldScope === 'backend' || $newScope === 'backend') {
            AdminRbacService::forgetAllRoleRbacCaches();
        }

        return $this->ok();
    }

    /**
     * delete 删除（有子节点禁止）
     *
     * @return Response
     */
    public function delete(Request $request): Response
    {
        if (!$request->isDelete()) {
            return $this->fail(405, '仅支持 DELETE');
        }
        $id = (int) $request->get('id', 0);
        if ($id <= 0) {
            return $this->fail(400, 'id 无效');
        }
        $row = RuleModel::find($id);
        if (!$row) {
            return $this->fail(404, '不存在');
        }
        $wasFrontend = ((string) $row->getAttr('scope')) === 'frontend';
        $hasChild = RuleModel::where('pid', $id)->count() > 0;
        if ($hasChild) {
            return $this->fail(400, '存在子节点，禁止删除');
        }
        Db::name('auth_rule_frontend_api')->where('frontend_rule_id', $id)->delete();
        Db::name('auth_rule_frontend_api')->where('backend_rule_id', $id)->delete();
        Db::name('auth_role_rule')->where('rule_id', $id)->delete();
        RuleModel::destroy($id);
        if ($wasFrontend) {
            MenuService::forgetAllRoleMenuCaches();
        }
        AdminRbacService::forgetAllRoleRbacCaches();

        return $this->ok();
    }

    /**
     * bindApis 为前端 view/btn 绑定后端 api 规则
     *
     * @return Response
     */
    public function bindApis(Request $request): Response
    {
        if (!$request->isPost()) {
            return $this->fail(405, '仅支持 POST');
        }
        $fid = (int) $request->post('frontend_rule_id', 0);
        if ($fid <= 0) {
            return $this->fail(400, '参数无效');
        }
        $rawIds = $request->post('backend_rule_ids/a', []);
        try {
            $err = Db::transaction(function () use ($fid, $rawIds) {
                return $this->syncBackendApis($fid, $rawIds);
            });
        } catch (Exception $e) {
            return $this->fail(400, $e->getMessage());
        }
        if ($err !== null) {
            return $this->fail(400, $err);
        }

        return $this->ok();
    }

    /**
     * syncBackendApis 同步前端 view/btn 与后端 api 的绑定关系
     *
     * @param  mixed $rawIds
     */
    private function syncBackendApis(int $fid, mixed $rawIds): ?string
    {
        $rawIds = is_array($rawIds) ? array_values(array_unique(array_map('intval', $rawIds))) : [];
        $front  = RuleModel::where([['id', '=', $fid], ['scope', '=', 'frontend'], ['type', 'in', ['view', 'btn']]])->find();
        if (!$front) {
            return '数据不存在，仅允许对前端 view/btn 绑定接口';
        }
        if ($rawIds === []) {
            $apiIds = [];
        } else {
            $apiIds = RuleModel::where([['id', 'in', $rawIds], ['scope', '=', 'backend'], ['type', '=', 'api']])->column('id');
        }
        $insert = [];
        foreach ($apiIds as $bid) {
            $insert[] = [
                'frontend_rule_id' => $fid,
                'backend_rule_id'  => $bid,
            ];
        }
        RuleFrontendApi::where('frontend_rule_id', $fid)->delete();
        if (count($insert) > 0) {
            RuleFrontendApi::insertAll($insert);
        }
        AdminRbacService::forgetAllRoleRbacCaches();

        return null;
    }

    /**
     * apiList 扁平列出后端 api 规则（供多选）
     *
     * @return Response
     */
    public function apiList(): Response
    {
        $list = RuleModel::where('scope', 'backend')
            ->where('type', 'api')
            ->where('status', 1)
            ->order('sort', 'asc')
            ->order('id', 'asc')
            ->field(['id', 'pid', 'name', 'path', 'method', 'sort'])
            ->select()
            ->toArray();

        return $this->ok(['list' => $list]);
    }

    /**
     * validatePayload 校验 scope/type 组合
     *
     * @param  array<string,mixed> $p
     */
    private function validatePayload(array $p, bool $isCreate): ?string
    {
        $scope = (string) ($p['scope'] ?? '');
        $type  = (string) ($p['type'] ?? '');
        if (!in_array($scope, ['backend', 'frontend'], true)) {
            return 'scope 无效';
        }
        if ($scope === 'backend' && !in_array($type, ['menu', 'api'], true)) {
            return 'backend 下 type 仅支持 menu、api';
        }
        if ($scope === 'frontend' && !in_array($type, ['menu', 'view', 'btn'], true)) {
            return 'frontend 下 type 仅支持 menu、view、btn';
        }
        if ($type === 'api' && $scope !== 'backend') {
            return 'api 仅属于 backend';
        }
        if (in_array($type, ['view', 'btn'], true) && $scope !== 'frontend') {
            return 'view/btn 仅属于 frontend';
        }
        if (($p['name'] ?? '') === '') {
            return '名称必填';
        }

        return null;
    }

    /**
     * normalizePayload 写入字段白名单
     *
     * @param  array<string,mixed> $p
     * @return array<string,mixed>
     */
    private function normalizePayload(array $p): array
    {
        return [
            'scope'     => (string) $p['scope'],
            'type'      => (string) $p['type'],
            'pid'       => (int) ($p['pid'] ?? 0),
            'name'      => (string) ($p['name'] ?? ''),
            'path'      => $p['path'] ?? null,
            'method'    => $p['method'] ?? null,
            'component' => $p['component'] ?? null,
            'icon'      => $p['icon'] ?? null,
            'sort'      => (int) ($p['sort'] ?? 0),
            'status'    => (int) ($p['status'] ?? 1),
        ];
    }

    /**
     * buildTree 组装树
     *
     * @param  list<array<string,mixed>> $rows
     * @return list<array<string,mixed>>
     */
    private function buildTree(array $rows, int $pid): array
    {
        $out = [];
        foreach ($rows as $r) {
            if ((int) $r['pid'] !== $pid) {
                continue;
            }
            $r['children'] = $this->buildTree($rows, (int) $r['id']);
            $out[]         = $r;
        }

        return $out;
    }
}
