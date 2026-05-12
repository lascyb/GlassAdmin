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
use think\facade\Route;

Route::get('version', function () {
    return json(['code' => 0, 'msg' => 'ok', 'data' => ['version' => 'v0.0.1']]);
});

Route::group('api', function () {
    Route::get('security/rsa-public-key', 'Security/rsaPublicKey');
    Route::post('login/password', 'Login/password');

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
    })->middleware(AdminAuth::class);
});
