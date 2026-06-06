<?php
declare (strict_types=1);

namespace app\controller;

use app\BaseController;
use app\middleware\ApiThrottle;
use app\model\Admin;
use app\service\AdminOperationLogService;
use app\service\AuthService;
use app\service\RsaKeyService;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\Response;

class Login extends BaseController
{
    /** @var array 控制器中间件 */
    protected array $middleware = [
        ApiThrottle::class,
    ];

    /**
     * password 用户名密码登录（仅成功时写入 admin_operation_log，失败不写库）
     *
     * @return Response JSON {code,msg,data}
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function password(): Response
    {
        if (!$this->request->isPost()) {
            return json([
                'code' => 405,
                'msg' => '仅支持 POST',
                'data' => null,
            ]);
        }
        $serial = trim((string)$this->request->header('X-RSA-Serial', ''));
        $encryptedUsername = (string)$this->request->post('username', '');
        $encryptedPassword = (string)$this->request->post('password', '');
        $remember = (bool)$this->request->post('remember', false);
        if ($serial === '' || $encryptedUsername === '' || $encryptedPassword === '') {
            return json([
                'code' => 400,
                'msg' => '参数缺失',
                'data' => null,
            ]);
        }
        $username = trim((string)RsaKeyService::decryptBySerial($serial, $encryptedUsername));
        $password = (string)RsaKeyService::decryptBySerial($serial, $encryptedPassword);
        if ($username === '' || $password === '') {
            return json([
                'code' => 400,
                'msg' => '密钥无效或已过期',
                'data' => null,
            ]);
        }
        /** @var Admin|null $user */
        $user = Admin::where('username', $username)->find();
        if (!$user || (int)$user->getAttr('status') !== 1) {
            return json([
                'code' => 401,
                'msg' => '用户名或密码错误',
                'data' => null,
            ]);
        }
        $passwordHash = (string)($user['password'] ?? '');
        if ($passwordHash === '' || !password_verify($password, $passwordHash)) {
            return json([
                'code' => 401,
                'msg' => '用户名或密码错误',
                'data' => null,
            ]);
        }
        $pair = AuthService::issue(
            (int)$user->getAttr('id'),
            (int)$user->getAttr('role_id'),
            $remember
        );
        AdminOperationLogService::record(
            (int)$user->getAttr('id'),
            'auth.login.password',
            '登录成功（密码）' . ($remember ? '，记住我' : ''),
            $this->request->ip(),
        );

        return json([
            'code' => 0,
            'msg' => '登录成功',
            'data' => [
                'access_token' => $pair['access_token'],
                'expire_at' => $pair['expire_at'],
                'refresh_token' => $pair['refresh_token'] ?? '',
                'refresh_expire_at' => $pair['refresh_expire_at'],
                'user' => [
                    'id' => (int)$user->getAttr('id'),
                    'username' => (string)$user->getAttr('username'),
                    'nickname' => (string)($user->getAttr('nickname') ?: $user->getAttr('username')),
                    'avatar' => $user->getAttr('avatar'),
                    'role_id' => $user->getAttr('role_id'),
                ],
            ],
        ]);
    }

    /**
     * refresh 用 refresh_token 换发新的 access_token 与 refresh_token
     * @return Response JSON {code,msg,data}
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function refresh(): Response
    {
        if (!$this->request->isPost()) {
            return json([
                'code' => 405,
                'msg' => '仅支持 POST',
                'data' => null,
            ]);
        }
        $refreshToken = trim((string)$this->request->param('refreshToken', ''));
        if ($refreshToken === '') {
            $refreshToken = trim((string)$this->request->param('refresh_token', ''));
        }
        if ($refreshToken === '') {
            return json([
                'code' => 400,
                'msg' => '缺少 refresh_token',
                'data' => null,
            ]);
        }
        $pair = AuthService::refresh($refreshToken);
        if ($pair === null) {
            return json([
                'code' => 401,
                'msg' => '刷新令牌无效或已过期',
                'data' => null,
            ], 401);
        }
        $authPayload = AuthService::authenticate($pair['access_token']);
        if ($authPayload === null) {
            return json([
                'code' => 500,
                'msg' => '签发 access 失败',
                'data' => null,
            ]);
        }
        /** @var Admin|null $user */
        $user = Admin::find($authPayload->adminId);
        if (!$user) {
            return json([
                'code' => 401,
                'msg' => '用户不存在',
                'data' => null,
            ], 401);
        }

        return json([
            'code' => 0,
            'msg' => 'ok',
            'data' => [
                'access_token' => $pair['access_token'],
                'refresh_token' => $pair['refresh_token'] ?? '',
                'expire_at' => $pair['expire_at'],
                'refresh_expire_at' => $pair['refresh_expire_at'],
                'user' => [
                    'id' => (int)$user->getAttr('id'),
                    'username' => (string)$user->getAttr('username'),
                    'nickname' => (string)($user->getAttr('nickname') ?: $user->getAttr('username')),
                    'avatar' => $user->getAttr('avatar'),
                    'role_id' => $user->getAttr('role_id'),
                ],
            ],
        ]);
    }
}
