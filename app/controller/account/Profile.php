<?php
declare (strict_types = 1);

namespace app\controller\account;

use app\BaseController;
use app\model\Admin as AdminModel;
use app\service\RsaKeyService;
use think\exception\FileException;
use think\file\UploadedFile;
use think\Request;
use think\Response;

/**
 * Profile 当前登录管理员的个人资料与头像
 */
class Profile extends BaseController
{
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
            return json(['code' => 405, 'msg' => '仅支持 GET', 'data' => null]);
        }
        $id = (int) $request->admin_id;
        $row = AdminModel::with([
            'role' => static function ($query): void {
                $query->field('id,name');
            },
        ])->field('id,username,nickname,avatar,role_id,status,create_time,update_time')->find($id);
        if (!$row) {
            return json(['code' => 404, 'msg' => '用户不存在', 'data' => null]);
        }

        return json(['code' => 0, 'msg' => 'ok', 'data' => $row->toArray()]);
    }

    /**
     * update 更新当前用户昵称、可选密码（RSA）；不可改用户名/角色/状态
     */
    public function update(Request $request): Response
    {
        if (!$request->isPut()) {
            return json(['code' => 405, 'msg' => '仅支持 PUT', 'data' => null]);
        }
        $id   = (int) $request->admin_id;
        $row  = AdminModel::find($id);
        if (!$row) {
            return json(['code' => 404, 'msg' => '用户不存在', 'data' => null]);
        }
        $nickname = trim((string) $request->put('nickname', ''));
        $encPwd   = (string) $request->put('password', '');
        $data     = [
            'nickname' => $nickname !== '' ? $nickname : null,
        ];
        if ($encPwd !== '') {
            $serial = trim((string) $request->header('X-RSA-Serial', ''));
            if ($serial === '') {
                return json(['code' => 400, 'msg' => '修改密码需携带 X-RSA-Serial', 'data' => null]);
            }
            $plain = RsaKeyService::decryptBySerial($serial, $encPwd);
            if ($plain === null || $plain === '') {
                return json(['code' => 400, 'msg' => '密码解密失败', 'data' => null]);
            }
            $err = $this->validatePlainPassword($plain);
            if ($err !== null) {
                return json(['code' => 400, 'msg' => $err, 'data' => null]);
            }
            $data['password'] = password_hash($plain, PASSWORD_BCRYPT);
        }
        $row->save($data);

        return json(['code' => 0, 'msg' => 'ok', 'data' => null]);
    }

    /**
     * uploadAvatar 上传当前用户头像（表单字段 file，存于 public/storage/avatar/）
     */
    public function uploadAvatar(Request $request): Response
    {
        if (!$request->isPost()) {
            return json(['code' => 405, 'msg' => '仅支持 POST', 'data' => null]);
        }
        $upload = $request->file('file');
        if (!$upload instanceof UploadedFile) {
            return json(['code' => 400, 'msg' => '请上传文件', 'data' => null]);
        }
        if (!$upload->isValid()) {
            return json(['code' => 400, 'msg' => '文件无效或过大', 'data' => null]);
        }
        $size = (int) $upload->getSize();
        if ($size <= 0 || $size > self::AVATAR_MAX_BYTES) {
            return json(['code' => 400, 'msg' => '文件大小需在 1B～2MB', 'data' => null]);
        }
        $rawExt = strtolower($upload->extension());
        if ($rawExt === 'jpeg') {
            $rawExt = 'jpg';
        }
        $allowed = ['jpg', 'png', 'gif', 'webp'];
        if (!in_array($rawExt, $allowed, true)) {
            return json(['code' => 400, 'msg' => '仅支持 jpg、png、gif、webp', 'data' => null]);
        }
        $mime = $upload->getMime();
        $mimeOk = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($mime, $mimeOk, true)) {
            return json(['code' => 400, 'msg' => '文件类型与扩展名不符', 'data' => null]);
        }

        $id  = (int) $request->admin_id;
        $row = AdminModel::find($id);
        if (!$row) {
            return json(['code' => 404, 'msg' => '用户不存在', 'data' => null]);
        }

        $dir      = rtrim(root_path('public/storage/avatar'), DIRECTORY_SEPARATOR);
        $baseName = bin2hex(random_bytes(16)) . '.' . $rawExt;
        try {
            $saved = $upload->move($dir, $baseName);
        } catch (FileException) {
            return json(['code' => 500, 'msg' => '保存失败', 'data' => null]);
        }
        $relative = 'avatar/' . $saved->getFilename();

        $old = (string) ($row->getOrigin('avatar') ?? '');
        if ($old !== '' && $old !== $relative) {
            AdminModel::deletePublicStorageAvatar($old);
        }
        $row->save(['avatar' => $relative]);

        return json([
            'code' => 0,
            'msg'  => 'ok',
            'data' => ['avatar' => AdminModel::avatarPublicUrl($relative)],
        ]);
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
