<?php
declare (strict_types=1);

namespace app\controller;

use app\BaseController;
use app\service\MenuService;
use think\Response;

/**
 * Menu 动态侧栏菜单接口（逻辑见 MenuService）
 */
class Menu extends BaseController
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

        return json([
            'code' => 0,
            'msg'  => 'ok',
            'data' => ['menus' => $menus],
        ]);
    }
}
