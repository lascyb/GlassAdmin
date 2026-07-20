<?php
declare(strict_types=1);

namespace app\admin\controller\account\login;

use app\admin\model\Admin;
use app\admin\service\AdminTokenService;
use think\Response;

/**
 * Refresh 刷新访问令牌
 */
class Refresh extends BaseLogin
{
    /**
     * token 用 refresh_token 换发新的 access_token 与 refresh_token
     * @return Response
     */
    public function token(): Response
    {
        if (!$this->request->isPost()) {
            return $this->fail(405, '仅支持 POST');
        }

        $refreshToken = trim((string) $this->request->param('refreshToken', ''));
        if ($refreshToken === '') {
            $refreshToken = trim((string) $this->request->param('refresh_token', ''));
        }
        if ($refreshToken === '') {
            return $this->fail(400, '缺少 refresh_token');
        }

        $pair = AdminTokenService::rotate($refreshToken);
        if ($pair === null) {
            return $this->fail(401, '刷新令牌无效或已过期', null, 401);
        }

        $authPayload = AdminTokenService::authenticateAccess($pair['access_token']);
        if ($authPayload === null) {
            return $this->fail(500, '签发 access 失败');
        }

        /** @var Admin|null $user */
        $user = Admin::find($authPayload->adminId);
        if (!$user) {
            return $this->fail(401, '用户不存在', null, 401);
        }

        return $this->ok([
            'access_token'      => $pair['access_token'],
            'refresh_token'     => $pair['refresh_token'] ?? '',
            'expire_at'         => $pair['expire_at'],
            'refresh_expire_at' => $pair['refresh_expire_at'],
            'user'              => [
                'id'       => (int) $user->getAttr('id'),
                'username' => (string) $user->getAttr('username'),
                'nickname' => (string) ($user->getAttr('nickname') ?: $user->getAttr('username')),
                'avatar'   => $user->getAttr('avatar'),
                'role_ids' => $authPayload->roleIds,
            ],
        ]);
    }
}
