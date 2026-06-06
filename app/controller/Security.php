<?php

namespace app\controller;

use app\BaseController;
use app\middleware\ApiThrottle;
use app\service\RsaKeyService;
use think\Response;

class Security extends BaseController
{
    /** @var array 控制器中间件 */
    protected array $middleware = [
        ApiThrottle::class,
    ];

    /**
     * 获取加密 RSA 公钥
     *
     * @return Response JSON {code,msg,data}
     */
    public function rsaPublicKey(): Response
    {
        $rsa = RsaKeyService::getAvailablePublicKey();
        if ($rsa === null) {
            return json([
                'code' => 500,
                'msg'  => '安全组件正在维护中，请稍后重试',
                'data' => null,
            ]);
        }

        return json([
            'code' => 0,
            'msg'  => 'ok',
            'data' => [
                'serial'     => $rsa['serial'],
                'public_key' => $rsa['public_key'],
                'expire_at'  => $rsa['expire_time'],
            ],
        ])->cacheControl("public, max-age=60");
    }
}
