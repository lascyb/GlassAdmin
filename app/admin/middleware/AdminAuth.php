<?php
declare (strict_types = 1);

namespace app\admin\middleware;

use app\admin\service\AdminRbacService;
use app\admin\service\AuthService;
use app\common\ApiResponse;
use Closure;
use think\Request;
use think\Response;

/**
 * AdminAuth 校验管理端 Bearer access_token（opaque，经 AuthService）；非超级管理员再经 AdminRbacService 做接口 RBAC
 */
class AdminAuth
{
    /**
     * handle 解析 token 并写入 request->admin_id、request->role_ids、request->auth
     *
     * @param Request $request
     * @param Closure $next
     * @return Response|mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $auth = (string) $request->header('Authorization', '');
        $token = '';
        if ($request->isOptions()) {
            return $next($request);
        }
        if (str_starts_with($auth, 'Bearer ')) {
            $token = trim(substr($auth, 7));
        }
        if ($token === '') {
            return ApiResponse::unauthorized('未登录');
        }
        $payload = AuthService::authenticate($token);
        if ($payload === null) {
            return ApiResponse::unauthorized('登录已失效');
        }
        if (!$payload->isSuper() && !AdminRbacService::isApiAllowed($request, $payload->roleIds)) {
            return ApiResponse::forbidden('无权访问');
        }
        $request->admin_id = $payload->adminId;
        $request->role_ids = $payload->roleIds;
        $request->auth     = $payload;

        return $next($request);
    }
}
