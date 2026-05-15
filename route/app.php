<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
use app\middleware\AdminAuth;
use app\middleware\AdminOperationLog;
use app\middleware\ApiThrottle;
use think\facade\Route;

Route::get('', function () {
    return "GlassAdmin v0.0.1";
});
Route::miss(function () {
    return json(['code' => 404,"msg"=>"请求的接口不存在"],404);
});

Route::group('api', function () {
    Route::group(function () {
        Route::get('security/rsa-public-key', 'Security/rsaPublicKey');
        Route::post('login/password', 'Login/password');
        Route::post('login/refresh', 'Login/refresh');
    })->middleware(ApiThrottle::class);

    Route::group(function () {
        Route::get('menu/menus', 'Menu/menus');

        Route::get('auth/rule/tree', 'auth.Rule/tree');
        Route::get('auth/rule/read', 'auth.Rule/read');
        Route::post('auth/rule/save', 'auth.Rule/save');
        Route::put('auth/rule/update', 'auth.Rule/update');
        Route::delete('auth/rule/delete', 'auth.Rule/delete');
        Route::post('auth/rule/bindApis', 'auth.Rule/bindApis');
        Route::get('auth/rule/apiList', 'auth.Rule/apiList');

        Route::get('auth/role/tree', 'auth.Role/tree');
        Route::get('auth/role/read', 'auth.Role/read');
        Route::post('auth/role/save', 'auth.Role/save');
        Route::put('auth/role/update', 'auth.Role/update');
        Route::delete('auth/role/delete', 'auth.Role/delete');
        Route::put('auth/role/assignRules', 'auth.Role/assignRules');

        Route::get('auth/admin/list', 'auth.Admin/list');
        Route::get('auth/admin/read', 'auth.Admin/read');
        Route::post('auth/admin/save', 'auth.Admin/save');
        Route::put('auth/admin/update', 'auth.Admin/update');
        Route::delete('auth/admin/delete', 'auth.Admin/delete');

        Route::get('account/profile', 'account.Profile/read');
        Route::put('account/profile', 'account.Profile/update');
        Route::post('account/profile/avatar', 'account.Profile/uploadAvatar');
        Route::get('account/operation-logs', 'account.OperationLog/index');
        Route::get('account/notifications', 'account.Notification/index');
        Route::put('account/notifications/read', 'account.Notification/markRead');
    })->middleware([AdminAuth::class, ApiThrottle::class, AdminOperationLog::class]);
});
