<?php
declare (strict_types = 1);

namespace app\service;

use think\facade\Db;
use think\Request;

/**
 * AdminOperationLogService 管理端操作日志写入（供中间件与业务显式调用）
 */
class AdminOperationLogService
{
    /** summaryMax summary 最大长度（与表字段一致） */
    private const int SUMMARY_MAX = 255;

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
     * recordFromSuccessfulApiRequest 根据成功 API 请求生成 action/summary 并写入
     */
    public static function recordFromSuccessfulApiRequest(Request $request, string $apiPath, string $method): void
    {
        $adminId = (int) ($request->admin_id ?? 0);
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
