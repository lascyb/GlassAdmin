<?php
declare(strict_types=1);

namespace app\admin\controller\system\account\login;

use app\admin\model\system\auth\Admin;
use app\admin\service\RsaKeyService;
use app\admin\service\auth\AdminLoginGuardService;
use app\admin\service\auth\LoginGuardException;
use app\common\ApiResponse;
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
     * login 用户名密码登录（限流 + 文件登录日志；成功时另写操作日志）
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

        $ip = $this->request->ip();

        try {
            AdminLoginGuardService::assertAttemptAllowed($username, $ip);
        } catch (LoginGuardException $e) {
            return ApiResponse::tooManyRequests($e->getMessage(), [
                'reason'         => $e->reason,
                'remain_seconds' => $e->remainSeconds,
            ]);
        }

        /** @var Admin|null $user */
        $user = Admin::where('username', $username)->find();
        if (!$user) {
            AdminLoginGuardService::recordFailure($username, $ip, 'no_user');

            return $this->fail(401, '用户名或密码错误');
        }

        $adminId = (int) $user->getAttr('id');
        if ((int) $user->getAttr('status') !== 1) {
            AdminLoginGuardService::recordFailure($username, $ip, 'disabled', $adminId);

            return $this->fail(401, '用户名或密码错误');
        }

        $passwordHash = (string) ($user['password'] ?? '');
        if ($passwordHash === '' || !password_verify($password, $passwordHash)) {
            AdminLoginGuardService::recordFailure($username, $ip, 'wrong_password', $adminId);

            return $this->fail(401, '用户名或密码错误');
        }

        AdminLoginGuardService::recordSuccess($username, $ip, $adminId);

        return $this->issueLoginOk(
            $user,
            $remember,
            'auth.login.password',
            '登录成功（密码）' . ($remember ? '，记住我' : ''),
        );
    }
}
