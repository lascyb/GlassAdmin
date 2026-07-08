<?php
declare (strict_types = 1);

namespace app\admin\controller\account;

use app\admin\service\AdminTokenService;
use app\admin\service\RsaKeyService;
use app\common\controller\AdminController;
use app\admin\model\Admin as AdminModel;
use think\exception\FileException;
use think\file\UploadedFile;
use think\facade\Db;
use think\Request;
use think\Response;

/**
 * Profile 当前登录管理员的个人资料与头像
 */
class Profile extends AdminController
{
    /** @var list<string> */
    protected array $loginOnlyActions = ['*'];

    /** password 明文最小长度（与 auth.Admin 一致） */
    private const int PASSWORD_MIN_LEN = 6;

    /** avatarMaxBytes 头像上传最大字节数 */
    private const int AVATAR_MAX_BYTES = 2 * 1024 * 1024;

    /**
     * read 当前用户资料（不含密码）
     */
    public function read(Request $request): Response
    {
        if (!$request->isGet()) {
            return $this->fail(405, '仅支持 GET');
        }
        $id = $this->auth->adminId;
        $row = AdminModel::field('id,username,nickname,avatar,status,create_time,update_time')->find($id);
        if (!$row) {
            return $this->fail(404, '用户不存在');
        }
        $data = $row->toArray();
        $rolesPayload = AdminModel::rolesPayload($id);
        $data['roles'] = $rolesPayload['roles'];
        $data['role_ids'] = $this->auth->roleIds;

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
        $bound = AdminModel::boundRoleIdsFor($id);
        foreach ($roleIds as $roleId) {
            if (!in_array($roleId, $bound, true)) {
                return $this->fail(400, '角色无效');
            }
        }

        Db::startTrans();
        try {
            AdminModel::syncActiveRoles($id, $roleIds);
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();

            return $this->fail(500, '保存失败');
        }

        $accessToken = AdminTokenService::extractBearerToken((string) $request->header('Authorization', ''));
        if ($accessToken === '' || !AdminTokenService::updateAccessRoleIds($accessToken, $roleIds)) {
            return $this->fail(500, '更新登录态失败');
        }

        $rolesPayload = AdminModel::rolesPayload($id);

        return $this->ok([
            'role_ids' => $roleIds,
            'roles'    => $rolesPayload['roles'],
        ]);
    }

    /**
     * update 更新当前用户昵称、可选密码（RSA）；不可改用户名/角色/状态
     */
    public function update(Request $request): Response
    {
        if (!$request->isPut()) {
            return $this->fail(405, '仅支持 PUT');
        }
        $id   = $this->auth->adminId;
        $row  = AdminModel::find($id);
        if (!$row) {
            return $this->fail(404, '用户不存在');
        }
        $nickname = trim((string) $request->put('nickname', ''));
        $encPwd   = (string) $request->put('password', '');
        $data     = [
            'nickname' => $nickname !== '' ? $nickname : null,
        ];
        if ($encPwd !== '') {
            $serial = trim((string) $request->header('X-RSA-Serial', ''));
            if ($serial === '') {
                return $this->fail(400, '修改密码需携带 X-RSA-Serial');
            }
            $plain = RsaKeyService::decryptBySerial($serial, $encPwd);
            if ($plain === null || $plain === '') {
                return $this->fail(400, '密码解密失败');
            }
            $err = $this->validatePlainPassword($plain);
            if ($err !== null) {
                return $this->fail(400, $err);
            }
            $data['password'] = password_hash($plain, PASSWORD_BCRYPT);
        }
        $row->save($data);

        return $this->ok();
    }

    /**
     * uploadAvatar 上传当前用户头像（表单字段 file，存于 public/storage/avatar/）
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
        if (!$upload->isValid()) {
            return $this->fail(400, '文件无效或过大');
        }
        $size = (int) $upload->getSize();
        if ($size <= 0 || $size > self::AVATAR_MAX_BYTES) {
            return $this->fail(400, '文件大小需在 1B～2MB');
        }
        $rawExt = strtolower($upload->extension());
        if ($rawExt === 'jpeg') {
            $rawExt = 'jpg';
        }
        $allowed = ['jpg', 'png', 'gif', 'webp'];
        if (!in_array($rawExt, $allowed, true)) {
            return $this->fail(400, '仅支持 jpg、png、gif、webp');
        }
        $mime = $upload->getMime();
        $mimeOk = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($mime, $mimeOk, true)) {
            return $this->fail(400, '文件类型与扩展名不符');
        }

        $id  = $this->auth->adminId;
        $row = AdminModel::find($id);
        if (!$row) {
            return $this->fail(404, '用户不存在');
        }

        $dir      = rtrim(root_path('public/storage/avatar'), DIRECTORY_SEPARATOR);
        $baseName = bin2hex(random_bytes(16)) . '.' . $rawExt;
        try {
            $saved = $upload->move($dir, $baseName);
        } catch (FileException) {
            return $this->fail(500, '保存失败');
        }
        $relative = 'avatar/' . $saved->getFilename();

        $old = (string) ($row->getOrigin('avatar') ?? '');
        if ($old !== '' && $old !== $relative) {
            AdminModel::deletePublicStorageAvatar($old);
        }
        $row->save(['avatar' => $relative]);

        return $this->ok(['avatar' => AdminModel::avatarPublicUrl($relative)]);
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
