<?php
declare (strict_types = 1);

namespace crud;

use crud\field\FieldMeta;
use crud\field\Naming;
use app\admin\model\system\auth\Rule;
use app\admin\model\system\auth\RuleApi;
use crud\Context;
use think\db\exception\DbException;

/**
 * AuthInstaller 将权限/菜单直接写入数据库（不挂角色、不生成 SQL 文件）
 *
 * 对齐 example 金样：
 * - backend menu 链（与 menus 同级，view 对应接口权限组）+ api：list/read/create/update/delete（+upload）
 * - frontend menu 链（menus 按 enabled 过滤，pid 逐级挂接）+ view + btn(add/edit/del)
 * - system_auth_rule_api 绑定
 *
 * 若 frontend view 已存在：更新 view/btn 字段，并覆盖重建该 view 子树的 frontend_api 绑定。
 * 远程关联 list API：已存在则只绑定不改 pid；不存在则挂到远程模块自身的接口组下。
 */
class AuthInstaller
{
    /**
     * install 写入权限规则
     *
     * @param Context $ctx
     * @return array{created: list<string>, updated: list<string>, reused: list<string>, bindings: int, bindingsCleared: int}
     * @throws DbException
     */
    public function install(Context $ctx): array
    {
        $created = [];
        $updated = [];
        $reused = [];
        $now = date('Y-m-d H:i:s');

        $backMenuId = 0;
        $menuChain = $ctx->resolvedMenuChain();
        $pathTrail = [];
        $apiGroupId = 0;

        foreach ($menuChain as $i => $menu) {
            $path = (string) $menu['path'];
            $pathTrail[] = $path;
            $pid = $backMenuId > 0 ? $backMenuId : 1;
            $type = (string) ($menu['type'] ?? 'menu');
            $sort = isset($menu['sort']) ? (int) $menu['sort'] : 0;

            if ($type === 'view') {
                $apiGroupName = $ctx->title . '接口';
                $apiGroupId = $this->ensureRule(
                    [
                        'scope' => 'backend',
                        'type'  => 'menu',
                        'pid'   => $pid,
                        'name'  => $apiGroupName,
                    ],
                    [
                        'scope'       => 'backend',
                        'type'        => 'menu',
                        'pid'         => $pid,
                        'name'        => $apiGroupName,
                        'path'        => null,
                        'method'      => null,
                        'component'   => null,
                        'icon'        => null,
                        'sort'        => $sort,
                        'status'      => 1,
                        'create_time' => $now,
                        'update_time' => $now,
                    ],
                    'backend.menu:' . implode('/', $pathTrail) . '/api',
                    $created,
                    $updated,
                    $reused,
                    true
                );
            } else {
                $icon = $this->resolveMenuIcon($menu, false);
                $backMenuId = $this->ensureRule(
                    [
                        'scope' => 'backend',
                        'type'  => 'menu',
                        'pid'   => $pid,
                        'path'  => $path,
                    ],
                    [
                        'scope'       => 'backend',
                        'type'        => 'menu',
                        'pid'         => $pid,
                        'name'        => (string) $menu['title'],
                        'path'        => $path,
                        'method'      => null,
                        'component'   => null,
                        'icon'        => $icon,
                        'sort'        => $sort,
                        'status'      => 1,
                        'create_time' => $now,
                        'update_time' => $now,
                    ],
                    'backend.menu:' . implode('/', $pathTrail),
                    $created,
                    $updated,
                    $reused,
                    true
                );
            }
        }

        if ($apiGroupId <= 0) {
            throw new \RuntimeException('menus 中缺少启用的页面(view)，无法生成后端接口权限组');
        }

        $apis = [
            ['list', 'GET', "{$ctx->title}列表", 0],
            ['read', 'GET', "{$ctx->title}详情", 1],
            ['create', 'POST', "新增{$ctx->title}", 2],
            ['update', 'PUT', "更新{$ctx->title}", 3],
            ['delete', 'DELETE', "删除{$ctx->title}", 4],
        ];
        $sort = 5;
        $uploadActions = [];
        foreach ($ctx->imageFields as $f) {
            $action = Naming::uploadActionName((string) $f['prop']);
            $apis[] = [$action, 'POST', "上传{$f['label']}", $sort++];
            $uploadActions[] = $action;
        }

        $apiIds = [];
        foreach ($apis as [$action, $method, $name, $apiSort]) {
            $path = "{$ctx->apiBase}/{$action}";
            $apiIds[$action] = $this->ensureRule(
                [
                    'scope'  => 'backend',
                    'type'   => 'api',
                    'path'   => $path,
                    'method' => $method,
                ],
                [
                    'scope'       => 'backend',
                    'type'        => 'api',
                    'pid'         => $apiGroupId,
                    'name'        => $name,
                    'path'        => $path,
                    'method'      => $method,
                    'component'   => null,
                    'icon'        => null,
                    'sort'        => $apiSort,
                    'status'      => 1,
                    'create_time' => $now,
                    'update_time' => $now,
                ],
                "backend.api:{$path} {$method}",
                $created,
                $updated,
                $reused,
                true
            );
        }

        $frontMenuId = 0;
        $frontPathTrail = [];
        $frontViewId = 0;
        $viewExisted = false;

        foreach ($menuChain as $i => $menu) {
            $path = (string) $menu['path'];
            $frontPathTrail[] = $path;
            $pid = $frontMenuId;
            $type = (string) ($menu['type'] ?? 'menu');
            $sort = isset($menu['sort']) ? (int) $menu['sort'] : 0;

            if ($type === 'view') {
                $icon = $this->resolveMenuIcon($menu, true);
                $viewWhere = [
                    'scope' => 'frontend',
                    'type'  => 'view',
                    'pid'   => $pid,
                    'path'  => $path,
                ];
                $viewExisted = $this->findRuleId($viewWhere) > 0;
                $viewPathLabel = implode('/', $frontPathTrail);
                $component = $ctx->viewComponent();
                $frontViewId = $this->ensureRule(
                    $viewWhere,
                    [
                        'scope'       => 'frontend',
                        'type'        => 'view',
                        'pid'         => $pid,
                        'name'        => (string) $menu['title'],
                        'path'        => $path,
                        'method'      => null,
                        'component'   => $component,
                        'icon'        => $icon,
                        'sort'        => $sort,
                        'status'      => 1,
                        'create_time' => $now,
                        'update_time' => $now,
                    ],
                    "frontend.view:{$viewPathLabel}",
                    $created,
                    $updated,
                    $reused,
                    true
                );
                $frontMenuId = $frontViewId;
            } else {
                $icon = $this->resolveMenuIcon($menu, false);
                $frontMenuId = $this->ensureRule(
                    [
                        'scope' => 'frontend',
                        'type'  => 'menu',
                        'pid'   => $pid,
                        'path'  => $path,
                    ],
                    [
                        'scope'       => 'frontend',
                        'type'        => 'menu',
                        'pid'         => $pid,
                        'name'        => (string) $menu['title'],
                        'path'        => $path,
                        'method'      => null,
                        'component'   => null,
                        'icon'        => $icon,
                        'sort'        => $sort,
                        'status'      => 1,
                        'create_time' => $now,
                        'update_time' => $now,
                    ],
                    'frontend.menu:' . implode('/', $frontPathTrail),
                    $created,
                    $updated,
                    $reused,
                    true
                );
            }
        }

        if ($frontViewId <= 0) {
            throw new \RuntimeException('menus 中缺少启用的页面(view)');
        }

        $btnIds = [];
        $btns = [
            ['add', '添加', 'el:Plus', 0],
            ['edit', '编辑', 'el:EditPen', 1],
            ['del', '删除', 'el:Delete', 2],
        ];
        foreach ($btns as [$btnPath, $btnName, $btnIcon, $btnSort]) {
            $btnIds[$btnPath] = $this->ensureRule(
                [
                    'scope' => 'frontend',
                    'type'  => 'btn',
                    'pid'   => $frontViewId,
                    'path'  => $btnPath,
                ],
                [
                    'scope'       => 'frontend',
                    'type'        => 'btn',
                    'pid'         => $frontViewId,
                    'name'        => $btnName,
                    'path'        => $btnPath,
                    'method'      => null,
                    'component'   => null,
                    'icon'        => $btnIcon,
                    'sort'        => $btnSort,
                    'status'      => 1,
                    'create_time' => $now,
                    'update_time' => $now,
                ],
                "frontend.btn:{$btnPath}",
                $created,
                $updated,
                $reused,
                true
            );
        }

        $bindings = [
            [$frontViewId, $apiIds['list']],
            [$frontViewId, $apiIds['read']],
            [$btnIds['add'], $apiIds['create']],
            [$btnIds['edit'], $apiIds['update']],
            [$btnIds['del'], $apiIds['delete']],
        ];
        foreach ($uploadActions as $action) {
            $bindings[] = [$btnIds['add'], $apiIds[$action]];
            $bindings[] = [$btnIds['edit'], $apiIds[$action]];
        }

        $remoteSort = $sort;
        foreach (array_merge($ctx->belongsToFields, $ctx->belongsToManyFields) as $f) {
            $remote = Naming::parseRemoteApi(FieldMeta::remoteApi($f) ?: null);
            if ($remote === null) {
                continue;
            }
            $remoteId = $this->ensureRemoteListApi(
                $remote,
                (string) ($f['label'] ?? $remote['entity']),
                $remoteSort++,
                $now,
                $created,
                $updated,
                $reused,
            );
            $bindings[] = [$frontViewId, $remoteId];
        }

        $frontRuleIds = array_values(array_filter([
            $frontViewId,
            $btnIds['add'] ?? 0,
            $btnIds['edit'] ?? 0,
            $btnIds['del'] ?? 0,
        ], static fn (int $id): bool => $id > 0));

        // 多远程字段可指向同一 list API，绑定时按 (front,back) 去重，避免覆盖写入撞主键
        $uniqueBindings = [];
        foreach ($bindings as [$frontId, $backId]) {
            if ($frontId <= 0 || $backId <= 0) {
                continue;
            }
            $uniqueBindings[$frontId . ':' . $backId] = [$frontId, $backId];
        }
        $bindings = array_values($uniqueBindings);

        $bindingsCleared = 0;
        if ($viewExisted && $frontRuleIds !== []) {
            // 已有 view：覆盖权限绑定（先清后写）
            $bindingsCleared = (int) RuleApi::whereIn('frontend_rule_id', $frontRuleIds)
                ->delete();
        }

        $bindCount = 0;
        foreach ($bindings as [$frontId, $backId]) {
            if (!$viewExisted) {
                $exists = RuleApi::where('frontend_rule_id', $frontId)
                    ->where('backend_rule_id', $backId)
                    ->count();
                if ($exists > 0) {
                    continue;
                }
            }
            RuleApi::insert([
                'frontend_rule_id' => $frontId,
                'backend_rule_id'  => $backId,
            ]);
            $bindCount++;
        }

        return [
            'created'         => $created,
            'updated'         => $updated,
            'reused'          => $reused,
            'bindings'        => $bindCount,
            'bindingsCleared' => $bindingsCleared,
            'viewOverwritten' => $viewExisted,
        ];
    }

    /**
     * validate 生成前校验：frontend 同 pid+path 下 menu/view 类型冲突则抛错
     *
     * @throws DbException
     */
    public function validate(Context $ctx): void
    {
        $menuChain = $ctx->resolvedMenuChain();
        if ($menuChain === []) {
            throw new \RuntimeException('menus 中缺少启用的页面(view)，无法生成权限');
        }

        $frontMenuId = 0;
        $pathTrail = [];

        foreach ($menuChain as $menu) {
            $path = (string) $menu['path'];
            $pathTrail[] = $path;
            $label = implode('/', $pathTrail);
            $expectedType = ((string) ($menu['type'] ?? 'menu')) === 'view' ? 'view' : 'menu';
            $pid = $frontMenuId;

            $conflict = Rule::where('scope', 'frontend')
                ->where('pid', $pid)
                ->where('path', $path)
                ->where('type', '<>', $expectedType)
                ->whereIn('type', ['menu', 'view'])
                ->find();

            if ($conflict) {
                throw new \RuntimeException(sprintf(
                    '前端菜单冲突：路径「%s」在 pid=%d 下已存在 %s「%s」(id=%d)，'
                    . '当前生成需要 %s「%s」。'
                    . '请调整 menus 路径、手动合并权限规则，或取消「生成权限规则」后仅生成代码。',
                    $label,
                    $pid,
                    (string) $conflict->getAttr('type'),
                    (string) $conflict->getAttr('name'),
                    (int) $conflict->getAttr('id'),
                    $expectedType,
                    (string) ($menu['title'] ?? $path),
                ));
            }

            $id = $this->findRuleId([
                'scope' => 'frontend',
                'type'  => $expectedType,
                'pid'   => $pid,
                'path'  => $path,
            ]);

            if ($id <= 0) {
                break;
            }
            $frontMenuId = $id;
        }
    }

    /**
     * preview 干跑预览将写入的规则标识
     *
     * @return list<array{name: string, status: string}>
     */
    public function preview(Context $ctx): array
    {
        $items = [];
        $pathTrail = [];
        foreach ($ctx->resolvedMenuChain() as $menu) {
            $pathTrail[] = (string) $menu['path'];
            $type = (string) ($menu['type'] ?? 'menu');
            $label = implode('/', $pathTrail);
            if ($type === 'view') {
                $items[] = ['name' => 'backend.menu:' . $label . '/api', 'status' => 'planned'];
                $items[] = ['name' => "frontend.view:{$label}", 'status' => 'planned'];
            } else {
                $items[] = ['name' => "backend.menu:{$label}", 'status' => 'planned'];
                $items[] = ['name' => "frontend.menu:{$label}", 'status' => 'planned'];
            }
        }
        if ($pathTrail === []) {
            $items[] = ['name' => 'backend.menu:(missing)', 'status' => 'planned'];
            $items[] = ['name' => 'frontend.view:(missing)', 'status' => 'planned'];
        }
        $items[] = ['name' => "backend.api:{$ctx->apiBase}/list GET", 'status' => 'planned'];
        $items[] = ['name' => "backend.api:{$ctx->apiBase}/read GET", 'status' => 'planned'];
        $items[] = ['name' => "backend.api:{$ctx->apiBase}/create POST", 'status' => 'planned'];
        $items[] = ['name' => "backend.api:{$ctx->apiBase}/update PUT", 'status' => 'planned'];
        $items[] = ['name' => "backend.api:{$ctx->apiBase}/delete DELETE", 'status' => 'planned'];
        foreach ($ctx->imageFields as $f) {
            $action = Naming::uploadActionName((string) $f['prop']);
            $items[] = ['name' => "backend.api:{$ctx->apiBase}/{$action} POST", 'status' => 'planned'];
        }
        $items[] = ['name' => 'frontend.btn:add', 'status' => 'planned'];
        $items[] = ['name' => 'frontend.btn:edit', 'status' => 'planned'];
        $items[] = ['name' => 'frontend.btn:del', 'status' => 'planned'];
        foreach (array_merge($ctx->belongsToFields, $ctx->belongsToManyFields) as $f) {
            $remote = Naming::parseRemoteApi(FieldMeta::remoteApi($f) ?: null);
            if ($remote !== null) {
                $items[] = ['name' => "backend.api:{$remote['url']} GET", 'status' => 'planned'];
            }
        }

        return $items;
    }

    /**
     * applyInstallStatus 将 install 结果写回 preview 项的 status
     *
     * @param list<array{name: string, status: string}> $preview
     * @param array{created: list<string>, updated: list<string>, reused: list<string>} $install
     * @return list<array{name: string, status: string}>
     */
    public function applyInstallStatus(array $preview, array $install): array
    {
        $created = array_fill_keys($install['created'], true);
        $updated = array_fill_keys($install['updated'], true);
        $reused = array_fill_keys($install['reused'], true);
        $out = [];
        foreach ($preview as $item) {
            $name = (string) ($item['name'] ?? '');
            $status = 'planned';
            if (isset($created[$name])) {
                $status = 'created';
            } elseif (isset($updated[$name])) {
                $status = 'updated';
            } elseif (isset($reused[$name])) {
                $status = 'reused';
            }
            $out[] = ['name' => $name, 'status' => $status];
        }

        return $out;
    }

    /**
     * resolveMenuIcon 解析菜单/页面默认图标 {menu:Folder, view:Document}
     *
     * @param array<string, mixed> $menu
     */
    private function resolveMenuIcon(array $menu, bool $isView): string
    {
        $icon = trim((string) ($menu['icon'] ?? ''));
        if ($icon !== '') {
            return $icon;
        }

        return $isView ? 'el:Document' : 'el:Folder';
    }

    /**
     * ensureRemoteListApi 确保远程 list API 规则存在并返回 id
     * 已存在：只复用，不改 pid/其它字段；不存在：挂到远程模块自身接口组下新建
     *
     * @param array{module: string, entity: string, url: string} $remote
     * @param list<string> $created
     * @param list<string> $updated
     * @param list<string> $reused
     */
    private function ensureRemoteListApi(
        array $remote,
        string $label,
        int $sort,
        string $now,
        array &$created,
        array &$updated,
        array &$reused,
    ): int {
        $rPath = $remote['url'];
        $where = [
            'scope'  => 'backend',
            'type'   => 'api',
            'path'   => $rPath,
            'method' => 'GET',
        ];
        $existingId = $this->findRuleId($where);
        if ($existingId > 0) {
            $reused[] = "backend.api:{$rPath} GET";

            return $existingId;
        }

        $groupId = $this->resolveRemoteApiGroupId($remote, $now, $created, $updated, $reused);

        return $this->ensureRule(
            $where,
            [
                'scope'       => 'backend',
                'type'        => 'api',
                'pid'         => $groupId,
                'name'        => $label . '列表',
                'path'        => $rPath,
                'method'      => 'GET',
                'component'   => null,
                'icon'        => null,
                'sort'        => $sort,
                'status'      => 1,
                'create_time' => $now,
                'update_time' => $now,
            ],
            "backend.api:{$rPath} GET",
            $created,
            $updated,
            $reused,
            false
        );
    }

    /**
     * resolveRemoteApiGroupId 解析远程 API 应挂靠的接口组 id
     * 优先复用同控制器前缀下已有 API 的 pid；否则按 module 路径创建/复用「{Entity}接口」组
     *
     * @param array{module: string, entity: string, url: string} $remote
     * @param list<string> $created
     * @param list<string> $updated
     * @param list<string> $reused
     */
    private function resolveRemoteApiGroupId(
        array $remote,
        string $now,
        array &$created,
        array &$updated,
        array &$reused,
    ): int {
        $base = preg_replace('/\/[^\/]+$/', '', $remote['url']) ?? $remote['url'];
        if ($base !== '') {
            $siblingPid = (int) Rule::where('scope', 'backend')
                ->where('type', 'api')
                ->whereLike('path', $base . '/%')
                ->where('pid', '>', 0)
                ->order('id', 'asc')
                ->value('pid');
            if ($siblingPid > 0) {
                return $siblingPid;
            }
        }

        // 按 module 路径（如 ass、test/ft）确保后端 menu 链，再挂 Entity 接口组
        $pid = 1;
        $pathTrail = [];
        $segments = array_values(array_filter(explode('/', str_replace('\\', '/', $remote['module']))));
        foreach ($segments as $segment) {
            $pathTrail[] = $segment;
            $pid = $this->ensureRule(
                [
                    'scope' => 'backend',
                    'type'  => 'menu',
                    'pid'   => $pid,
                    'path'  => $segment,
                ],
                [
                    'scope'       => 'backend',
                    'type'        => 'menu',
                    'pid'         => $pid,
                    'name'        => $segment,
                    'path'        => $segment,
                    'method'      => null,
                    'component'   => null,
                    'icon'        => null,
                    'sort'        => 0,
                    'status'      => 1,
                    'create_time' => $now,
                    'update_time' => $now,
                ],
                'backend.menu:' . implode('/', $pathTrail),
                $created,
                $updated,
                $reused,
                false
            );
        }

        $groupName = $remote['entity'] . '接口';
        $pathTrail[] = 'api';

        return $this->ensureRule(
            [
                'scope' => 'backend',
                'type'  => 'menu',
                'pid'   => $pid,
                'name'  => $groupName,
            ],
            [
                'scope'       => 'backend',
                'type'        => 'menu',
                'pid'         => $pid,
                'name'        => $groupName,
                'path'        => null,
                'method'      => null,
                'component'   => null,
                'icon'        => null,
                'sort'        => 0,
                'status'      => 1,
                'create_time' => $now,
                'update_time' => $now,
            ],
            'backend.menu:' . implode('/', $pathTrail),
            $created,
            $updated,
            $reused,
            false
        );
    }

    /**
     * findRuleId 按条件查找规则 id
     *
     * @param array<string, mixed> $where
     */
    private function findRuleId(array $where): int
    {
        $q = Rule::where($where);

        return (int) $q->value('id');
    }

    /**
     * ensureRule 按条件查找；不存在则插入；overwrite 时更新可变字段
     *
     * @param array<string, mixed> $where
     * @param array<string, mixed> $data
     * @param list<string> $created
     * @param list<string> $updated
     * @param list<string> $reused
     */
    private function ensureRule(
        array $where,
        array $data,
        string $label,
        array &$created,
        array &$updated,
        array &$reused,
        bool $overwrite = false
    ): int {
        $id = $this->findRuleId($where);
        if ($id > 0) {
            if ($overwrite) {
                $patch = $data;
                unset($patch['create_time'], $patch['scope'], $patch['type']);
                $patch['update_time'] = date('Y-m-d H:i:s');
                Rule::where('id', $id)->update($patch);
                $updated[] = $label;
            } else {
                $reused[] = $label;
            }

            return $id;
        }
        $id = (int) Rule::insertGetId($data);
        $created[] = $label;

        return $id;
    }
}
