<?php
declare (strict_types = 1);

namespace app\controller\auth;

use app\BaseController;
use app\model\auth\Rule as RuleModel;
use think\facade\Db;
use think\Request;
use think\Response;

/**
 * Rule 权限规则管理
 */
class Rule extends BaseController
{
    /**
     * tree 按 scope 返回树
     *
     * @return Response
     */
    public function tree(Request $request): Response
    {
        $scope = (string) $request->get('scope', 'backend');
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
     * read 单条规则；前端 view/btn 附带已绑定的 backend api id 列表
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
            $data['api_ids'] = Db::name('auth_rule_frontend_api')
                ->where('frontend_rule_id', $id)
                ->column('backend_rule_id');
            $data['api_ids'] = array_map('intval', $data['api_ids']);
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
        $payload = array_merge($request->post(), $this->parseJsonBody($request));
        $err     = $this->validatePayload($payload, true);
        if ($err !== null) {
            return json(['code' => 400, 'msg' => $err, 'data' => null]);
        }
        $m = new RuleModel();
        $m->save($this->normalizePayload($payload));

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
        $payload = array_merge($row->toArray(), $this->parseJsonBody($request), $request->put());
        $err     = $this->validatePayload($payload, false);
        if ($err !== null) {
            return json(['code' => 400, 'msg' => $err, 'data' => null]);
        }
        $row->save($this->normalizePayload($payload));

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
        $hasChild = RuleModel::where('pid', $id)->count() > 0;
        if ($hasChild) {
            return json(['code' => 400, 'msg' => '存在子节点，禁止删除', 'data' => null]);
        }
        Db::name('auth_rule_frontend_api')->where('frontend_rule_id', $id)->delete();
        Db::name('auth_rule_frontend_api')->where('backend_rule_id', $id)->delete();
        Db::name('auth_role_rule')->where('rule_id', $id)->delete();
        RuleModel::destroy($id);

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
        $post = array_merge($request->post(), $this->parseJsonBody($request));
        $fid    = (int) ($post['frontend_rule_id'] ?? 0);
        $apiIds = $post['api_rule_ids'] ?? [];
        if ($fid <= 0 || !is_array($apiIds)) {
            return json(['code' => 400, 'msg' => '参数无效', 'data' => null]);
        }
        $apiIds = array_map('intval', $apiIds);
        $front  = RuleModel::find($fid);
        if (!$front || $front->getAttr('scope') !== 'frontend' || !in_array($front->getAttr('type'), ['view', 'btn'], true)) {
            return json(['code' => 400, 'msg' => '仅允许对前端 view/btn 绑定接口', 'data' => null]);
        }
        foreach ($apiIds as $bid) {
            if ($bid <= 0) {
                return json(['code' => 400, 'msg' => 'api_rule_ids 含无效项', 'data' => null]);
            }
            $api = RuleModel::find($bid);
            if (!$api || $api->getAttr('scope') !== 'backend' || $api->getAttr('type') !== 'api') {
                return json(['code' => 400, 'msg' => '仅允许绑定后端 api 类型规则', 'data' => null]);
            }
        }
        Db::name('auth_rule_frontend_api')->where('frontend_rule_id', $fid)->delete();
        foreach (array_unique($apiIds) as $bid) {
            Db::name('auth_rule_frontend_api')->insert([
                'frontend_rule_id' => $fid,
                'backend_rule_id'  => $bid,
            ]);
        }

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
     * parseJsonBody 读取 application/json 请求体
     *
     * @return array<string,mixed>
     */
    private function parseJsonBody(Request $request): array
    {
        $ct = (string) $request->header('Content-Type', '');
        if (!str_contains($ct, 'application/json')) {
            return [];
        }
        $raw = (string) $request->getContent();
        if ($raw === '') {
            return [];
        }
        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
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
            'path'      => $p['path'] !== null && $p['path'] !== '' ? (string) $p['path'] : null,
            'method'    => $p['method'] !== null && $p['method'] !== '' ? (string) $p['method'] : null,
            'component' => $p['component'] !== null && $p['component'] !== '' ? (string) $p['component'] : null,
            'icon'      => $p['icon'] !== null && $p['icon'] !== '' ? (string) $p['icon'] : null,
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
