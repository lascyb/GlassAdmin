<?php
declare (strict_types = 1);

namespace app;

use app\middleware\AdminAuth;
use app\middleware\AdminOperationLog;
use app\middleware\ApiThrottle;

/**
 * AuthController 需登录的管理端 API 基类（鉴权 + 限流 + 操作日志）
 */
abstract class AuthController extends BaseController
{
    /** @var array 控制器中间件 */
    protected array $middleware = [
        AdminAuth::class,
        ApiThrottle::class,
        AdminOperationLog::class,
    ];
}
