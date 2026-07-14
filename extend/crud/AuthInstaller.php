<?php
declare (strict_types = 1);

namespace crud;

use crud\Context;
use think\db\exception\DbException;
use think\facade\Db;

/**
 * AuthInstaller 将权限/菜单直接写入数据库（不挂角色、不生成 SQL 文件）
 *
 * 对齐 example 金样：
 * - backend menu 组（pid=1）+ api：list/read/create/update/delete（+upload）
 * - frontend menu 链（menus 按 enabled 过滤，pid 逐级挂接）+ view + btn(add/edit/del)
 * - auth_rule_frontend_api 绑定
 *
 * 若 frontend view 已存在：更新 view/btn 字段，并覆盖重建该 view 子树的 frontend_api 绑定。
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

        $backendMenuName = $ctx->moduleTitle . '接口';
        $backendMenuId = $this->ensureRule(
            [
                'scope' => 'backend',
                'type'  => 'menu',
                'pid'   => 1,
                'name'  => $backendMenuName,
            ],
            [
                'scope'       => 'backend',
                'type'        => 'menu',
                'pid'         => 1,
                'name'        => $backendMenuName,
                'path'        => null,
                'method'      => null,
                'component'   => null,
                'icon'        => null,
                'sort'        => $ctx->menuSort,
                'status'      => 1,
                'create_time' => $now,
                'update_time' => $now,
            ],
            "backend.menu:{$backendMenuName}",
            $created,
            $updated,
            $reused,
            true
        );

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
            $action = Context::uploadActionName((string) $f['prop']);
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
                    'pid'         => $backendMenuId,
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
        $menuChain = $ctx->resolvedMenuChain();
        $pathTrail = [];
        $frontViewId = 0;
        $viewExisted = false;

        foreach ($menuChain as $i => $menu) {
            $path = (string) $menu['path'];
            $pathTrail[] = $path;
            $pid = $frontMenuId;
            $type = (string) ($menu['type'] ?? 'menu');
            $icon = trim((string) ($menu['icon'] ?? ''));
            if ($icon === '' && $i === 0) {
                $icon = $ctx->menuIcon;
            }
            $sort = isset($menu['sort']) ? (int) $menu['sort'] : ($i === 0 ? $ctx->menuSort : 0);

            if ($type === 'view') {
                $viewWhere = [
                    'scope' => 'frontend',
                    'type'  => 'view',
                    'pid'   => $pid,
                    'path'  => $path,
                ];
                $viewExisted = $this->findRuleId($viewWhere) > 0;
                $viewPathLabel = implode('/', $pathTrail);
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
                        'icon'        => $icon !== '' ? $icon : 'el:Document',
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
                        'icon'        => $icon !== '' ? $icon : null,
                        'sort'        => $sort,
                        'status'      => 1,
                        'create_time' => $now,
                        'update_time' => $now,
                    ],
                    'frontend.menu:' . implode('/', $pathTrail),
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
            $remote = Context::parseRemoteApi(Context::optionsKey($f) ?: null);
            if ($remote === null) {
                continue;
            }
            $rPath = $remote['url'];
            $remoteId = $this->ensureRule(
                [
                    'scope'  => 'backend',
                    'type'   => 'api',
                    'path'   => $rPath,
                    'method' => 'GET',
                ],
                [
                    'scope'       => 'backend',
                    'type'        => 'api',
                    'pid'         => $backendMenuId,
                    'name'        => ((string) ($f['label'] ?? $remote['entity'])) . '列表',
                    'path'        => $rPath,
                    'method'      => 'GET',
                    'component'   => null,
                    'icon'        => null,
                    'sort'        => $remoteSort++,
                    'status'      => 1,
                    'create_time' => $now,
                    'update_time' => $now,
                ],
                "backend.api:{$rPath} GET",
                $created,
                $updated,
                $reused,
                true
            );
            $bindings[] = [$frontViewId, $remoteId];
        }

        $frontRuleIds = array_values(array_filter([
            $frontViewId,
            $btnIds['add'] ?? 0,
            $btnIds['edit'] ?? 0,
            $btnIds['del'] ?? 0,
        ], static fn (int $id): bool => $id > 0));

        $bindingsCleared = 0;
        if ($viewExisted && $frontRuleIds !== []) {
            // 已有 view：覆盖权限绑定（先清后写）
            $bindingsCleared = (int) Db::name('auth_rule_frontend_api')
                ->whereIn('frontend_rule_id', $frontRuleIds)
                ->delete();
        }

        $bindCount = 0;
        foreach ($bindings as [$frontId, $backId]) {
            if ($frontId <= 0 || $backId <= 0) {
                continue;
            }
            if (!$viewExisted) {
                $exists = Db::name('auth_rule_frontend_api')
                    ->where('frontend_rule_id', $frontId)
                    ->where('backend_rule_id', $backId)
                    ->count();
                if ($exists > 0) {
                    continue;
                }
            }
            Db::name('auth_rule_frontend_api')->insert([
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
     * preview 干跑预览将写入的规则标识
     *
     * @return list<string>
     */
    public function preview(Context $ctx): array
    {
        $items = [
            "backend.menu:{$ctx->moduleTitle}接口",
            "backend.api:{$ctx->apiBase}/list GET",
            "backend.api:{$ctx->apiBase}/read GET",
            "backend.api:{$ctx->apiBase}/create POST",
            "backend.api:{$ctx->apiBase}/update PUT",
            "backend.api:{$ctx->apiBase}/delete DELETE",
        ];
        foreach ($ctx->imageFields as $f) {
            $action = Context::uploadActionName((string) $f['prop']);
            $items[] = "backend.api:{$ctx->apiBase}/{$action} POST";
        }
        $pathTrail = [];
        foreach ($ctx->resolvedMenuChain() as $menu) {
            $pathTrail[] = (string) $menu['path'];
            $type = (string) ($menu['type'] ?? 'menu');
            $label = implode('/', $pathTrail);
            $items[] = $type === 'view' ? "frontend.view:{$label}" : "frontend.menu:{$label}";
        }
        if ($pathTrail === []) {
            $items[] = 'frontend.view:(missing)';
        }
        $items[] = 'frontend.btn:add';
        $items[] = 'frontend.btn:edit';
        $items[] = 'frontend.btn:del';
        foreach (array_merge($ctx->belongsToFields, $ctx->belongsToManyFields) as $f) {
            $remote = Context::parseRemoteApi(Context::optionsKey($f) ?: null);
            if ($remote !== null) {
                $items[] = "backend.api:{$remote['url']} GET";
            }
        }

        return $items;
    }

    /**
     * findRuleId 按条件查找规则 id
     *
     * @param array<string, mixed> $where
     */
    private function findRuleId(array $where): int
    {
        $q = Db::name('auth_rule');
        foreach ($where as $k => $v) {
            $q->where($k, $v);
        }

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
                Db::name('auth_rule')->where('id', $id)->update($patch);
                $updated[] = $label;
            } else {
                $reused[] = $label;
            }

            return $id;
        }
        $id = (int) Db::name('auth_rule')->insertGetId($data);
        $created[] = $label;

        return $id;
    }
}
