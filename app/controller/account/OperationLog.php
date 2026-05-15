<?php
declare (strict_types = 1);

namespace app\controller\account;

use app\BaseController;
use app\model\AdminOperationLog;
use think\Request;
use think\Response;

/**
 * OperationLog 当前登录管理员的操作日志
 */
class OperationLog extends BaseController
{
    /** operationLogsMax 操作日志单页 limit 上限 */
    private const int OPERATION_LOGS_MAX = 100;

    /**
     * index 当前用户操作日志分页
     */
    public function index(Request $request): Response
    {
        if (!$request->isGet()) {
            return json(['code' => 405, 'msg' => '仅支持 GET', 'data' => null]);
        }
        $adminId = (int) $request->admin_id;
        $page    = max(1, (int) $request->get('page', 1));
        $limit   = min(self::OPERATION_LOGS_MAX, max(1, (int) $request->get('limit', 20)));
        $q       = AdminOperationLog::where('admin_id', $adminId)->order('id', 'desc');
        $paginator = $q->paginate([
            'list_rows' => $limit,
            'page'      => $page,
        ]);

        return json([
            'code' => 0,
            'msg'  => 'ok',
            'data' => [
                'list'  => $paginator->items(),
                'total' => $paginator->total(),
                'page'  => $paginator->currentPage(),
                'limit' => $paginator->listRows(),
            ],
        ]);
    }
}
