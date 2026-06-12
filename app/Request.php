<?php

namespace app;

// 应用请求对象类
use app\admin\service\auth\AdminAuthPayload;

class Request extends \think\Request
{
    public AdminAuthPayload|null $auth = null;
    

}
