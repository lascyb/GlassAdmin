<?php
declare (strict_types = 1);

namespace app\admin\service;

use app\admin\model\auth\Rule;
use think\facade\Cache;
use think\facade\Db;
use think\Request;

/**
 * AdminRbacService 管理端接口授权：角色经前端规则绑定后端 api 规则（与 auth_role_rule、auth_rule_frontend_api 一致）
 * 超级管理员应在中间件层跳过本服务，不在此处理
 */
class AdminRbacService
{
    /** 缓存标签（用于整批失效） */
    private const string CACHE_TAG = 'admin_rbac_api';

    /** 按角色缓存允许调用的后端 api 规则 id 列表 */
    private const string CACHE_KEY_PREFIX = 'admin_rbac_api_ids:role:';

    /** 缓存 TTL（秒），0 表示不过期，依赖 forget* 清理 */
    private const int CACHE_TTL = 0;

    /**
     * forgetRoleRbacCache 清除指定 role_id 的接口权限缓存（分配前端规则后调用）
     */
    public static function forgetRoleRbacCache(int $roleId): void
    {
        if ($roleId > 0) {
            Cache::delete(self::cacheKey($roleId));
        }
    }

    /**
     * forgetAllRoleRbacCaches 清除所有角色的接口权限缓存（绑定前后端 api、前端规则结构变更等）
     */
    public static function forgetAllRoleRbacCaches(): void
    {
        Cache::tag(self::CACHE_TAG)->clear();
    }

    /**
     * isApiAllowed 当前请求是否命中已登记的后端 api 且该 api 在角色允许集合内（不含超级管理员）
     */
    public static function isApiAllowed(Request $request, int $roleId): bool
    {
        $path   = AdminOperationLogService::normalizeApiPath($request->pathinfo());
        $method = strtoupper($request->method());
        if ($path === '') {
            return false;
        }
        // 动态菜单：已登录角色均需拉取侧栏，不依赖 auth_rule_frontend_api 显式绑定
        if ($path === 'Menu/menus' && $method === 'GET' && $roleId > 0) {
            return true;
        }
        // 已登录用户拉取本人通知列表（仅 GET；数据范围固定为 request->admin_id，limit 在 account\Notification 控制器收紧）
        if ($path === 'account.Notification/index' && $method === 'GET' && $roleId > 0) {
            return true;
        }
        $ruleId = self::resolveBackendApiRuleId($path, $method);
        if ($ruleId === null) {
            return false;
        }
        $allowed = self::allowedBackendApiIds($roleId);

        return in_array($ruleId, $allowed, true);
    }

    /**
     * cacheKey 生成角色接口权限缓存键
     */
    private static function cacheKey(int $roleId): string
    {
        return self::CACHE_KEY_PREFIX . $roleId;
    }

    /**
     * resolveBackendApiRuleId 按 path + method 解析启用的后端 api 规则 id
     */
    private static function resolveBackendApiRuleId(string $path, string $method): ?int
    {
        $row = Rule::where('scope', 'backend')
            ->where('type', 'api')
            ->where('status', 1)
            ->where('path', $path)
            ->whereRaw('(method = ? OR method = ?)', [$method, '*'])
            ->field('id')
            ->find();
        if ($row === null) {
            return null;
        }

        return (int) $row->getAttr('id');
    }

    /**
     * allowedBackendApiIds 角色允许的后端 api 规则 id 列表（读缓存或计算后写入）
     *
     * @return list<int>
     */
    private static function allowedBackendApiIds(int $roleId): array
    {
        if ($roleId <= 0) {
            return [];
        }
        $key = self::cacheKey($roleId);
        $cached = Cache::get($key);
        if (is_array($cached)) {
            return array_map('intval', $cached);
        }
        $frontendIds = Db::name('auth_role_rule')->where('role_id', $roleId)->column('rule_id');
        $frontendIds = array_map('intval', $frontendIds);
        if ($frontendIds === []) {
            $ids = [];
        } else {
            $ids = Db::name('auth_rule_frontend_api')
                ->where('frontend_rule_id', 'in', $frontendIds)
                ->column('backend_rule_id');
            $ids = array_values(array_unique(array_map('intval', $ids)));
        }
        Cache::tag(self::CACHE_TAG)->set($key, $ids, self::CACHE_TTL);

        return $ids;
    }
}
