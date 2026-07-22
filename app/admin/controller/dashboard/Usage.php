<?php
declare (strict_types = 1);

namespace app\admin\controller\dashboard;

use app\admin\model\auth\Admin;
use app\admin\model\auth\AdminOperationLog;
use app\admin\model\auth\AdminRole;
use app\admin\model\auth\Role;
use app\admin\model\auth\Rule;
use app\common\controller\AdminController;
use think\Request;
use think\Response;

/**
 * Usage 系统使用情况仪表盘
 */
class Usage extends AdminController
{
    /** trendDaysDefault 趋势默认天数 */
    private const int TREND_DAYS_DEFAULT = 7;

    /** trendDaysMax 趋势最大天数 */
    private const int TREND_DAYS_MAX = 30;

    /** recentLogsLimit 最近操作日志条数 */
    private const int RECENT_LOGS_LIMIT = 20;

    /**
     * index 系统使用情况统计（全站）
     */
    public function index(Request $request): Response
    {
        if (!$request->isGet()) {
            return $this->fail(405, '仅支持 GET');
        }
        $days = min(self::TREND_DAYS_MAX, max(1, (int) $request->get('days', self::TREND_DAYS_DEFAULT)));
        $todayStart = date('Y-m-d 00:00:00');
        $trendStart = date('Y-m-d 00:00:00', strtotime('-' . ($days - 1) . ' days'));

        $adminTotal    = (int) Admin::count();
        $adminEnabled  = (int) Admin::where('status', 1)->count();
        $adminDisabled = (int) Admin::where('status', 0)->count();
        $roleCount     = (int) Role::count();
        $frontendRules = (int) Rule::where('scope', 'frontend')->count();
        $backendApis   = (int) Rule::where('scope', 'backend')->where('type', 'api')->count();

        $todayActive = (int) AdminOperationLog::where('create_time', '>=', $todayStart)
            ->group('admin_id')
            ->count();
        $todayOps = (int) AdminOperationLog::where('create_time', '>=', $todayStart)->count();
        $todayLogin = (int) AdminOperationLog::where('create_time', '>=', $todayStart)
            ->whereLike('action', 'auth.login%')->count();
        $todayWriteOps = max(0, $todayOps - $todayLogin);

        $trendRows = AdminOperationLog::where('create_time', '>=', $trendStart)
            ->field([
                'DATE(create_time) AS day',
                'COUNT(*) AS ops',
                'COUNT(DISTINCT admin_id) AS active_users',
                "SUM(CASE WHEN action LIKE 'auth.login%' THEN 1 ELSE 0 END) AS logins",
            ])
            ->group('DATE(create_time)')
            ->order('day', 'asc')
            ->select()
            ->toArray();

        $trendByDay = [];
        foreach ($trendRows as $row) {
            $trendByDay[(string) $row['day']] = [
                'ops'          => (int) $row['ops'],
                'active_users' => (int) $row['active_users'],
                'logins'       => (int) $row['logins'],
                'write_ops'    => max(0, (int) $row['ops'] - (int) $row['logins']),
            ];
        }

        $dates        = [];
        $opsSeries    = [];
        $writeSeries  = [];
        $activeSeries = [];
        $loginSeries  = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-{$i} days"));
            $dates[] = $d;
            $bucket  = $trendByDay[$d] ?? ['ops' => 0, 'write_ops' => 0, 'active_users' => 0, 'logins' => 0];
            $opsSeries[]    = $bucket['ops'];
            $writeSeries[]  = $bucket['write_ops'];
            $activeSeries[] = $bucket['active_users'];
            $loginSeries[]  = $bucket['logins'];
        }

        $topAdminRows = AdminOperationLog::alias('l')
            ->leftJoin('auth_admin a', 'a.id = l.admin_id')
            ->where('l.create_time', '>=', $trendStart)
            ->field([
                'l.admin_id',
                'COUNT(*) AS count',
                'MAX(a.username) AS username',
                'MAX(a.nickname) AS nickname',
            ])
            ->group('l.admin_id')
            ->order('count', 'desc')
            ->limit(5)
            ->select()
            ->toArray();

        $topAdmins = array_map(static function (array $row): array {
            $nickname = trim((string) ($row['nickname'] ?? ''));
            $username = (string) ($row['username'] ?? '');
            return [
                'admin_id' => (int) $row['admin_id'],
                'name'     => $nickname !== '' ? $nickname : ($username !== '' ? $username : '未知'),
                'count'    => (int) $row['count'],
            ];
        }, $topAdminRows);

        $topActionRows = AdminOperationLog::where('create_time', '>=', $trendStart)
            ->whereNotLike('action', 'auth.login%')
            ->field(['action', 'COUNT(*) AS count'])
            ->group('action')
            ->order('count', 'desc')
            ->limit(10)
            ->select()
            ->toArray();

        $topActions = array_map(static fn (array $row): array => [
            'action' => (string) $row['action'],
            'count'  => (int) $row['count'],
        ], $topActionRows);

        $roleDistRows = AdminRole::alias('ar')
            ->leftJoin('auth_role r', 'r.id = ar.role_id')
            ->field(['ar.role_id', 'MAX(IFNULL(r.name, \'未分配\')) AS role_name', 'COUNT(*) AS count'])
            ->group('ar.role_id')
            ->order('count', 'desc')
            ->select()
            ->toArray();

        $roleDistribution = array_map(static fn (array $row): array => [
            'role_name' => (string) $row['role_name'],
            'count'     => (int) $row['count'],
        ], $roleDistRows);

        $moduleActionRows = AdminOperationLog::where('create_time', '>=', $trendStart)
            ->whereNotLike('action', 'auth.login%')
            ->field(['action', 'COUNT(*) AS count'])
            ->group('action')
            ->select()
            ->toArray();

        $moduleCounts = [];
        foreach ($moduleActionRows as $row) {
            $module = self::actionModule((string) $row['action']);
            if (!isset($moduleCounts[$module])) {
                $moduleCounts[$module] = 0;
            }
            $moduleCounts[$module] += (int) $row['count'];
        }
        arsort($moduleCounts);
        $moduleDistribution = [];
        foreach (array_slice($moduleCounts, 0, 8, true) as $module => $count) {
            $moduleDistribution[] = ['module' => $module, 'count' => $count];
        }

        $recentRows = AdminOperationLog::alias('l')
            ->leftJoin('auth_admin a', 'a.id = l.admin_id')
            ->field('l.id,l.admin_id,l.action,l.summary,l.ip,l.create_time,a.username,a.nickname')
            ->order('l.id', 'desc')
            ->limit(self::RECENT_LOGS_LIMIT)
            ->select()
            ->toArray();

        $recentLogs = array_map(static function (array $row): array {
            $nickname = trim((string) ($row['nickname'] ?? ''));
            $username = (string) ($row['username'] ?? '');
            return [
                'id'          => (int) $row['id'],
                'admin_id'    => (int) $row['admin_id'],
                'admin_name'  => $nickname !== '' ? $nickname : ($username !== '' ? $username : '未知'),
                'action'      => (string) $row['action'],
                'summary'     => (string) $row['summary'],
                'ip'          => $row['ip'] ?? null,
                'create_time' => $row['create_time'] ?? null,
            ];
        }, $recentRows);

        return $this->ok([
            'kpi' => [
                'admin_total'         => $adminTotal,
                'admin_enabled'       => $adminEnabled,
                'admin_disabled'      => $adminDisabled,
                'role_count'          => $roleCount,
                'frontend_rule_count' => $frontendRules,
                'backend_api_count'   => $backendApis,
                'today_active_users'  => $todayActive,
                'today_ops'           => $todayOps,
                'today_write_ops'     => $todayWriteOps,
                'today_login_count'   => $todayLogin,
            ],
            'trend' => [
                'days'         => $days,
                'dates'        => $dates,
                'ops'          => $opsSeries,
                'write_ops'    => $writeSeries,
                'active_users' => $activeSeries,
                'logins'       => $loginSeries,
            ],
            'top_admins'          => $topAdmins,
            'top_actions'         => $topActions,
            'role_distribution'   => $roleDistribution,
            'module_distribution' => $moduleDistribution,
            'recent_logs'         => $recentLogs,
        ]);
    }

    /**
     * actionModule 从 action 解析模块名（如 POST.auth.Admin/save → auth.Admin）
     */
    private static function actionModule(string $action): string
    {
        if (str_starts_with($action, 'auth.login')) {
            return 'auth.login';
        }
        $parts = explode('.', $action, 2);
        if (count($parts) < 2) {
            return $action;
        }
        $path = explode('/', $parts[1], 2)[0];

        return $path !== '' ? $path : $parts[1];
    }
}
