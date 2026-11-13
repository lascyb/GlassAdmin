<?php
declare (strict_types = 1);

namespace app\admin\controller\system\auth;

use app\admin\model\system\auth\Admin as AdminModel;
use app\admin\model\system\auth\AdminRole;
use app\admin\model\system\auth\oauth2\Feishu as OauthFeishu;
use app\admin\model\system\auth\Role as RoleModel;
use app\admin\service\RsaKeyService;
use app\common\controller\AdminController;
use think\Request;
use think\Response;

/**
 * Admin 后台管理员账号 CRUD（密码经 RSA 解密后 bcrypt 存储）
 *
 * 登录方式字段见 {@see AdminModel}：list/read 附带 `login_methods` 与 `login_methods_effective`；
 * 新建时生效含 password 则密码必填，否则随机哈希占位。
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
        $oauthProvider = trim((string) $request->get('oauth_provider', ''));
        $roleId = (int) $request->get('role_id', 0);

        $q = AdminModel::alias('a')->with([
            'roles' => static function ($query): void {
                $query->field('id,name');
            },
        ]);

        if ($oauthProvider === 'feishu') {
            $q->join('system_auth_oauth2_feishu o', 'o.admin_id = a.id');
        } elseif ($oauthProvider === 'none') {
            $q->leftJoin('system_auth_oauth2_feishu o', 'o.admin_id = a.id')
                ->whereNull('o.id');
        }

        if ($roleId > 0) {
            $q->join('system_auth_admin_role ar', 'ar.admin_id = a.id')
                ->where('ar.role_id', $roleId);
        }

        // exclude_role_id 排除已拥有该角色的管理员（批量绑定时只列出未绑定的）
        $excludeRoleId = (int) $request->get('exclude_role_id', 0);
        if ($excludeRoleId > 0) {
            $q->whereNotIn('a.id', function ($query) use ($excludeRoleId): void {
                $query->name('system_auth_admin_role')
                    ->where('role_id', $excludeRoleId)
                    ->field('admin_id');
            });
        }

        $q->order('a.id', 'desc')
            ->field('a.id,a.username,a.nickname,a.avatar,a.status,a.login_methods,a.last_login_time,a.create_time,a.update_time');

        if ($keyword !== '') {
            $kw = addcslashes($keyword, '%_\\');
            $q->where('a.username|a.nickname', 'like', '%' . $kw . '%');
        }
        if ($username !== '') {
            $q->where('a.username', 'like', '%' . addcslashes($username, '%_\\') . '%');
        }
        if ($nickname !== '') {
            $q->where('a.nickname', 'like', '%' . addcslashes($nickname, '%_\\') . '%');
        }
        if ($statusRaw !== null && $statusRaw !== '') {
            $status = (int) $statusRaw;
            if (in_array($status, [0, 1], true)) {
                $q->where('a.status', $status);
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
            $data['oauth_providers'] = [];
            $stored = $row->getAttr('login_methods');
            $data['login_methods'] = $stored === null ? null : AdminModel::normalizeLoginMethods($stored);
            $data['login_methods_effective'] = $row->effectiveLoginMethods();

            return $data;
        }, $paginator->items());

        $items = $this->attachOauthProviders($items);

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
        $data['role_ids'] = array_column($data['roles'] ?? [], 'id');
        $data['oauth_providers'] = [];
        $stored = $row->getAttr('login_methods');
        $data['login_methods'] = $stored === null ? null : AdminModel::normalizeLoginMethods($stored);
        $data['login_methods_effective'] = $row->effectiveLoginMethods();
        $data = $this->attachOauthProviders([$data])[0];

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
        if ($username === '') {
            return $this->fail(400, '用户名缺失');
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

        $loginMethods = $this->parseLoginMethods($request->post('login_methods'));
        $effective = $loginMethods === null
            ? AdminModel::defaultLoginMethods()
            : AdminModel::normalizeLoginMethods($loginMethods);
        $needsPassword = in_array(AdminModel::METHOD_PASSWORD, $effective, true);

        $plain = null;
        if ($encPwd !== '') {
            if ($serial === '') {
                return $this->fail(400, '密钥参数缺失');
            }
            $plain = RsaKeyService::decryptBySerial($serial, $encPwd);
            if ($plain === null || $plain === '') {
                return $this->rsaKeyInvalid();
            }
            $err = $this->validatePlainPassword($plain);
            if ($err !== null) {
                return $this->fail(400, $err);
            }
        } elseif ($needsPassword) {
            return $this->fail(400, '用户名、密码或密钥参数缺失');
        }

        if (AdminModel::where('username', $username)->count() > 0) {
            return $this->fail(400, '用户名已存在');
        }

        // 未开通密码登录时无明文：随机哈希占位（无法用密码登录，仅占位账号）
        $passwordHash = $plain !== null
            ? password_hash($plain, PASSWORD_BCRYPT)
            : password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT);

        $m = new AdminModel();
        $m->save([
            'username'      => $username,
            'password'      => $passwordHash,
            'nickname'      => $nickname !== '' ? $nickname : null,
            'status'        => $status,
            'login_methods' => $loginMethods,
        ]);
        AdminRole::syncRoles((int) $m->getAttr('id'), $roleIds);

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
            $roleIds = AdminRole::allRoleIds($id);
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
        $currentId = $this->auth->adminId;
        if ($id === $currentId && $status !== 1) {
            return $this->fail(400, '不能禁用当前登录账号');
        }

        $loginMethods = $row->getAttr('login_methods');
        $putAll = $request->put();
        if (!is_array($putAll)) {
            $putAll = [];
        }
        if (array_key_exists('login_methods', $putAll)) {
            $loginMethods = $this->parseLoginMethods($putAll['login_methods']);
        }

        $superErr = $this->assertSelfSuperKeepsPassword($id, $roleIds, $loginMethods);
        if ($superErr !== null) {
            return $this->fail(400, $superErr);
        }

        $data = [
            'nickname'      => $nickname !== '' ? $nickname : null,
            'status'        => $status,
            'login_methods' => $loginMethods,
        ];
        if ($encPwd !== '') {
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
            $data['password'] = password_hash($plain, PASSWORD_BCRYPT);
        }
        $row->save($data);
        if ($request->has('role_ids')) {
            AdminRole::syncRoles($id, $roleIds);
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
        if ($id === $this->auth->adminId) {
            return $this->fail(400, '不能删除当前登录账号');
        }
        $row = AdminModel::find($id);
        if (!$row) {
            return $this->fail(404, '不存在');
        }
        AdminRole::where('admin_id', $id)->delete();
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
     * attachOauthProviders 为本页管理员附加 oauth_providers；无本地头像时回填飞书头像
     *
     * @param list<array<string, mixed>> $items
     * @return list<array<string, mixed>>
     */
    private function attachOauthProviders(array $items): array
    {
        if ($items === []) {
            return $items;
        }
        $ids = [];
        foreach ($items as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id > 0) {
                $ids[] = $id;
            }
        }
        if ($ids === []) {
            return $items;
        }

        $binds = OauthFeishu::whereIn('admin_id', $ids)
            ->field('admin_id,avatar')
            ->select();
        /** @var array<int, array{avatar?: string|null}> $byAdmin */
        $byAdmin = [];
        foreach ($binds as $bind) {
            $aid = (int) $bind->getAttr('admin_id');
            $byAdmin[$aid] = [
                'avatar' => $bind->getAttr('avatar'),
            ];
        }

        foreach ($items as &$row) {
            $aid = (int) ($row['id'] ?? 0);
            $providers = [];
            if (isset($byAdmin[$aid])) {
                $providers[] = 'feishu';
                $avatar = $row['avatar'] ?? null;
                if (($avatar === null || $avatar === '') && !empty($byAdmin[$aid]['avatar'])) {
                    $row['avatar'] = $byAdmin[$aid]['avatar'];
                }
            }
            $row['oauth_providers'] = $providers;
        }
        unset($row);

        return $items;
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

    /**
     * parseLoginMethods 解析请求中的 login_methods
     *
     * null / 空串 → null（跟随系统默认）；否则 normalize（显式 [] 表示占位、不开通任何方式）。
     *
     * @return list<string>|null
     */
    private function parseLoginMethods(mixed $raw): ?array
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        return AdminModel::normalizeLoginMethods(is_array($raw) ? $raw : []);
    }

    /**
     * assertSelfSuperKeepsPassword 编辑自己且绑定超管角色（id=1）时，生效集合须含 password
     *
     * @param list<int>         $roleIds      即将保存的角色 id
     * @param list<string>|null $loginMethods 即将保存的登录方式（null=跟随默认）
     * @return string|null 错误文案；通过则 null
     */
    private function assertSelfSuperKeepsPassword(int $id, array $roleIds, ?array $loginMethods): ?string
    {
        if ($id !== $this->auth->adminId || !in_array(1, $roleIds, true)) {
            return null;
        }
        $effective = $loginMethods === null
            ? AdminModel::defaultLoginMethods()
            : AdminModel::normalizeLoginMethods($loginMethods);
        if (!in_array(AdminModel::METHOD_PASSWORD, $effective, true)) {
            return '超级管理员账号必须保留密码登录';
        }

        return null;
    }
}
