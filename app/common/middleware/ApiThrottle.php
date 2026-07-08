<?php
declare (strict_types = 1);

namespace app\common\middleware;

use app\admin\service\auth\AdminAuthPayload;
use app\common\ApiResponse;
use Closure;
use think\facade\Cache;
use think\Request;
use think\Response;

/**
 * ApiThrottle 固定窗口计数限流：公开接口按 IP+路径；鉴权接口按 admin_id+路径
 */
class ApiThrottle
{
    /** guest 单窗口内单 IP+接口最大请求次数 */
    private const int GUEST_MAX_ATTEMPTS = 60;

    /** guest 统计窗口秒数 */
    private const int GUEST_DECAY_SECONDS = 60;

    /** auth 单窗口内单 admin_id+接口最大请求次数 */
    private const int AUTH_MAX_ATTEMPTS = 60;

    /** auth 统计窗口秒数 */
    private const int AUTH_DECAY_SECONDS = 60;

    /**
     * enforce 校验限流，超限返回 429 Response，否则返回 null
     */
    public static function enforce(Request $request): ?Response
    {
        if ($request->isOptions()) {
            return null;
        }

        $adminId = AdminAuthPayload::fromRequest()?->adminId ?? 0;
        $max     = $adminId > 0 ? self::AUTH_MAX_ATTEMPTS : self::GUEST_MAX_ATTEMPTS;
        $decay   = $adminId > 0 ? self::AUTH_DECAY_SECONDS : self::GUEST_DECAY_SECONDS;
        $suffix  = $adminId > 0 ? ('u:' . $adminId) : ('ip:' . md5($request->ip()));
        $path      = strtolower((string) $request->pathinfo());
        $method    = strtoupper($request->method());
        $window    = (int) floor(time() / $decay);
        $cacheKey  = 'api_throttle:' . $window . ':' . $suffix . ':' . $method . ':' . md5($path);

        $count = Cache::get($cacheKey);
        if ($count === false || $count === null) {
            Cache::set($cacheKey, 1, $decay + 1);
        } else {
            $n = (int) $count;
            if ($n >= $max) {
                return ApiResponse::tooManyRequests();
            }
            Cache::set($cacheKey, $n + 1, $decay + 1);
        }

        return null;
    }

    /**
     * handle 超过配置次数返回 429
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $blocked = self::enforce($request);
        if ($blocked instanceof Response) {
            return $blocked;
        }

        return $next($request);
    }
}
