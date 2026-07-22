<?php
declare(strict_types=1);

namespace app\admin\controller\account\login;

use app\admin\model\auth\Admin;
use app\admin\model\auth\AdminRole;
use app\admin\service\AdminOperationLogService;
use app\admin\service\AdminTokenService;
use app\BaseController;
use app\common\middleware\ApiThrottle;
use Random\RandomException;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\Response;

/**
 * BaseLogin 登录控制器基类（限流 + 签发令牌响应）
 */
abstract class BaseLogin extends BaseController
{
    /** @var array 控制器中间件 */
    protected array $middleware = [
        ApiThrottle::class,
    ];

    /**
     * issueLoginOk 校验角色、签发令牌并返回统一登录成功结构
     * @param Admin $user 管理员
     * @param bool $remember 记住我
     * @param string $action 操作日志 action
     * @param string $summary 操作日志摘要
     * @return Response
     * @throws RandomException
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    protected function issueLoginOk(Admin $user, bool $remember, string $action, string $summary): Response
    {
        $adminId = (int)$user->getAttr('id');
        // 无角色也可登录；业务权限靠 RBAC，个人中心等为 loginOnly
        $roles = AdminRole::rolesPayload($adminId);
        $roleIds = array_column(array_filter($roles,static function ($item) {
            return $item["enabled"] == 1;
        }),"role_id");
        $pair = AdminTokenService::issuePair($adminId, $roleIds, $remember);

        $user->save(['last_login_time' => time()]);
        AdminOperationLogService::record($adminId, $action, $summary, $this->request->ip());

        return $this->ok([
            'access_token' => $pair['access_token'],
            'expire_at' => $pair['expire_at'],
            'refresh_token' => $pair['refresh_token'] ?? '',
            'refresh_expire_at' => $pair['refresh_expire_at'],
            'user' => [
                'id' => $adminId,
                'username' => (string)$user->getAttr('username'),
                'nickname' => (string)($user->getAttr('nickname') ?: $user->getAttr('username')),
                'avatar' => $user->getAttr('avatar'),
                'roles' => $roles,
            ],
        ], '登录成功');
    }
}
