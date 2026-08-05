<?php
declare (strict_types=1);

namespace app\admin\controller\account;

use app\admin\model\auth\Rule as RuleModel;
use app\common\controller\AdminController;
use think\Request;
use think\Response;

/**
 * Menu 动态侧栏菜单与规则描述
 */
class Menu extends AdminController
{
    /** @var list<string> 已登录即可访问 */
    protected array $loginOnlyActions = ['menus', 'describe'];

    /**
     * menus 返回当前用户菜单树；catalog=1 含可发现未授权项
     */
    public function menus(Request $request): Response
    {
        if (!$request->isGet()) {
            return $this->fail(405, '仅支持 GET');
        }
        $catalog = (int) $request->get('catalog', 0) === 1;
        $menus   = RuleModel::menusForRoles($this->auth->roleIds, $catalog);

        return $this->ok(['menus' => $menus]);
    }

    /**
     * describe 无权限页描述（授权或可发现）
     */
    public function describe(Request $request): Response
    {
        if (!$request->isGet()) {
            return $this->fail(405, '仅支持 GET');
        }
        $id = (int) $request->get('id', 0);
        $data = RuleModel::describeForAdmin($id, $this->auth->roleIds);
        if ($data === null) {
            return $this->fail(404, '规则不存在或无权查看');
        }

        return $this->ok($data);
    }
}
