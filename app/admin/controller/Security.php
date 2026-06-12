<?php

namespace app\admin\controller;

use app\admin\service\RsaKeyService;
use app\BaseController;
use app\common\middleware\ApiThrottle;
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
            return $this->fail(500, '安全组件正在维护中，请稍后重试');
        }

        return $this->ok([
            'serial'     => $rsa['serial'],
            'public_key' => $rsa['public_key'],
            'expire_at'  => $rsa['expire_time'],
        ])->cacheControl("public, max-age=60");
    }
}
