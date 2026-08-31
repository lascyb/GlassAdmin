<?php

declare(strict_types=1);

namespace app\admin\controller\system\auth;

use app\admin\model\system\auth\Role as RoleModel;
use app\admin\model\system\auth\RoleRule;
use app\admin\model\system\auth\Rule as RuleModel;
use app\admin\model\system\auth\RuleApi;
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
     * sort 批量更新规则排序；items 可带 pid 以改层级
     *
     * @return Response
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function sort(Request $request): Response
    {
        if (!$request->isPut()) {
            return $this->fail(405, '仅支持 PUT');
        }
        $items = $request->put('items/a', []);
        if (!is_array($items) || $items === []) {
            return $this->fail(400, 'items 无效');
        }
        if (count($items) > 2000) {
            return $this->fail(400, 'items 过多');
        }
        $pairs = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                return $this->fail(400, 'items 无效');
            }
            $id = (int) ($item['id'] ?? 0);
            if ($id <= 0) {
                return $this->fail(400, 'id 无效');
            }
            if (!array_key_exists('sort', $item)) {
                return $this->fail(400, 'sort 无效');
            }
            if (isset($pairs[$id])) {
                return $this->fail(400, 'id 重复');
            }
            $pid = null;
            if (array_key_exists('pid', $item)) {
                $pid = (int) $item['pid'];
                if ($pid < 0) {
                    return $this->fail(400, 'pid 无效');
                }
            }
            $pairs[$id] = [
                'sort' => (int) $item['sort'],
                'pid'  => $pid,
            ];
        }
        $ids  = array_keys($pairs);
        $rows = RuleModel::whereIn('id', $ids)->select();
        if (count($rows) !== count($ids)) {
            return $this->fail(404, '部分规则不存在');
        }
        $forgetFrontend = false;
        $forgetBackend  = false;
        try {
            Db::transaction(function () use ($rows, $pairs, &$forgetFrontend, &$forgetBackend) {
                foreach ($rows as $row) {
                    $id    = (int) $row->getAttr('id');
                    $patch = ['sort' => $pairs[$id]['sort']];
                    $newPid = $pairs[$id]['pid'];
                    if ($newPid !== null) {
                        $err = $this->validateMovePid($row, $newPid);
                        if ($err !== null) {
                            throw new Exception($err);
                        }
                        $patch['pid'] = $newPid;
                    }
                    $row->save($patch);
                    $scope = (string) $row->getAttr('scope');
                    if ($scope === 'frontend') {
                        $forgetFrontend = true;
                    }
                    if ($scope === 'backend') {
                        $forgetBackend = true;
                    }
                }
            });
        } catch (Exception $e) {
            return $this->fail(400, $e->getMessage());
        }
        if ($forgetFrontend) {
            RuleModel::forgetMenuCaches();
        }
        if ($forgetBackend) {
            AdminRbacService::forgetAllRoleRbacCaches();
            AdminOperationLogService::forgetApiNameCache();
        }

        return $this->ok(null, '排序已保存');
    }

    /**
     * validateMovePid 校验改层级：父节点存在、同 scope、可收子项、无环
     */
    private function validateMovePid(RuleModel $row, int $newPid): ?string
    {
        $id = (int) $row->getAttr('id');
        if ($newPid === $id) {
            return '不能挂到自身下';
        }
        if ($newPid === 0) {
            return null;
        }
        $parent = RuleModel::find($newPid);
        if (!$parent) {
            return '父节点不存在';
        }
        if ((string) $parent->getAttr('scope') !== (string) $row->getAttr('scope')) {
            return '不能跨前后端移动';
        }
        $pType = (string) $parent->getAttr('type');
        if ((string) $parent->getAttr('scope') === 'frontend' && in_array($pType, ['link', 'iframe', 'btn'], true)) {
            return '该节点不能添加子项';
        }
        $cur   = $newPid;
        $guard = 0;
        while ($cur > 0 && $guard++ < 64) {
            if ($cur === $id) {
                return '不能挂到自己的子节点下';
            }
            $cur = (int) RuleModel::where('id', $cur)->value('pid');
        }

        return null;
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
            $ids = array_values(array_unique(array_filter(array_map('intval', $raw), static fn(int $x): bool => $x > 0)));
            if ($ids !== []) {
                return $ids;
            }
        }
        if (is_string($raw) && $raw !== '') {
            $ids = array_values(array_unique(array_filter(array_map('intval', explode(',', $raw)), static fn(int $x): bool => $x > 0)));
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
     * roles 某条前端规则已分配的角色 id，并附带角色树供勾选
     *
     * @return Response
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function roles(Request $request): Response
    {
        $id = (int) $request->get('id', 0);
        if ($id <= 0) {
            return $this->fail(400, 'id 无效');
        }
        $row = RuleModel::find($id);
        if (!$row) {
            return $this->fail(404, '不存在');
        }
        if ((string) $row->getAttr('scope') !== 'frontend') {
            return $this->fail(400, '仅支持前端规则');
        }
        $roleIds = array_map('intval', RoleRule::where('rule_id', $id)->column('role_id'));
        $rows    = RoleModel::order('sort', 'asc')
            ->order('id', 'asc')
            ->field(['id', 'pid', 'code', 'name', 'status', 'sort'])
            ->select()
            ->toArray();

        return $this->ok([
            'role_ids' => $roleIds,
            'roles'    => $this->buildTree($rows, 0),
        ]);
    }

    /**
     * assignRoles 全量覆盖某条前端规则与角色的关联（无父子继承语义）
     *
     * @return Response
     */
    public function assignRoles(Request $request): Response
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
        if ((string) $row->getAttr('scope') !== 'frontend') {
            return $this->fail(400, '仅支持前端规则');
        }
        $roleIds = $request->put('role_ids/a', []);
        $roleIds = is_array($roleIds) ? array_unique(array_map('intval', $roleIds)) : [];
        $roleIds = array_values(array_filter($roleIds, static fn(int $rid): bool => $rid > 0));
        if ($roleIds !== []) {
            $roleIds = array_map('intval', RoleModel::where([['id', 'in', $roleIds]])->column('id'));
        }
        $oldRoleIds = array_map('intval', RoleRule::where('rule_id', $id)->column('role_id'));
        $insert     = [];
        foreach ($roleIds as $rid) {
            $insert[] = ['role_id' => $rid, 'rule_id' => $id];
        }
        try {
            Db::transaction(function () use ($id, $insert) {
                RoleRule::where('rule_id', $id)->delete();
                if ($insert !== []) {
                    RoleRule::insertAll($insert);
                }
            });
        } catch (Exception $e) {
            return $this->fail(400, $e->getMessage());
        }

        RuleModel::forgetMenuCaches();
        foreach (array_values(array_unique(array_merge($oldRoleIds, $roleIds))) as $rid) {
            AdminRbacService::forgetRoleRbacCache($rid);
        }

        return $this->ok();
    }

    /**
     * frontends 某条后端 api 已绑定的前端规则 id，并附带前端规则树供勾选
     *
     * @return Response
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function frontends(Request $request): Response
    {
        $id = (int) $request->get('id', 0);
        if ($id <= 0) {
            return $this->fail(400, 'id 无效');
        }
        $row = RuleModel::find($id);
        if (!$row) {
            return $this->fail(404, '不存在');
        }
        if ((string) $row->getAttr('scope') !== 'backend' || (string) $row->getAttr('type') !== 'api') {
            return $this->fail(400, '仅支持后端 api 规则');
        }
        $frontendIds = array_map('intval', RuleApi::where('backend_rule_id', $id)->column('frontend_rule_id'));
        $rows        = RuleModel::where('scope', 'frontend')
            ->order('sort', 'asc')
            ->order('id', 'asc')
            ->field(['id', 'pid', 'name', 'type', 'path', 'icon', 'status', 'sort'])
            ->select()
            ->toArray();

        return $this->ok([
            'frontend_rule_ids' => $frontendIds,
            'rules'             => $this->buildTree($rows, 0),
        ]);
    }

    /**
     * assignFrontends 全量覆盖某条后端 api 与前端 view/route/btn 的绑定（不影响这些前端规则的其它 api）
     *
     * @return Response
     */
    public function assignFrontends(Request $request): Response
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
        if ((string) $row->getAttr('scope') !== 'backend' || (string) $row->getAttr('type') !== 'api') {
            return $this->fail(400, '仅支持后端 api 规则');
        }
        $frontIds = $request->put('frontend_rule_ids/a', []);
        $frontIds = is_array($frontIds) ? array_unique(array_map('intval', $frontIds)) : [];
        $frontIds = array_values(array_filter($frontIds, static fn(int $fid): bool => $fid > 0));
        if ($frontIds !== []) {
            $frontIds = array_map(
                'intval',
                RuleModel::where([
                    ['id', 'in', $frontIds],
                    ['scope', '=', 'frontend'],
                    ['type', 'in', RuleModel::API_BINDABLE_TYPES],
                ])->column('id'),
            );
        }
        $insert = [];
        foreach ($frontIds as $fid) {
            $insert[] = ['frontend_rule_id' => $fid, 'backend_rule_id' => $id];
        }
        try {
            Db::transaction(function () use ($id, $insert) {
                RuleApi::where('backend_rule_id', $id)->delete();
                if ($insert !== []) {
                    RuleApi::insertAll($insert);
                }
            });
        } catch (Exception $e) {
            return $this->fail(400, $e->getMessage());
        }
        AdminRbacService::forgetAllRoleRbacCaches();

        return $this->ok();
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
            if (
                in_array($vis, RuleModel::VISIBILITIES, true)
                && ($vis !== RuleModel::VISIBILITY_HIDDEN || in_array($type, RuleModel::HIDDEN_TYPES, true))
            ) {
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
