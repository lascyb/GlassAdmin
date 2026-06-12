<?php
declare (strict_types = 1);

namespace app\common;

use think\Response;

/**
 * ApiResponse 统一 JSON 响应封装 {code,msg,data}
 */
final class ApiResponse
{
    /**
     * success 成功响应，code=0
     *
     * @param mixed  $data 业务数据
     * @param string $msg  提示信息
     */
    public static function success(mixed $data = null, string $msg = 'ok'): Response
    {
        return json([
            'code' => 0,
            'msg'  => $msg,
            'data' => $data,
        ]);
    }

    /**
     * fail 业务失败响应；默认 HTTP 200，显式传入 $httpStatus 时使用对应状态码
     *
     * @param int         $code       业务码
     * @param string      $msg        提示信息
     * @param mixed       $data       附加数据
     * @param int|null    $httpStatus HTTP 状态码
     */
    public static function fail(int $code, string $msg, mixed $data = null, ?int $httpStatus = null): Response
    {
        return json([
            'code' => $code,
            'msg'  => $msg,
            'data' => $data,
        ], $httpStatus ?? 200);
    }

    /**
     * paginate 分页列表响应
     *
     * @param array $list  当前页数据
     * @param int   $total 总条数
     * @param int   $page  当前页码
     * @param int   $limit 每页条数
     */
    public static function paginate(array $list, int $total, int $page, int $limit): Response
    {
        return self::success([
            'list'  => $list,
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
        ]);
    }

    /**
     * badRequest 400 参数或校验错误
     */
    public static function badRequest(string $msg, mixed $data = null): Response
    {
        return self::fail(400, $msg, $data);
    }

    /**
     * unauthorized 401 未登录或鉴权失败（HTTP 401）
     */
    public static function unauthorized(string $msg = '未登录', mixed $data = null): Response
    {
        return self::fail(401, $msg, $data, 401);
    }

    /**
     * forbidden 403 无权访问（HTTP 403）
     */
    public static function forbidden(string $msg = '无权访问', mixed $data = null): Response
    {
        return self::fail(403, $msg, $data, 403);
    }

    /**
     * notFound 404 资源不存在（HTTP 404）
     */
    public static function notFound(string $msg = '不存在', mixed $data = null): Response
    {
        return self::fail(404, $msg, $data, 404);
    }

    /**
     * methodNotAllowed 405 请求方法不允许
     */
    public static function methodNotAllowed(string $msg = '仅支持 GET'): Response
    {
        return self::fail(405, $msg);
    }

    /**
     * tooManyRequests 429 请求过于频繁（HTTP 429）
     */
    public static function tooManyRequests(string $msg = '请求过于频繁，请稍后再试', mixed $data = null): Response
    {
        return self::fail(429, $msg, $data, 429);
    }

    /**
     * serverError 500 服务器内部错误
     */
    public static function serverError(string $msg, mixed $data = null): Response
    {
        return self::fail(500, $msg, $data);
    }
}
