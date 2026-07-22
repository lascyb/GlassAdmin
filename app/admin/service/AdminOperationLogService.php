<?php
declare (strict_types = 1);

namespace app\admin\service;

use app\admin\model\auth\AdminOperationLog;
use app\admin\model\auth\Rule;
use app\admin\service\auth\AdminAuthPayload;
use think\facade\Cache;
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
        'account.Profile/operationLogs',
    ];

    /** 后端 API 中文名缓存键 */
    private const string CACHE_KEY_API_NAMES = 'admin_api_rule_names';

    /** 后端 API 中文名缓存标签 */
    private const string CACHE_TAG_API_NAMES = 'admin_api_rule_names';

    /** 缓存 TTL（秒），0 表示不过期（依赖 forget 清理） */
    private const int CACHE_TTL = 0;

    /**
     * normalizeApiPath 与 auth_rule.path、AdminRbacService 对齐（pathinfo 点号格式，如 auth.Rule/tree）
     */
    public static function normalizeApiPath(string $pathinfo): string
    {
        return trim($pathinfo, '/');
    }

    /**
     * forgetApiNameCache 规则变更后清理 API 中文名缓存
     */
    public static function forgetApiNameCache(): void
    {
        Cache::tag(self::CACHE_TAG_API_NAMES)->clear();
    }

    /**
     * resolveApiName 按 method+path 查 auth_rule 接口中文名（带缓存）
     */
    public static function resolveApiName(string $method, string $path): string
    {
        $path = self::normalizeApiPath($path);
        if ($path === '') {
            return '';
        }
        $m   = strtoupper($method);
        $map = self::apiNameMap();
        $exact = $map[$m . ':' . $path] ?? '';
        if ($exact !== '') {
            return $exact;
        }

        return $map['*:' . $path] ?? '';
    }

    /**
     * apiNameMap 全量后端 api：METHOD:path => name（含 *:path）
     *
     * @return array<string, string>
     */
    private static function apiNameMap(): array
    {
        $cached = Cache::get(self::CACHE_KEY_API_NAMES);
        if (is_array($cached)) {
            /** @var array<string, string> $cached */
            return $cached;
        }

        $map  = [];
        $rows = Rule::where('scope', 'backend')
            ->where('type', 'api')
            ->field('path,method,name')
            ->select()
            ->toArray();
        foreach ($rows as $row) {
            $path = self::normalizeApiPath((string) ($row['path'] ?? ''));
            if ($path === '') {
                continue;
            }
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $method = strtoupper(trim((string) ($row['method'] ?? '')));
            if ($method === '' || $method === '*') {
                $map['*:' . $path] = $name;
            } else {
                $map[$method . ':' . $path] = $name;
            }
        }
        Cache::tag(self::CACHE_TAG_API_NAMES)->set(self::CACHE_KEY_API_NAMES, $map, self::CACHE_TTL);

        return $map;
    }

    /**
     * record 写入一条操作日志（异常吞掉，不抛给上层；单条 insert，避免 save 模型开销）
     */
    public static function record(int $adminId, string $action, string $summary, ?string $ip): void
    {
        try {
            AdminOperationLog::insert([
                'admin_id'    => $adminId,
                'action'      => $action,
                'summary'     => mb_substr($summary, 0, self::SUMMARY_MAX, 'UTF-8'),
                'ip'          => $ip,
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
        $m       = strtoupper($method);
        $action  = $m . '.' . $apiPath;
        $summary = self::resolveApiName($m, $apiPath);
        if ($summary === '') {
            $summary = $m . ' ' . $apiPath;
        }
        self::record($adminId, $action, $summary, $request->ip());
    }
}
