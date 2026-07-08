<?php
declare (strict_types = 1);

namespace app\common\controller;

use app\admin\service\AdminOperationLogService;
use app\admin\service\AdminRbacService;
use app\admin\service\AdminTokenService;
use app\admin\service\auth\AdminAuthPayload;
use app\BaseController;
use app\common\ApiResponse;
use app\common\middleware\ApiThrottle;
use think\exception\HttpResponseException;
use think\Response;

/**
 * AdminController 需登录的管理端 API 基类（鉴权 + 限流 + 操作日志）
 *
 * 子类可覆盖：
 * - $noLoginActions   无需登录的 action
 * - $loginOnlyActions 仅需登录、跳过 RBAC 的 action；含 '*' 表示全部 action
 */
abstract class AdminController extends BaseController
{
    /** 当前请求的鉴权载荷 */
    protected ?AdminAuthPayload $auth = null;

    /** @var list<string> 无需登录的 action 名 */
    protected array $noLoginActions = [];

    /** @var list<string> 仅需登录、跳过 RBAC 的 action 名；'*' 表示全部 */
    protected array $loginOnlyActions = [];

    /**
     * initialize 鉴权与限流
     */
    protected function initialize(): void
    {
        if ($this->request->isOptions()) {
            return;
        }

        $action = $this->request->action();
        if (!$this->actionInList($action, $this->noLoginActions)) {
            $auth = (string) $this->request->header('Authorization', '');
            $token = '';
            if (str_starts_with($auth, 'Bearer ')) {
                $token = trim(substr($auth, 7));
            }
            if ($token === '') {
                $this->abortWith(ApiResponse::unauthorized('未登录'));
            }
            $payload = AdminTokenService::authenticateAccess($token);
            if ($payload === null) {
                $this->abortWith(ApiResponse::unauthorized('登录已失效'));
            }

            $this->auth = $payload;
            AdminAuthPayload::bindForRequest($payload);

            if (!$this->actionInList($action, $this->loginOnlyActions)
                && !$payload->isSuper()
                && !AdminRbacService::isApiAllowed($this->request, $payload->roleIds)) {
                $this->abortWith(ApiResponse::forbidden('无权访问'));
            }
        }

        $throttle = ApiThrottle::enforce($this->request);
        if ($throttle instanceof Response) {
            $this->abortWith($throttle);
        }
    }

    /**
     * ok 成功响应并记录写操作日志
     */
    protected function ok(mixed $data = null, string $msg = 'ok'): Response
    {
        $response = parent::ok($data, $msg);
        AdminOperationLogService::recordIfSuccessfulApiResponse($this->request, $response);

        return $response;
    }

    /**
     * paginate 分页响应并记录写操作日志
     */
    protected function paginate(array $list, int $total, int $page, int $limit): Response
    {
        $response = parent::paginate($list, $total, $page, $limit);
        AdminOperationLogService::recordIfSuccessfulApiResponse($this->request, $response);

        return $response;
    }

    /**
     * abortWith 中断请求并返回响应
     */
    private function abortWith(Response $response): never
    {
        throw new HttpResponseException($response);
    }

    /**
     * actionInList 判断 action 是否在列表中（忽略大小写）；列表含 '*' 时匹配全部
     */
    private function actionInList(string $action, array $list): bool
    {
        foreach ($list as $item) {
            if ($item === '*') {
                return true;
            }
            if (strcasecmp($action, (string) $item) === 0) {
                return true;
            }
        }

        return false;
    }
}
