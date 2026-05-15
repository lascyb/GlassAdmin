<?php
declare (strict_types = 1);

namespace app\controller\account;

use app\BaseController;
use app\model\AdminNotification;
use think\Request;
use think\Response;

/**
 * Notification 当前登录管理员的站内消息通知
 */
class Notification extends BaseController
{
    /** notificationsBriefMax GET 通知列表在 Header 等场景下的 limit 上限 */
    private const int NOTIFICATIONS_BRIEF_MAX = 50;

    /**
     * index 当前用户通知列表（支持 category；返回 unread_count）
     */
    public function index(Request $request): Response
    {
        if (!$request->isGet()) {
            return json(['code' => 405, 'msg' => '仅支持 GET', 'data' => null]);
        }
        $adminId  = (int) $request->admin_id;
        $category = trim((string) $request->get('category', ''));
        if ($category !== '' && !in_array($category, ['system', 'friend'], true)) {
            return json(['code' => 400, 'msg' => 'category 无效', 'data' => null]);
        }
        $page  = max(1, (int) $request->get('page', 1));
        $limit = min(self::NOTIFICATIONS_BRIEF_MAX, max(1, (int) $request->get('limit', 20)));
        $q     = AdminNotification::where('admin_id', $adminId);
        if ($category !== '') {
            $q->where('category', $category);
        }
        $unreadQ = AdminNotification::where('admin_id', $adminId)->whereNull('read_at');
        if ($category !== '') {
            $unreadQ->where('category', $category);
        }
        $unreadCount = (int) $unreadQ->count();
        $paginator = $q->order('id', 'desc')->paginate([
            'list_rows' => $limit,
            'page'      => $page,
        ]);

        return json([
            'code' => 0,
            'msg'  => 'ok',
            'data' => [
                'list'         => $paginator->items(),
                'total'        => $paginator->total(),
                'page'         => $paginator->currentPage(),
                'limit'        => $paginator->listRows(),
                'unread_count' => $unreadCount,
            ],
        ]);
    }

    /**
     * markRead 标记通知已读（ids 或 all）
     */
    public function markRead(Request $request): Response
    {
        if (!$request->isPut()) {
            return json(['code' => 405, 'msg' => '仅支持 PUT', 'data' => null]);
        }
        $adminId = (int) $request->admin_id;
        $all     = (bool) $request->put('all', false);
        $now     = date('Y-m-d H:i:s');
        if ($all) {
            AdminNotification::where('admin_id', $adminId)->whereNull('read_at')->update(['read_at' => $now]);
        } else {
            $ids = $request->put('ids', []);
            if (!is_array($ids) || $ids === []) {
                return json(['code' => 400, 'msg' => '请传 ids 数组或 all=true', 'data' => null]);
            }
            $ids = array_values(array_unique(array_map('intval', $ids)));
            $ids = array_filter($ids, static fn (int $x): bool => $x > 0);
            if ($ids === []) {
                return json(['code' => 400, 'msg' => 'ids 无效', 'data' => null]);
            }
            AdminNotification::where('admin_id', $adminId)
                ->whereIn('id', $ids)
                ->whereNull('read_at')
                ->update(['read_at' => $now]);
        }

        return json(['code' => 0, 'msg' => 'ok', 'data' => null]);
    }
}
