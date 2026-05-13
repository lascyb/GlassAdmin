<?php

namespace app\controller;

use app\BaseController;
use app\service\RsaKeyService;
use think\Response;

class Security extends BaseController
{
    /**
     * 获取登录 RSA 公钥
     *
     * @return Response JSON {code,msg,data}
     */
    public function rsaPublicKey(): Response
    {
        $key = (new RsaKeyService())->getAvailablePublicKey();
        if ($key === null) {
            return json([
                'code' => 500,
                'msg'  => '公钥生成失败',
                'data' => null,
            ]);
        }

        return json([
            'code' => 0,
            'msg'  => 'ok',
            'data' => [
                'serial'    => $key['serial'],
                'publicKey' => $key['public_key'],
                'expireAt'  => $key['expire_time'],
            ],
        ])->cacheControl("public, max-age=60");
    }
}
