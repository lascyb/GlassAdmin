<?php
declare (strict_types=1);

namespace app\admin\controller;

use app\admin\service\AdminOperationLogService;
use app\admin\service\AuthService;
use app\admin\service\RsaKeyService;
use app\BaseController;
use app\common\middleware\ApiThrottle;
use app\admin\model\Admin;
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
            return $this->fail(405, '仅支持 POST');
        }
        $serial = trim((string)$this->request->header('X-RSA-Serial', ''));
        $encryptedUsername = (string)$this->request->post('username', '');
        $encryptedPassword = (string)$this->request->post('password', '');
        $remember = (bool)$this->request->post('remember', false);
        if ($serial === '' || $encryptedUsername === '' || $encryptedPassword === '') {
            return $this->fail(400, '参数缺失');
        }
        $username = trim((string)RsaKeyService::decryptBySerial($serial, $encryptedUsername));
        $password = (string)RsaKeyService::decryptBySerial($serial, $encryptedPassword);
        if ($username === '' || $password === '') {
            return $this->fail(400, '密钥无效或已过期');
        }
        /** @var Admin|null $user */
        $user = Admin::where('username', $username)->find();
        if (!$user || (int)$user->getAttr('status') !== 1) {
            return $this->fail(401, '用户名或密码错误');
        }
        $passwordHash = (string)($user['password'] ?? '');
        if ($passwordHash === '' || !password_verify($password, $passwordHash)) {
            return $this->fail(401, '用户名或密码错误');
        }
        $roleIds = Admin::roleIdsFor((int) $user->getAttr('id'));
        if ($roleIds === []) {
            return $this->fail(403, '账号未分配角色');
        }
        $pair = AuthService::issue(
            (int)$user->getAttr('id'),
            $roleIds,
            $remember
        );
        $user->save(['last_login_time' => time()]);
        AdminOperationLogService::record(
            (int)$user->getAttr('id'),
            'auth.login.password',
            '登录成功（密码）' . ($remember ? '，记住我' : ''),
            $this->request->ip(),
        );

        return $this->ok([
            'access_token' => $pair['access_token'],
            'expire_at' => $pair['expire_at'],
            'refresh_token' => $pair['refresh_token'] ?? '',
            'refresh_expire_at' => $pair['refresh_expire_at'],
            'user' => [
                'id' => (int)$user->getAttr('id'),
                'username' => (string)$user->getAttr('username'),
                'nickname' => (string)($user->getAttr('nickname') ?: $user->getAttr('username')),
                'avatar' => $user->getAttr('avatar'),
                'role_ids' => $roleIds,
            ],
        ], '登录成功');
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
            return $this->fail(405, '仅支持 POST');
        }
        $refreshToken = trim((string)$this->request->param('refreshToken', ''));
        if ($refreshToken === '') {
            $refreshToken = trim((string)$this->request->param('refresh_token', ''));
        }
        if ($refreshToken === '') {
            return $this->fail(400, '缺少 refresh_token');
        }
        $pair = AuthService::refresh($refreshToken);
        if ($pair === null) {
            return $this->fail(401, '刷新令牌无效或已过期', null, 401);
        }
        $authPayload = AuthService::authenticate($pair['access_token']);
        if ($authPayload === null) {
            return $this->fail(500, '签发 access 失败');
        }
        /** @var Admin|null $user */
        $user = Admin::find($authPayload->adminId);
        if (!$user) {
            return $this->fail(401, '用户不存在', null, 401);
        }

        return $this->ok([
            'access_token' => $pair['access_token'],
            'refresh_token' => $pair['refresh_token'] ?? '',
            'expire_at' => $pair['expire_at'],
            'refresh_expire_at' => $pair['refresh_expire_at'],
            'user' => [
                'id' => (int)$user->getAttr('id'),
                'username' => (string)$user->getAttr('username'),
                'nickname' => (string)($user->getAttr('nickname') ?: $user->getAttr('username')),
                'avatar' => $user->getAttr('avatar'),
                'role_ids' => $authPayload->roleIds,
            ],
        ]);
    }
}
