<?php
declare (strict_types = 1);

namespace app\admin\controller\account;

use app\admin\model\AdminOperationLog;
use app\common\controller\AdminController;
use think\Request;
use think\Response;

/**
 * OperationLog 当前登录管理员的操作日志
 */
class OperationLog extends AdminController
{
    /** @var list<string> */
    protected array $loginOnlyActions = ['*'];

    /** operationLogsMax 操作日志单页 limit 上限 */
    private const int OPERATION_LOGS_MAX = 100;

    /**
     * index 当前用户操作日志分页
     */
    public function index(Request $request): Response
    {
        if (!$request->isGet()) {
            return $this->fail(405, '仅支持 GET');
        }
        $adminId = $this->auth->adminId;
        $page    = max(1, (int) $request->get('page', 1));
        $limit   = min(self::OPERATION_LOGS_MAX, max(1, (int) $request->get('limit', 20)));
        $q       = AdminOperationLog::where('admin_id', $adminId)->order('id', 'desc');
        $paginator = $q->paginate([
            'list_rows' => $limit,
            'page'      => $page,
        ]);

        return $this->paginate(
            $paginator->items(),
            $paginator->total(),
            $paginator->currentPage(),
            $paginator->listRows()
        );
    }
}
