<?php
declare (strict_types = 1);

namespace app\controller\auth;

use app\AuthController;
use app\model\auth\Rule as RuleModel;
use app\model\auth\RuleFrontendApi;
use app\service\AdminRbacService;
use app\service\MenuService;
use Exception;
use think\facade\Db;
use think\Request;
use think\Response;

/**
 * Rule 权限规则管理
 */
class Rule extends AuthController
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
            return json(['code' => 400, 'msg' => 'scope 无效', 'data' => null]);
        }
        $rows = RuleModel::where('scope', $scope)
            ->order('sort', 'asc')
            ->order('id', 'asc')
            ->select()
            ->toArray();
        $tree = $this->buildTree($rows, 0);

        return json(['code' => 0, 'msg' => 'ok', 'data' => ['list' => $tree]]);
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
            return json(['code' => 400, 'msg' => 'id 无效', 'data' => null]);
        }
        $row = RuleModel::find($id);
        if (!$row) {
            return json(['code' => 404, 'msg' => '不存在', 'data' => null]);
        }
        $data = $row->toArray();
        if ($data['scope'] === 'frontend' && in_array($data['type'], ['view', 'btn'], true)) {
            $data['backend_rule_ids'] = RuleFrontendApi::where('frontend_rule_id', $id)->column('backend_rule_id');
            $data['backend_rule_ids'] = array_map('intval', $data['backend_rule_ids']);
        }

        return json(['code' => 0, 'msg' => 'ok', 'data' => $data]);
    }

    /**
     * save 新建规则
     *
     * @return Response
     */
    public function save(Request $request): Response
    {
        if (!$request->isPost()) {
            return json(['code' => 405, 'msg' => '仅支持 POST', 'data' => null]);
        }
        $payload = $request->post();
        $err     = $this->validatePayload($payload, true);
        if ($err !== null) {
            return json(['code' => 400, 'msg' => $err, 'data' => null]);
        }
        $m = new RuleModel();
        $m->save($this->normalizePayload($payload));
        if ((string) ($payload['scope'] ?? '') === 'frontend') {
            MenuService::forgetAllRoleMenuCaches();
        }
        if ((string) ($payload['scope'] ?? '') === 'backend') {
            AdminRbacService::forgetAllRoleRbacCaches();
        }

        return json(['code' => 0, 'msg' => 'ok', 'data' => ['id' => (int) $m->getAttr('id')]]);
    }

    /**
     * update 更新规则
     *
     * @return Response
     */
    public function update(Request $request): Response
    {
        if (!$request->isPut()) {
            return json(['code' => 405, 'msg' => '仅支持 PUT', 'data' => null]);
        }
        $id = (int) $request->get('id', 0);
        if ($id <= 0) {
            return json(['code' => 400, 'msg' => 'id 无效', 'data' => null]);
        }
        $row = RuleModel::find($id);
        if (!$row) {
            return json(['code' => 404, 'msg' => '不存在', 'data' => null]);
        }
        $payload = array_merge($row->toArray(), $request->put());
        $err     = $this->validatePayload($payload, false);
        if ($err !== null) {
            return json(['code' => 400, 'msg' => $err, 'data' => null]);
        }
        $oldScope = (string) $row->getAttr('scope');
        $row->save($this->normalizePayload($payload));
        $newScope = (string) $row->getAttr('scope');
        if ($oldScope === 'frontend' || $newScope === 'frontend') {
            MenuService::forgetAllRoleMenuCaches();
        }
        if ($oldScope === 'backend' || $newScope === 'backend') {
            AdminRbacService::forgetAllRoleRbacCaches();
        }

        return json(['code' => 0, 'msg' => 'ok', 'data' => null]);
    }

    /**
     * delete 删除（有子节点禁止）
     *
     * @return Response
     */
    public function delete(Request $request): Response
    {
        if (!$request->isDelete()) {
            return json(['code' => 405, 'msg' => '仅支持 DELETE', 'data' => null]);
        }
        $id = (int) $request->get('id', 0);
        if ($id <= 0) {
            return json(['code' => 400, 'msg' => 'id 无效', 'data' => null]);
        }
        $row = RuleModel::find($id);
        if (!$row) {
            return json(['code' => 404, 'msg' => '不存在', 'data' => null]);
        }
        $wasFrontend = ((string) $row->getAttr('scope')) === 'frontend';
        $hasChild = RuleModel::where('pid', $id)->count() > 0;
        if ($hasChild) {
            return json(['code' => 400, 'msg' => '存在子节点，禁止删除', 'data' => null]);
        }
        Db::name('auth_rule_frontend_api')->where('frontend_rule_id', $id)->delete();
        Db::name('auth_rule_frontend_api')->where('backend_rule_id', $id)->delete();
        Db::name('auth_role_rule')->where('rule_id', $id)->delete();
        RuleModel::destroy($id);
        if ($wasFrontend) {
            MenuService::forgetAllRoleMenuCaches();
        }
        AdminRbacService::forgetAllRoleRbacCaches();

        return json(['code' => 0, 'msg' => 'ok', 'data' => null]);
    }

    /**
     * bindApis 为前端 view/btn 绑定后端 api 规则
     *
     * @return Response
     */
    public function bindApis(Request $request): Response
    {
        if (!$request->isPost()) {
            return json(['code' => 405, 'msg' => '仅支持 POST', 'data' => null]);
        }
        $rawIds = $request->post('backend_rule_ids/a', []);
        $rawIds = is_array($rawIds) ? array_values(array_unique(array_map('intval', $rawIds))) : [];
        $fid = (int) $request->post('frontend_rule_id', 0);
        if ($fid <= 0) {
            return json(['code' => 400, 'msg' => '参数无效', 'data' => null]);
        }

        $front = RuleModel::where([['id', '=', $fid], ['scope', '=', 'frontend'], ['type', 'in', ['view', 'btn']]])->find();
        if (!$front) {
            return json(['code' => 400, 'msg' => '数据不存在，仅允许对前端 view/btn 绑定接口', 'data' => null]);
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
        try {
            RuleFrontendApi::where('frontend_rule_id', $fid)->delete();
            if (count($insert)>0){
                RuleFrontendApi::insertAll($insert);
            }
        }catch (Exception $exception){
            return json(['code' => 400, 'msg' => $exception->getMessage(), 'data' => null]);
        }
        AdminRbacService::forgetAllRoleRbacCaches();

        return json(['code' => 0, 'msg' => 'ok', 'data' => null]);
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

        return json(['code' => 0, 'msg' => 'ok', 'data' => ['list' => $list]]);
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
