<?php
declare(strict_types=1);

namespace app\admin\controller\account\login;

use app\admin\model\auth\Admin;
use app\admin\service\RsaKeyService;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\Response;

/**
 * Password 用户名密码登录
 */
class Password extends BaseLogin
{
    /**
     * login 用户名密码登录（仅成功时写操作日志）
     * @return Response
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function login(): Response
    {
        if (!$this->request->isPost()) {
            return $this->fail(405, '仅支持 POST');
        }

        $serial = trim((string) $this->request->header('X-RSA-Serial', ''));
        $encryptedUsername = (string) $this->request->post('username', '');
        $encryptedPassword = (string) $this->request->post('password', '');
        $remember = (bool) $this->request->post('remember', false);
        if ($serial === '' || $encryptedUsername === '' || $encryptedPassword === '') {
            return $this->fail(400, '参数缺失');
        }

        $username = trim((string) RsaKeyService::decryptBySerial($serial, $encryptedUsername));
        $password = (string) RsaKeyService::decryptBySerial($serial, $encryptedPassword);
        if ($username === '' || $password === '') {
            return $this->rsaKeyInvalid();
        }

        /** @var Admin|null $user */
        $user = Admin::where('username', $username)->find();
        if (!$user || (int) $user->getAttr('status') !== 1) {
            return $this->fail(401, '用户名或密码错误');
        }

        $passwordHash = (string) ($user['password'] ?? '');
        if ($passwordHash === '' || !password_verify($password, $passwordHash)) {
            return $this->fail(401, '用户名或密码错误');
        }

        return $this->issueLoginOk(
            $user,
            $remember,
            'auth.login.password',
            '登录成功（密码）' . ($remember ? '，记住我' : ''),
        );
    }
}
