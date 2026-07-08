<?php
declare (strict_types = 1);

namespace app\admin\controller\auth;

use app\admin\model\auth\Role as RoleModel;
use app\admin\service\RsaKeyService;
use app\common\controller\AdminController;
use app\admin\model\Admin as AdminModel;
use think\facade\Db;
use think\Request;
use think\Response;

/**
 * Admin 后台管理员账号 CRUD（密码经 RSA 解密后 bcrypt 存储）
 */
class Admin extends AdminController
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
            return $this->fail(405, '仅支持 GET');
        }
        $page     = max(1, (int) $request->get('page', 1));
        $limit    = min(100, max(1, (int) $request->get('limit', self::LIMIT_DEFAULT)));
        $keyword  = trim((string) $request->get('keyword', ''));
        $username = trim((string) $request->get('username', ''));
        $nickname = trim((string) $request->get('nickname', ''));
        $statusRaw = $request->get('status');

        $q = AdminModel::with([
            'roles' => static function ($query): void {
                $query->field('id,name');
            },
        ])->order('id', 'desc')->field('id,username,nickname,avatar,status,last_login_time,create_time,update_time');
        if ($keyword !== '') {
            $kw = addcslashes($keyword, '%_\\');
            $q->where('username|nickname', 'like', '%' . $kw . '%');
        }
        if ($username !== '') {
            $q->where('username', 'like', '%' . addcslashes($username, '%_\\') . '%');
        }
        if ($nickname !== '') {
            $q->where('nickname', 'like', '%' . addcslashes($nickname, '%_\\') . '%');
        }
        if ($statusRaw !== null && $statusRaw !== '') {
            $status = (int) $statusRaw;
            if (in_array($status, [0, 1], true)) {
                $q->where('status', $status);
            }
        }

        $paginator = $q->paginate([
            'list_rows' => $limit,
            'page'      => $page,
        ]);
        $items = array_map(static function (AdminModel $row): array {
            $data = $row->toArray();
            unset($data['password']);
            $roles = $data['roles'] ?? [];
            $data['role_ids'] = array_map(static fn (array $r): int => (int) $r['id'], $roles);

            return $data;
        }, $paginator->items());

        return $this->paginate(
            $items,
            $paginator->total(),
            $paginator->currentPage(),
            $paginator->listRows()
        );
    }

    /**
     * read 详情（无密码）
     */
    public function read(Request $request): Response
    {
        if (!$request->isGet()) {
            return $this->fail(405, '仅支持 GET');
        }
        $id = (int) $request->get('id', 0);
        if ($id <= 0) {
            return $this->fail(400, 'id 无效');
        }
        $row = AdminModel::with([
            'roles' => static function ($query): void {
                $query->field('id,name');
            },
        ])->find($id);
        if (!$row) {
            return $this->fail(404, '不存在');
        }
        $data = $row->toArray();
        unset($data['password']);
        $roles = $data['roles'] ?? [];
        $data['role_ids'] = array_map(static fn (array $r): int => (int) $r['id'], $roles);

        return $this->ok($data);
    }

    /**
     * save 新建管理员
     */
    public function save(Request $request): Response
    {
        if (!$request->isPost()) {
            return $this->fail(405, '仅支持 POST');
        }
        $username = trim((string) $request->post('username', ''));
        $nickname = trim((string) $request->post('nickname', ''));
        $roleIds  = $this->parseRoleIds($request->post('role_ids'));
        $status   = (int) $request->post('status', 1);
        $serial   = trim((string) $request->header('X-RSA-Serial', ''));
        $encPwd   = (string) $request->post('password', '');
        if ($username === '' || $encPwd === '' || $serial === '') {
            return $this->fail(400, '用户名、密码或密钥参数缺失');
        }
        if (!preg_match('/^[a-zA-Z0-9_]{3,64}$/', $username)) {
            return $this->fail(400, '用户名须为 3～64 位字母、数字或下划线');
        }
        $roleErr = $this->validateRoleIds($roleIds);
        if ($roleErr !== null) {
            return $this->fail(400, $roleErr);
        }
        if (!in_array($status, [0, 1], true)) {
            return $this->fail(400, 'status 无效');
        }
        $plain = RsaKeyService::decryptBySerial($serial, $encPwd);
        if ($plain === null || $plain === '') {
            return $this->fail(400, '密码解密失败');
        }
        $err = $this->validatePlainPassword($plain);
        if ($err !== null) {
            return $this->fail(400, $err);
        }
        if (AdminModel::where('username', $username)->count() > 0) {
            return $this->fail(400, '用户名已存在');
        }
        $m = new AdminModel();
        $m->save([
            'username' => $username,
            'password' => password_hash($plain, PASSWORD_BCRYPT),
            'nickname' => $nickname !== '' ? $nickname : null,
            'status'   => $status,
        ]);
        AdminModel::syncRoles((int) $m->getAttr('id'), $roleIds);

        return $this->ok(['id' => (int) $m->getAttr('id')]);
    }

    /**
     * update 更新管理员（用户名不可改；密码可选，传则 RSA 密文）
     */
    public function update(Request $request): Response
    {
        if (!$request->isPut()) {
            return $this->fail(405, '仅支持 PUT');
        }
        $id = (int) $request->get('id', 0);
        if ($id <= 0) {
            return $this->fail(400, 'id 无效');
        }
        $row = AdminModel::find($id);
        if (!$row) {
            return $this->fail(404, '不存在');
        }
        $nickname = trim((string) $request->put('nickname', ''));
        $roleIds  = $this->parseRoleIds($request->put('role_ids'));
        if ($roleIds === []) {
            $roleIds = AdminModel::roleIdsFor($id);
        }
        $status   = (int) $request->put('status', (int) $row->getAttr('status'));
        $encPwd   = (string) $request->put('password', '');
        $roleErr = $this->validateRoleIds($roleIds);
        if ($roleErr !== null) {
            return $this->fail(400, $roleErr);
        }
        if (!in_array($status, [0, 1], true)) {
            return $this->fail(400, 'status 无效');
        }
        $currentId = (int) $request->admin_id;
        if ($id === $currentId && $status !== 1) {
            return $this->fail(400, '不能禁用当前登录账号');
        }
        $data = [
            'nickname' => $nickname !== '' ? $nickname : null,
            'status'   => $status,
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
        if ($request->has('role_ids')) {
            AdminModel::syncRoles($id, $roleIds);
        }

        return $this->ok();
    }

    /**
     * delete 删除管理员（不可删当前登录账号）
     */
    public function delete(Request $request): Response
    {
        if (!$request->isDelete()) {
            return $this->fail(405, '仅支持 DELETE');
        }
        $id = (int) $request->get('id', 0);
        if ($id <= 0) {
            return $this->fail(400, 'id 无效');
        }
        if ($id === (int) $request->admin_id) {
            return $this->fail(400, '不能删除当前登录账号');
        }
        $row = AdminModel::find($id);
        if (!$row) {
            return $this->fail(404, '不存在');
        }
        Db::name('admin_role')->where('admin_id', $id)->delete();
        AdminModel::destroy($id);

        return $this->ok();
    }

    /**
     * parseRoleIds 解析请求中的角色 id 列表
     *
     * @return list<int>
     */
    private function parseRoleIds(mixed $raw): array
    {
        if (!is_array($raw)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map('intval', $raw), static fn (int $id): bool => $id > 0)));
    }

    /**
     * validateRoleIds 校验角色 id 列表
     */
    private function validateRoleIds(array $roleIds): ?string
    {
        if ($roleIds === []) {
            return '请至少选择一个角色';
        }
        $count = (int) RoleModel::where('id', 'in', $roleIds)->count();
        if ($count !== count($roleIds)) {
            return '角色无效';
        }

        return null;
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
