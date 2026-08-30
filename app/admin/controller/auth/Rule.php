<?php
declare (strict_types = 1);

namespace app\admin\controller\auth;

use app\admin\model\auth\RoleRule;
use app\admin\model\auth\Rule as RuleModel;
use app\admin\model\auth\RuleApi;
use app\admin\service\AdminOperationLogService;
use app\admin\service\AdminRbacService;
use app\common\controller\AdminController;
use Exception;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
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
     * @param Request $request
     * @return Response
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
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
     * read 单条规则；前端 view/route/btn 附带已绑定的 backend_rule_id 列表（字段名 backend_rule_ids）
     *
     * @param Request $request
     * @return Response
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
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
        if ($data['scope'] === 'frontend' && in_array($data['type'], RuleModel::API_BINDABLE_TYPES, true)) {
            $data['backend_rule_ids'] = RuleApi::where('frontend_rule_id', $id)->column('backend_rule_id');
            $data['backend_rule_ids'] = array_map('intval', $data['backend_rule_ids']);
        }

        return $this->ok($data);
    }

    /**
     * save 新建规则；frontend view/route/btn 可同时提交 backend_rule_ids 绑定后端 api
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
                if ($scope === 'frontend' && in_array($type, RuleModel::API_BINDABLE_TYPES, true) && array_key_exists('backend_rule_ids', $payload)) {
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
            RuleModel::forgetMenuCaches();
        }
        if ($scope === 'backend') {
            AdminRbacService::forgetAllRoleRbacCaches();
            AdminOperationLogService::forgetApiNameCache();
        }

        return $this->ok(['id' => $id]);
    }

    /**
     * update 更新规则；frontend view/route/btn 可同时提交 backend_rule_ids
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
                    if (in_array($newType, RuleModel::API_BINDABLE_TYPES, true) && array_key_exists('backend_rule_ids', $payload)) {
                        $bindErr = $this->syncBackendApis($id, $payload['backend_rule_ids']);
                        if ($bindErr !== null) {
                            throw new Exception($bindErr);
                        }
                    } elseif ($newType === 'menu') {
                        RuleApi::where('frontend_rule_id', $id)->delete();
                        AdminRbacService::forgetAllRoleRbacCaches();
                    }
                }
            });
        } catch (Exception $e) {
            return $this->fail(400, $e->getMessage());
        }
        $newScope = (string) $row->getAttr('scope');
        if ($oldScope === 'frontend' || $newScope === 'frontend') {
            RuleModel::forgetMenuCaches();
        }
        if ($oldScope === 'backend' || $newScope === 'backend') {
            AdminRbacService::forgetAllRoleRbacCaches();
            AdminOperationLogService::forgetApiNameCache();
        }

        return $this->ok();
    }

    /**
     * delete 删除（有子节点禁止）；兼容单 id 与 ids 批量
     *
     * @return Response
     */
    public function delete(Request $request): Response
    {
        if (!$request->isDelete()) {
            return $this->fail(405, '仅支持 DELETE');
        }
        $ids = $this->parseDeleteIds($request);
        if ($ids === []) {
            return $this->fail(400, 'id 无效');
        }

        $rows = RuleModel::whereIn('id', $ids)->select();
        if (count($rows) !== count($ids)) {
            return $this->fail(404, '部分规则不存在');
        }

        foreach ($rows as $row) {
            $id = (int) $row->getAttr('id');
            $hasChild = RuleModel::where('pid', $id)->whereNotIn('id', $ids)->count() > 0;
            if ($hasChild) {
                return $this->fail(400, '「' . (string) $row->getAttr('name') . '」存在子节点，禁止删除');
            }
        }

        $sortedIds = $this->sortDeleteIds($ids);
        $forgetFrontendMenu = false;
        try {
            Db::transaction(function () use ($sortedIds, $rows, &$forgetFrontendMenu) {
                foreach ($rows as $row) {
                    if ((string) $row->getAttr('scope') === 'frontend') {
                        $forgetFrontendMenu = true;
                    }
                }
                foreach ($sortedIds as $id) {
                    $this->destroyRule($id);
                }
            });
        } catch (Exception $e) {
            return $this->fail(400, $e->getMessage());
        }

        if ($forgetFrontendMenu) {
            RuleModel::forgetMenuCaches();
        }
        AdminRbacService::forgetAllRoleRbacCaches();
        AdminOperationLogService::forgetApiNameCache();

        return $this->ok(['deleted' => count($sortedIds)]);
    }

    /**
     * parseDeleteIds 解析删除 id（id 单条或 ids 批量，ids 支持数组与逗号分隔）
     *
     * @return list<int>
     */
    private function parseDeleteIds(Request $request): array
    {
        $raw = $request->param('ids');
        if (is_array($raw)) {
            $ids = array_values(array_unique(array_filter(array_map('intval', $raw), static fn (int $x): bool => $x > 0)));
            if ($ids !== []) {
                return $ids;
            }
        }
        if (is_string($raw) && $raw !== '') {
            $ids = array_values(array_unique(array_filter(array_map('intval', explode(',', $raw)), static fn (int $x): bool => $x > 0)));
            if ($ids !== []) {
                return $ids;
            }
        }
        $id = (int) $request->param('id', 0);

        return $id > 0 ? [$id] : [];
    }

    /**
     * sortDeleteIds 子节点优先排序，便于同一批内删父子
     *
     * @param list<int> $ids
     * @return list<int>
     */
    private function sortDeleteIds(array $ids): array
    {
        $remaining = array_fill_keys($ids, true);
        $sorted    = [];
        while ($remaining !== []) {
            $progress = false;
            foreach (array_keys($remaining) as $id) {
                $hasChildInBatch = RuleModel::where('pid', $id)->whereIn('id', array_keys($remaining))->count() > 0;
                if (!$hasChildInBatch) {
                    $sorted[] = $id;
                    unset($remaining[$id]);
                    $progress = true;
                }
            }
            if (!$progress) {
                break;
            }
        }

        return $sorted !== [] ? $sorted : $ids;
    }

    /** destroyRule 删除单条规则及关联 */
    private function destroyRule(int $id): void
    {
        RuleApi::where('frontend_rule_id', $id)->delete();
        RuleApi::where('backend_rule_id', $id)->delete();
        RoleRule::where('rule_id', $id)->delete();
        RuleModel::destroy($id);
    }

    /**
     * bindApis 为前端 view/route/btn 绑定后端 api 规则
     *
     * @param Request $request
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
     * syncBackendApis 同步前端 view/route/btn 与后端 api 的绑定关系
     *
     * @param int $fid
     * @param mixed $rawIds
     * @return string|null
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    private function syncBackendApis(int $fid, mixed $rawIds): ?string
    {
        $rawIds = is_array($rawIds) ? array_values(array_unique(array_map('intval', $rawIds))) : [];
        $front  = RuleModel::where([['id', '=', $fid], ['scope', '=', 'frontend'], ['type', 'in', RuleModel::API_BINDABLE_TYPES]])->find();
        if (!$front) {
            return '数据不存在，仅允许对前端 view/route/btn 绑定接口';
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
        RuleApi::where('frontend_rule_id', $fid)->delete();
        if (count($insert) > 0) {
            RuleApi::insertAll($insert);
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
        if ($scope === 'frontend' && !in_array($type, ['menu', 'view', 'route', 'btn', 'link', 'iframe'], true)) {
            return 'frontend 下 type 仅支持 menu、view、route、btn、link、iframe';
        }
        if ($type === 'api' && $scope !== 'backend') {
            return 'api 仅属于 backend';
        }
        if (in_array($type, ['view', 'route', 'btn', 'link', 'iframe'], true) && $scope !== 'frontend') {
            return 'view/route/btn/link/iframe 仅属于 frontend';
        }
        if (in_array($type, ['link', 'iframe'], true) && trim((string) ($p['path'] ?? '')) === '') {
            return '外链/内嵌须填写路由 path';
        }
        if (in_array($type, ['link', 'iframe'], true) && trim((string) ($p['component'] ?? '')) === '') {
            return '外链/内嵌须填写目标地址';
        }
        if (($p['name'] ?? '') === '') {
            return '名称必填';
        }
        if ($scope === 'frontend' && in_array($type, RuleModel::VISIBILITY_TYPES, true)) {
            $vis = (int) ($p['visibility'] ?? RuleModel::VISIBILITY_SIDEBAR);
            if (!in_array($vis, RuleModel::VISIBILITIES, true)) {
                return 'visibility 无效';
            }
            if ($vis === RuleModel::VISIBILITY_HIDDEN && !in_array($type, RuleModel::HIDDEN_TYPES, true)) {
                return '仅目录可设为不入侧栏；页面请使用 route 类型';
            }
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
        $scope = (string) $p['scope'];
        $type  = (string) $p['type'];
        $showVisibility = $scope === 'frontend' && in_array($type, RuleModel::VISIBILITY_TYPES, true);
        $visibility     = RuleModel::VISIBILITY_SIDEBAR;
        if ($showVisibility) {
            $vis = (int) ($p['visibility'] ?? RuleModel::VISIBILITY_SIDEBAR);
            if (in_array($vis, RuleModel::VISIBILITIES, true)
                && ($vis !== RuleModel::VISIBILITY_HIDDEN || in_array($type, RuleModel::HIDDEN_TYPES, true))) {
                $visibility = $vis;
            }
        }
        $keepAlive = 0;
        if ($scope === 'frontend' && in_array($type, RuleModel::KEEP_ALIVE_TYPES, true)) {
            $keepAlive = (int) ($p['keep_alive'] ?? 1) === 0 ? 0 : 1;
        }

        return [
            'scope'       => $scope,
            'type'        => $type,
            'pid'         => (int) ($p['pid'] ?? 0),
            'name'        => (string) ($p['name'] ?? ''),
            'path'        => $p['path'] ?? null,
            'method'      => self::normalizeMethod($scope, $type, $p['method'] ?? null),
            'component'   => $p['component'] ?? null,
            'icon'        => $p['icon'] ?? null,
            'sort'        => (int) ($p['sort'] ?? 0),
            'status'      => (int) ($p['status'] ?? 1),
            'visibility'  => $visibility,
            'keep_alive'  => $keepAlive,
            'description' => $showVisibility && isset($p['description']) && trim((string) $p['description']) !== ''
                ? mb_substr(trim((string) $p['description']), 0, 500)
                : null,
        ];
    }

    /**
     * normalizeMethod backend api 保留 HTTP method；frontend iframe 为鉴权策略 ID
     */
    private static function normalizeMethod(string $scope, string $type, mixed $raw): ?string
    {
        if ($scope === 'backend' && $type === 'api') {
            $m = trim((string) ($raw ?? ''));

            return $m !== '' ? $m : null;
        }
        if ($scope === 'frontend' && $type === 'iframe') {
            $m = strtolower(trim((string) ($raw ?? '')));
            if ($m !== '' && preg_match('/^[a-z][a-z0-9-]{0,63}$/', $m) === 1) {
                return $m;
            }
        }

        return null;
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
