<?php
declare (strict_types = 1);

namespace app\admin\service;

use app\admin\service\auth\AdminAuthPayload;
use think\facade\Db;
use think\Request;
use think\Response;

/**
 * AdminOperationLogService 管理端操作日志写入（供控制器与业务显式调用）
 */
class AdminOperationLogService
{
    /** summaryMax summary 最大长度（与表字段一致） */
    private const int SUMMARY_MAX = 255;

    /** skipPaths 不记录日志的 API 路径 */
    private const array SKIP_PATHS = [
        'account.OperationLog/index',
    ];

    /**
     * normalizeApiPath 与 auth_rule.path、AdminRbacService 对齐（pathinfo 点号格式，如 auth.Rule/tree）
     */
    public static function normalizeApiPath(string $pathinfo): string
    {
        return trim($pathinfo, '/');
    }

    /**
     * record 写入一条操作日志（异常吞掉，不抛给上层；单条 insert，避免 save 模型开销）
     */
    public static function record(int $adminId, string $action, string $summary, ?string $ip): void
    {
        try {
            Db::name('admin_operation_log')->insert([
                'admin_id'  => $adminId,
                'action'     => $action,
                'summary'    => mb_substr($summary, 0, self::SUMMARY_MAX, 'UTF-8'),
                'ip'         => $ip,
                'create_time' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable) {
        }
    }

    /**
     * recordIfSuccessfulApiResponse 成功 JSON 写操作响应后记录操作日志
     */
    public static function recordIfSuccessfulApiResponse(Request $request, Response $response): void
    {
        if ($request->isOptions()) {
            return;
        }
        $method = strtoupper($request->method());
        if (!in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'], true)) {
            return;
        }
        if ((AdminAuthPayload::fromRequest()?->adminId ?? 0) <= 0) {
            return;
        }
        $path = self::normalizeApiPath($request->pathinfo());
        if ($path === '' || in_array($path, self::SKIP_PATHS, true)) {
            return;
        }
        $content = $response->getContent();
        $data    = json_decode($content, true);
        if (!is_array($data) || (int) ($data['code'] ?? -1) !== 0) {
            return;
        }
        self::recordFromSuccessfulApiRequest($request, $path, $method);
    }

    /**
     * recordFromSuccessfulApiRequest 根据成功 API 请求生成 action/summary 并写入
     */
    public static function recordFromSuccessfulApiRequest(Request $request, string $apiPath, string $method): void
    {
        $adminId = AdminAuthPayload::fromRequest()?->adminId ?? 0;
        if ($adminId <= 0) {
            return;
        }
        $m      = strtoupper($method);
        $action = $m . '.' . $apiPath;
        $get    = $request->get();
        $suffix = '';
        if ($get !== []) {
            $q = http_build_query($get);
            if ($q !== '') {
                $suffix = ' ?' . $q;
            }
        }
        $summary = $m . ' ' . $apiPath . $suffix;
        self::record($adminId, $action, $summary, $request->ip());
    }
}
