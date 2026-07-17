<?php
declare (strict_types = 1);

namespace app;

use app\common\ApiResponse;
use think\App;
use think\exception\ValidateException;
use think\Response;
use think\Validate;

/**
 * 控制器基础类
 */
abstract class BaseController
{
    /**
     * Request实例
     * @var \think\Request
     */
    protected \think\Request $request;

    /**
     * 应用实例
     * @var App
     */
    protected App $app;

    /**
     * 是否批量验证
     * @var bool
     */
    protected bool $batchValidate = false;

    /**
     * 控制器中间件
     * @var array
     */
    protected array $middleware = [];

    /**
     * 构造方法
     * @access public
     * @param  App  $app  应用对象
     */
    public function __construct(App $app)
    {
        $this->app     = $app;
        $this->request = $this->app->request;

        // 控制器初始化
        $this->initialize();
    }

    // 初始化
    protected function initialize()
    {}

    /**
     * 验证数据
     * @access protected
     * @param  array        $data     数据
     * @param  string|array $validate 验证器名或者验证规则数组
     * @param  array        $message  提示信息
     * @param  bool         $batch    是否批量验证
     * @return array|string|true
     * @throws ValidateException
     */
    protected function validate(array $data, string|array $validate, array $message = [], bool $batch = false): true|array|string
    {
        if (is_array($validate)) {
            $v = new Validate();
            $v->rule($validate);
        } else {
            if (strpos($validate, '.')) {
                // 支持场景
                [$validate, $scene] = explode('.', $validate);
            }
            $class = str_contains($validate, '\\') ? $validate : $this->app->parseClass('validate', $validate);
            $v     = new $class();
            if (!empty($scene)) {
                $v->scene($scene);
            }
        }

        $v->message($message);

        // 是否批量验证
        if ($batch || $this->batchValidate) {
            $v->batch(true);
        }

        return $v->failException(true)->check($data);
    }

    /**
     * ok 成功响应，委托 ApiResponse::success
     */
    protected function ok(mixed $data = null, string $msg = 'ok'): Response
    {
        return ApiResponse::success($data, $msg);
    }

    /**
     * fail 失败响应，委托 ApiResponse::fail
     */
    protected function fail(int $code, string $msg, mixed $data = null, ?int $httpStatus = null): Response
    {
        return ApiResponse::fail($code, $msg, $data, $httpStatus);
    }

    /**
     * rsaKeyInvalid RSA 密钥无效或已过期
     */
    protected function rsaKeyInvalid(string $msg = '密钥无效或已过期', mixed $data = null): Response
    {
        return ApiResponse::rsaKeyInvalid($msg, $data);
    }

    /**
     * paginate 分页响应，委托 ApiResponse::paginate
     */
    protected function paginate(array $list, int $total, int $page, int $limit): Response
    {
        return ApiResponse::paginate($list, $total, $page, $limit);
    }

}
