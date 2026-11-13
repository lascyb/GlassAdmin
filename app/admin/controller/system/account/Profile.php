<?php
declare (strict_types = 1);

namespace app\admin\controller\system\account;

use app\admin\model\system\auth\Admin as AdminModel;
use app\admin\model\system\auth\AdminOperationLog;
use app\admin\model\system\auth\AdminRole;
use app\admin\model\system\auth\oauth2\Feishu;
use app\admin\model\system\Attachment;
use app\admin\model\system\AttachmentUploadOptions;
use app\admin\service\AdminTokenService;
use app\admin\service\RsaKeyService;
use app\common\controller\AdminController;
use InvalidArgumentException;
use RuntimeException;
use think\facade\Db;
use think\file\UploadedFile;
use think\Request;
use think\Response;

/**
 * Profile 当前登录管理员的个人资料与头像
 */
class Profile extends AdminController
{
    /**
     * @var list<string> 仅需登录的 action；改密由生效登录方式控制，不走按钮 RBAC
     */
    protected array $loginOnlyActions = [
        'read',
        'roles',
        'update',
        'uploadAvatar',
        'switchRoles',
        'operationLogs',
        'changePassword',
    ];

    /** password 明文最小长度（与 system.auth.Admin 一致） */
    private const int PASSWORD_MIN_LEN = 6;

    /** operationLogsMax 操作日志单页 limit 上限 */
    private const int OPERATION_LOGS_MAX = 100;

    /** operationLogsDefaultLimit 操作日志默认每页条数 */
    private const int OPERATION_LOGS_DEFAULT_LIMIT = 10;

    /**
     * read 当前用户资料（不含密码）
     */
    public function read(Request $request): Response
    {
        if (!$request->isGet()) {
            return $this->fail(405, '仅支持 GET');
        }
        $id = $this->auth->adminId;
        $row = AdminModel::field('id,username,nickname,avatar,status,login_methods,create_time,update_time')->find($id);
        if (!$row) {
            return $this->fail(404, '用户不存在');
        }
        $data = $row->toArray();
        $data = array_merge($data, $row->loginMethodsPayload());
        $data["oauth2"] = [];
        $feishu = Feishu::where("admin_id", $id)->field("name,avatar")->find();
        if ($feishu) {
            $data["oauth2"][] = $feishu->toArray();
        }
        return $this->ok($data);
    }
    public function roles(Request $request): Response
    {
        if (!$request->isGet()) {
            return $this->fail(405, '仅支持 GET');
        }
        $id = $this->auth->adminId;
        $data['roles'] = AdminRole::rolesPayload($id);
        return $this->ok($data);
    }

    /**
     * switchRoles 切换当前用户启用的角色并更新 access_token 中的 role_ids
     */
    public function switchRoles(Request $request): Response
    {
        if (!$request->isPut()) {
            return $this->fail(405, '仅支持 PUT');
        }
        $id = $this->auth->adminId;
        $roleIds = $request->put('role_ids');
        if (!is_array($roleIds)) {
            return $this->fail(400, 'role_ids 无效');
        }
        $roleIds = array_values(array_unique(array_filter(array_map('intval', $roleIds), static fn (int $rid): bool => $rid > 0)));
        if ($roleIds === []) {
            return $this->fail(400, '请至少启用一个角色');
        }
        $bound = AdminRole::allRoleIds($id);
        foreach ($roleIds as $roleId) {
            if (!in_array($roleId, $bound, true)) {
                return $this->fail(400, '角色无效');
            }
        }

        Db::startTrans();
        try {
            AdminRole::syncActiveRoles($id, $roleIds);
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();

            return $this->fail(500, '保存失败');
        }

        $accessToken = AdminTokenService::extractBearerToken((string) $request->header('Authorization', ''));
        if ($accessToken === '' || !AdminTokenService::updateAccessRoleIds($accessToken, $roleIds)) {
            return $this->fail(500, '更新登录态失败');
        }

        return $this->ok([
            'roles'    => AdminRole::rolesPayload($id),
        ]);
    }

    /**
     * update 更新当前用户昵称；不可改用户名/角色/状态/密码
     */
    public function update(Request $request): Response
    {
        if (!$request->isPut()) {
            return $this->fail(405, '仅支持 PUT');
        }
        $id  = $this->auth->adminId;
        $row = AdminModel::find($id);
        if (!$row) {
            return $this->fail(404, '用户不存在');
        }
        $nickname = trim((string) $request->put('nickname', ''));
        $row->save([
            'nickname' => $nickname !== '' ? $nickname : null,
        ]);

        return $this->ok();
    }

    /**
     * changePassword 修改当前用户密码（RSA 密文；需生效登录方式含 password）
     */
    public function changePassword(Request $request): Response
    {
        if (!$request->isPut()) {
            return $this->fail(405, '仅支持 PUT');
        }
        $id  = $this->auth->adminId;
        $row = AdminModel::find($id);
        if (!$row) {
            return $this->fail(404, '用户不存在');
        }
        try {
            $row->assertAllowsLoginMethod(AdminModel::METHOD_PASSWORD);
        } catch (InvalidArgumentException $e) {
            return $this->fail(403, $e->getMessage());
        }
        $encPwd = (string) $request->put('password', '');
        if ($encPwd === '') {
            return $this->fail(400, '请输入新密码');
        }
        $serial = trim((string) $request->header('X-RSA-Serial', ''));
        if ($serial === '') {
            return $this->fail(400, '修改密码需携带 X-RSA-Serial');
        }
        $plain = RsaKeyService::decryptBySerial($serial, $encPwd);
        if ($plain === null || $plain === '') {
            return $this->rsaKeyInvalid();
        }
        $err = $this->validatePlainPassword($plain);
        if ($err !== null) {
            return $this->fail(400, $err);
        }
        $row->save([
            'password' => password_hash($plain, PASSWORD_BCRYPT),
        ]);

        return $this->ok();
    }

    /**
     * uploadAvatar 上传当前用户头像（表单字段 file；落盘与附件表由 Attachment::upload 统一处理）
     */
    public function uploadAvatar(Request $request): Response
    {
        if (!$request->isPost()) {
            return $this->fail(405, '仅支持 POST');
        }
        $upload = $request->file('file');
        if (!$upload instanceof UploadedFile) {
            return $this->fail(400, '请上传文件');
        }

        $id  = $this->auth->adminId;
        $row = AdminModel::find($id);
        if (!$row) {
            return $this->fail(404, '用户不存在');
        }

        try {
            $att = Attachment::upload($upload, AttachmentUploadOptions::avatar(
                'public',
                'system.account.Profile/uploadAvatar',
                $id,
            ));
        } catch (InvalidArgumentException $e) {
            return $this->fail(400, $e->getMessage());
        } catch (RuntimeException $e) {
            return $this->fail(500, $e->getMessage());
        }

        $relative = (string) $att->getAttr('path');
        $old = (string) ($row->getOrigin('avatar') ?? '');
        if ($old !== '' && $old !== $relative) {
            Attachment::deleteByPath($old, 'public');
        }
        $row->save(['avatar' => $relative]);

        return $this->ok(['avatar' => $att->getAttr('url') ?? Attachment::publicUrl($relative)]);
    }

    /**
     * operationLogs 当前用户操作日志分页
     */
    public function operationLogs(Request $request): Response
    {
        if (!$request->isGet()) {
            return $this->fail(405, '仅支持 GET');
        }
        $adminId = $this->auth->adminId;
        $page    = max(1, (int) $request->get('page', 1));
        $limit   = min(
            self::OPERATION_LOGS_MAX,
            max(1, (int) $request->get('limit', self::OPERATION_LOGS_DEFAULT_LIMIT))
        );
        $paginator = AdminOperationLog::where('admin_id', $adminId)
            ->field('id,admin_id,summary,ip,create_time')
            ->order('id', 'desc')
            ->paginate([
                'list_rows' => $limit,
                'page'      => $page,
            ]);

        return $this->paginate(
            $paginator->items(),
            $paginator->total(),
            $paginator->currentPage(),
            $paginator->listRows()
        );
    }

    /**
     * validatePlainPassword 校验明文密码长度
     */
    private function validatePlainPassword(string $plain): ?string
    {
        if (strlen($plain) < self::PASSWORD_MIN_LEN) {
            return '密码长度至少 ' . self::PASSWORD_MIN_LEN . ' 位';
        }

        return null;
    }
}
