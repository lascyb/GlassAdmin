<?php
declare (strict_types = 1);

namespace app\admin\controller;

use app\BaseController;
use think\Response;

class Dashboard extends BaseController
{
    /**
     * 显示资源
     *
     * @return Response
     */
    public function index(): Response
    {
        return $this->ok();
    }
}
