<?php
declare (strict_types=1);

namespace app\admin\controller;

use app\admin\service\MenuService;
use app\common\controller\AdminController;
use think\Response;

/**
 * Menu 动态侧栏菜单接口（逻辑见 MenuService）
 */
class Menu extends AdminController
{
    /** @var list<string> 动态菜单：已登录即可拉取，不校验 RBAC */
    protected array $loginOnlyActions = ['menus'];

    /**
     * menus 返回当前用户可见菜单树
     *
     * @return Response JSON {code,msg,data:{menus:[]}}
     */
    public function menus(): Response
    {
        $roleIds = $this->auth->roleIds;
        $menus   = MenuService::getMenus($roleIds);

        return $this->ok(['menus' => $menus]);
    }
}
