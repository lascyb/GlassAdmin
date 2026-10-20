<?php
declare(strict_types=1);

namespace app\admin\controller\system\account\login;

use app\admin\model\system\auth\Admin;
use app\admin\service\AdminTokenService;
use app\admin\service\auth\AdminLoginGuardService;
use app\admin\service\auth\LoginGuardException;
use app\common\ApiResponse;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\Response;

/**
 * Refresh 刷新访问令牌
 */
class Refresh extends BaseLogin
{
    /**
     * token 用 refresh_token 换发新的 access_token 与 refresh_token
     * @return Response
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function token(): Response
    {
        if (!$this->request->isPost()) {
            return $this->fail(405, '仅支持 POST');
        }

        $ip = $this->request->ip();

        try {
            AdminLoginGuardService::assertIpAllowed($ip);
        } catch (LoginGuardException $e) {
            return ApiResponse::tooManyRequests($e->getMessage(), [
                'reason'         => $e->reason,
                'remain_seconds' => $e->remainSeconds,
            ]);
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
            AdminLoginGuardService::recordIpFailure($ip, 'refresh_invalid');

            return $this->fail(401, '刷新令牌无效或已过期', null, 401);
        }

        $authPayload = AdminTokenService::authenticateAccess($pair['access_token']);
        if ($authPayload === null) {
            return $this->fail(500, '签发 access 失败');
        }

        /** @var Admin|null $user */
        $user = Admin::find($authPayload->adminId);
        if (!$user) {
            AdminLoginGuardService::recordIpFailure($ip, 'no_user', $authPayload->adminId);

            return $this->fail(401, '用户不存在', null, 401);
        }

        AdminLoginGuardService::recordIpSuccess(
            $ip,
            (int) $user->getAttr('id'),
            (string) $user->getAttr('username'),
        );

        return $this->ok([
            'access_token'      => $pair['access_token'],
            'refresh_token'     => $pair['refresh_token'] ?? '',
            'expire_at'         => $pair['expire_at'],
            'refresh_expire_at' => $pair['refresh_expire_at'],
        ]);
    }
}
