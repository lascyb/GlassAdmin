<?php
declare (strict_types = 1);

namespace app\controller\auth;

use app\AuthController;
use app\model\Admin as AdminModel;
use app\model\auth\Role as RoleModel;
use app\service\RsaKeyService;
use think\Request;
use think\Response;

/**
 * Admin 后台管理员账号 CRUD（密码经 RSA 解密后 bcrypt 存储）
 */
class Admin extends AuthController
{
    /** list 默认每页条数（请求参数 limit） */
    private const int LIMIT_DEFAULT = 20;

    /** password 明文最小长度 */
    private const int PASSWORD_MIN_LEN = 6;

    /**
     * list 分页列表
     */
    public function list(Request $request): Response
    {
        if (!$request->isGet()) {
            return json(['code' => 405, 'msg' => '仅支持 GET', 'data' => null]);
        }
        $page    = max(1, (int) $request->get('page', 1));
        $limit   = min(100, max(1, (int) $request->get('limit', self::LIMIT_DEFAULT)));
        $keyword = trim((string) $request->get('keyword', ''));

        $q = AdminModel::with([
            'role' => static function ($query): void {
                $query->field('id,name');
            },
        ])->order('id', 'desc')->field('id,username,nickname,avatar,role_id,status,create_time,update_time');
        if ($keyword !== '') {
            $kw = addcslashes($keyword, '%_\\');
            $q->where('username|nickname', 'like', '%' . $kw . '%');
        }
    
        $paginator = $q->paginate([
            'list_rows' => $limit,
            'page'      => $page,
        ]);
        return json([
            'code' => 0,
            'msg'  => 'ok',
            'data' => [
                'list'  => $paginator->items(),
                'total' => $paginator->total(),
                'page'  => $paginator->currentPage(),
                'limit' => $paginator->listRows(),
            ],
        ]);
    }

    /**
     * read 详情（无密码）
     */
    public function read(Request $request): Response
    {
        if (!$request->isGet()) {
            return json(['code' => 405, 'msg' => '仅支持 GET', 'data' => null]);
        }
        $id = (int) $request->get('id', 0);
        if ($id <= 0) {
            return json(['code' => 400, 'msg' => 'id 无效', 'data' => null]);
        }
        $row = AdminModel::with([
            'role' => static function ($query): void {
                $query->field('id,name');
            },
        ])->find($id);
        if (!$row) {
            return json(['code' => 404, 'msg' => '不存在', 'data' => null]);
        }
        $data = $row->toArray();
        unset($data['password']);

        return json(['code' => 0, 'msg' => 'ok', 'data' => $data]);
    }

    /**
     * save 新建管理员
     */
    public function save(Request $request): Response
    {
        if (!$request->isPost()) {
            return json(['code' => 405, 'msg' => '仅支持 POST', 'data' => null]);
        }
        $username = trim((string) $request->post('username', ''));
        $nickname = trim((string) $request->post('nickname', ''));
        $roleId   = (int) $request->post('role_id', 0);
        $status   = (int) $request->post('status', 1);
        $serial   = trim((string) $request->header('X-RSA-Serial', ''));
        $encPwd   = (string) $request->post('password', '');
        if ($username === '' || $encPwd === '' || $serial === '') {
            return json(['code' => 400, 'msg' => '用户名、密码或密钥参数缺失', 'data' => null]);
        }
        if (!preg_match('/^[a-zA-Z0-9_]{3,64}$/', $username)) {
            return json(['code' => 400, 'msg' => '用户名须为 3～64 位字母、数字或下划线', 'data' => null]);
        }
        if ($roleId <= 0 || !RoleModel::find($roleId)) {
            return json(['code' => 400, 'msg' => '角色无效', 'data' => null]);
        }
        if (!in_array($status, [0, 1], true)) {
            return json(['code' => 400, 'msg' => 'status 无效', 'data' => null]);
        }
        $plain = RsaKeyService::decryptBySerial($serial, $encPwd);
        if ($plain === null || $plain === '') {
            return json(['code' => 400, 'msg' => '密码解密失败', 'data' => null]);
        }
        $err = $this->validatePlainPassword($plain);
        if ($err !== null) {
            return json(['code' => 400, 'msg' => $err, 'data' => null]);
        }
        if (AdminModel::where('username', $username)->count() > 0) {
            return json(['code' => 400, 'msg' => '用户名已存在', 'data' => null]);
        }
        $m = new AdminModel();
        $m->save([
            'username' => $username,
            'password' => password_hash($plain, PASSWORD_BCRYPT),
            'nickname' => $nickname !== '' ? $nickname : null,
            'role_id'  => $roleId,
            'status'   => $status,
        ]);

        return json(['code' => 0, 'msg' => 'ok', 'data' => ['id' => (int) $m->getAttr('id')]]);
    }

    /**
     * update 更新管理员（用户名不可改；密码可选，传则 RSA 密文）
     */
    public function update(Request $request): Response
    {
        if (!$request->isPut()) {
            return json(['code' => 405, 'msg' => '仅支持 PUT', 'data' => null]);
        }
        $id = (int) $request->get('id', 0);
        if ($id <= 0) {
            return json(['code' => 400, 'msg' => 'id 无效', 'data' => null]);
        }
        $row = AdminModel::find($id);
        if (!$row) {
            return json(['code' => 404, 'msg' => '不存在', 'data' => null]);
        }
        $nickname = trim((string) $request->put('nickname', ''));
        $roleId   = (int) $request->put('role_id', (int) $row->getAttr('role_id'));
        $status   = (int) $request->put('status', (int) $row->getAttr('status'));
        $encPwd   = (string) $request->put('password', '');
        if ($roleId <= 0 || !RoleModel::find($roleId)) {
            return json(['code' => 400, 'msg' => '角色无效', 'data' => null]);
        }
        if (!in_array($status, [0, 1], true)) {
            return json(['code' => 400, 'msg' => 'status 无效', 'data' => null]);
        }
        $currentId = (int) $request->admin_id;
        if ($id === $currentId && $status !== 1) {
            return json(['code' => 400, 'msg' => '不能禁用当前登录账号', 'data' => null]);
        }
        $data = [
            'nickname' => $nickname !== '' ? $nickname : null,
            'role_id'  => $roleId,
            'status'   => $status,
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
     * delete 删除管理员（不可删当前登录账号）
     */
    public function delete(Request $request): Response
    {
        if (!$request->isDelete()) {
            return json(['code' => 405, 'msg' => '仅支持 DELETE', 'data' => null]);
        }
        $id = (int) $request->get('id', 0);
        if ($id <= 0) {
            return json(['code' => 400, 'msg' => 'id 无效', 'data' => null]);
        }
        if ($id === (int) $request->admin_id) {
            return json(['code' => 400, 'msg' => '不能删除当前登录账号', 'data' => null]);
        }
        $row = AdminModel::find($id);
        if (!$row) {
            return json(['code' => 404, 'msg' => '不存在', 'data' => null]);
        }
        AdminModel::destroy($id);

        return json(['code' => 0, 'msg' => 'ok', 'data' => null]);
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
