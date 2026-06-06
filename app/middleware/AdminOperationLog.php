<?php
declare (strict_types = 1);

namespace app\middleware;

use app\service\AdminOperationLogService;
use Closure;
use think\Request;
use think\Response;

/**
 * AdminOperationLog 在已鉴权 API 成功响应后收集写操作日志（POST/PUT/DELETE/PATCH，code===0）
 */
class AdminOperationLog
{
    /** skipPaths 不记录日志的 API 路径（与 AdminOperationLogService::normalizeApiPath 结果一致） */
    private const array SKIP_PATHS = [
        'account.OperationLog/index',
    ];

    /**
     * handle 在控制器执行完毕后按条件写入 admin_operation_log
     *
     * @param Request $request
     * @param Closure $next
     * @return Response|mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $response = $next($request);
        if (!$response instanceof Response) {
            return $response;
        }
        if ($request->isOptions()) {
            return $response;
        }
        $method = strtoupper($request->method());
        if (!in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'], true)) {
            return $response;
        }
        $adminId = (int) ($request->admin_id ?? 0);
        if ($adminId <= 0) {
            return $response;
        }
        $path = AdminOperationLogService::normalizeApiPath($request->pathinfo());
        if ($path === '' || in_array($path, self::SKIP_PATHS, true)) {
            return $response;
        }

        $content = $response->getContent();
        $data    = json_decode($content, true);
        if (!is_array($data) || (int) ($data['code'] ?? -1) !== 0) {
            return $response;
        }

        AdminOperationLogService::recordFromSuccessfulApiRequest($request, $path, $method);

        return $response;
    }
}
