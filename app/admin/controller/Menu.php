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
    /**
     * menus 返回当前用户可见菜单树
     *
     * @return Response JSON {code,msg,data:{menus:[]}}
     */
    public function menus(): Response
    {
        $roleId = (int) $this->request->role_id;
        $menus   = MenuService::getMenus($roleId);

        return $this->ok(['menus' => $menus]);
    }
}
