<?php
declare (strict_types = 1);

namespace app\admin\controller\system\workbench;

use app\admin\model\system\workbench\Shortcut as ShortcutModel;
use app\common\controller\AdminController;
use think\exception\ValidateException;
use think\Request;
use think\Response;

/**
 * Shortcut 工作台快捷入口（当前登录管理员）
 */
class Shortcut extends AdminController
{
    /** @var list<string> 已登录即可读写本人快捷入口 */
    protected array $loginOnlyActions = ['list', 'save'];

    /**
     * list 快捷入口列表（未设置时返回有权限菜单前 8 条默认）
     */
    public function list(Request $request): Response
    {
        if (!$request->isGet()) {
            return $this->fail(405, '仅支持 GET');
        }
        $data = ShortcutModel::listForAdmin(
            $this->auth->adminId,
            $this->auth->roleIds,
        );

        return $this->ok($data);
    }

    /**
     * save 全量保存；空数组恢复默认
     */
    public function save(Request $request): Response
    {
        if (!$request->isPost()) {
            return $this->fail(405, '仅支持 POST');
        }
        $data = $request->post();
        if (!isset($data['rule_ids'])) {
            $data['rule_ids'] = [];
        }
        try {
            $this->validate($data, \app\admin\validate\system\workbench\Shortcut::class . '.save');
        } catch (ValidateException $e) {
            return $this->fail(400, $e->getError() ?: $e->getMessage());
        }

        /** @var list<int|string> $ruleIds */
        $ruleIds = is_array($data['rule_ids']) ? $data['rule_ids'] : [];
        ShortcutModel::saveForAdmin($this->auth->adminId, $ruleIds);

        return $this->ok(true);
    }
}
