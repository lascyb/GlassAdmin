<?php
declare (strict_types = 1);

namespace app\controller;

use app\BaseController;
use app\model\Admin;
use app\service\RsaKeyService;
use Random\RandomException;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\facade\Cache;
use think\Response;

class Login extends BaseController
{
    /**
     * password 用户名密码登录
     *
     * @return Response JSON {code,msg,data}
     * @throws RandomException
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function password(): Response
    {
        if (!$this->request->isPost()) {
            return json([
                'code'    => 405,
                'msg'     => '仅支持 POST',
                'data'    => null,
            ]);
        }

        $serial = trim((string) $this->request->header('X-RSA-Serial', ''));
        $encryptedUsername = (string) $this->request->post('username', '');
        $encryptedPassword = (string) $this->request->post('password', '');
        $remember = (bool) $this->request->post('remember', false);

        if ($serial === '' || $encryptedUsername === '' || $encryptedPassword === '') {
            return json([
                'code' => 400,
                'msg'  => '参数缺失',
                'data' => null,
            ]);
        }

        $rsaKeyService = new RsaKeyService();
        $username = trim((string) $rsaKeyService->decryptBySerial($serial, $encryptedUsername));
        $password = (string) $rsaKeyService->decryptBySerial($serial, $encryptedPassword);
        if ($username === '' || $password === '') {
            return json([
                'code' => 400,
                'msg'  => '密钥无效或已过期',
                'data' => null,
            ]);
        }

        /** @var Admin|null $user */
        $user = Admin::where('username', $username)->find();
        if (!$user || (int) $user->getAttr('status') !== 1) {
            return json([
                'code' => 401,
                'msg'  => '用户名或密码错误',
                'data' => null,
            ]);
        }

        $passwordHash = (string) ($user['password'] ?? '');
        if ($passwordHash === '' || !password_verify($password, $passwordHash)) {
            return json([
                'code' => 401,
                'msg'  => '用户名或密码错误',
                'data' => null,
            ]);
        }

        $ttl      = $remember ? 604800 : 86400;
        $token    = bin2hex(random_bytes(32));
        $expireAt = time() + $ttl;
        Cache::set('admin_token:' . $token, ['uid' => (int) $user->getAttr('id')], $ttl);

        return json([
            'code' => 0,
            'msg'  => '登录成功',
            'data' => [
                'token'    => $token,
                'expireAt' => $expireAt,
                'user'     => [
                    'id'       => (int) $user->getAttr('id'),
                    'username' => (string) $user->getAttr('username'),
                    'nickname' => (string) ($user->getAttr('nickname') ?: $user->getAttr('username')),
                    'role_id'  => $user->getAttr('role_id') !== null ? (int) $user->getAttr('role_id') : null,
                ],
            ],
        ]);
    }
}
