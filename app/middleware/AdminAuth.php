<?php
declare (strict_types = 1);

namespace app\middleware;

use Closure;
use think\facade\Cache;
use think\Request;
use think\Response;

/**
 * AdminAuth 校验管理端 Bearer token（与 Login 写入的 Cache 键一致）
 */
class AdminAuth
{
    /**
     * handle 解析 token 并写入 request->adminId
     *
     * @param Request $request
     * @param Closure $next
     * @return Response|mixed
     */
    public function handle(Request $request, Closure $next)
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
            return json(['code' => 401, 'msg' => '未登录', 'data' => null], 401);
        }
        $payload = Cache::get('admin_token:' . $token);
        if (!is_array($payload) || empty($payload['uid'])) {
            return json(['code' => 401, 'msg' => '登录已失效', 'data' => null], 401);
        }
        $request->adminId = (int) $payload['uid'];

        return $next($request);
    }
}
