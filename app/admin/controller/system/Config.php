<?php
declare (strict_types = 1);

namespace app\admin\controller\system;

use app\admin\service\SystemConfigService;
use app\common\controller\AdminController;
use think\Request;
use think\Response;

/**
 * Config 系统配置管理控制器
 */
class Config extends AdminController
{
    /** list 默认每页条数 */
    private const int LIMIT_DEFAULT = 20;

    /** @var list<string> 已登录可读配置值，不校验 RBAC */
    protected array $loginOnlyActions = ['values'];

    /**
     * values 读取启用中的配置键值（可选 keys 逗号分隔或数组）
     */
    public function values(Request $request): Response
    {
        if (!$request->isGet()) {
            return $this->fail(405, '仅支持 GET');
        }
        $raw = $request->get('keys', []);
        $keys = [];
        if (is_string($raw) && $raw !== '') {
            $keys = array_values(array_filter(array_map('trim', explode(',', $raw))));
        } elseif (is_array($raw)) {
            $keys = array_values(array_filter(array_map(static fn ($k): string => trim((string) $k), $raw)));
        }
        if ($keys !== []) {
            return $this->ok(SystemConfigService::getConfigs($keys));
        }

        $grouped = SystemConfigService::getAllConfigs();
        $flat = [];
        foreach ($grouped as $pairs) {
            foreach ($pairs as $k => $v) {
                $flat[$k] = $v;
            }
        }

        return $this->ok($flat);
    }

    /**
     * list 配置列表（分页 + 筛选）
     */
    public function list(Request $request): Response
    {
        if (!$request->isGet()) {
            return $this->fail(405, '仅支持 GET');
        }
        $page    = max(1, (int) $request->get('page', 1));
        $limit   = min(100, max(1, (int) $request->get('limit', self::LIMIT_DEFAULT)));
        $group   = trim((string) $request->get('group', ''));
        $keyword = trim((string) $request->get('keyword', ''));

        $paginator = SystemConfigService::getList($page, $limit, $group, $keyword);

        return $this->paginate(
            $paginator->items(),
            $paginator->total(),
            $paginator->currentPage(),
            $paginator->listRows()
        );
    }

    /**
     * read 配置详情
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
        $row = \app\admin\model\system\Config::find($id);
        if (!$row) {
            return $this->fail(404, '不存在');
        }

        return $this->ok($row->toArray());
    }

    /**
     * groups 获取所有配置分组
     */
    public function groups(Request $request): Response
    {
        if (!$request->isGet()) {
            return $this->fail(405, '仅支持 GET');
        }
        $groups = SystemConfigService::getGroups();

        return $this->ok($groups);
    }

    /**
     * save 新建配置
     */
    public function save(Request $request): Response
    {
        if (!$request->isPost()) {
            return $this->fail(405, '仅支持 POST');
        }
        $data = $request->post();
        try {
            $this->validate($data, \app\admin\validate\system\Config::class . '.save');
        } catch (\think\exception\ValidateException $e) {
            return $this->fail(400, $e->getError() ?: $e->getMessage());
        }
        $id = SystemConfigService::saveConfig($data);
        if ($id <= 0) {
            return $this->fail(500, '创建失败');
        }

        return $this->ok(['id' => $id]);
    }

    /**
     * update 更新配置
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
        $data = $request->put();

        $row = \app\admin\model\system\Config::find($id);
        if (!$row) {
            return $this->fail(404, '不存在');
        }

        // 系统内置配置仅允许编辑：说明、默认值、排序
        if ((int) $row->getAttr('status') === 2) {
            $data = array_intersect_key($data, array_flip(['value', 'description', 'sort']));
        }

        try {
            $this->validate($data, \app\admin\validate\system\Config::class . '.update');
        } catch (\think\exception\ValidateException $e) {
            return $this->fail(400, $e->getError() ?: $e->getMessage());
        }
        $result = SystemConfigService::saveConfig($data, $id);
        if ($result <= 0) {
            return $this->fail(404, '不存在或更新失败');
        }

        return $this->ok();
    }

    /**
     * delete 删除配置
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
        $row = \app\admin\model\system\Config::find($id);
        if (!$row) {
            return $this->fail(404, '不存在');
        }
        if ((int) $row->getAttr('status') === 2) {
            return $this->fail(403, '系统内置配置不可删除');
        }
        $ok = SystemConfigService::deleteConfig($id);
        if (!$ok) {
            return $this->fail(500, '删除失败');
        }

        return $this->ok();
    }

    /**
     * batchSave 批量保存配置（按 group）
     */
    public function batchSave(Request $request): Response
    {
        if (!$request->isPut()) {
            return $this->fail(405, '仅支持 PUT');
        }
        $group = trim((string) $request->put('group', ''));
        $pairs = $request->put('pairs', []);
        if ($group === '') {
            return $this->fail(400, 'group 不能为空');
        }
        if (!is_array($pairs) || $pairs === []) {
            return $this->fail(400, 'pairs 不能为空');
        }
        SystemConfigService::batchSave($group, $pairs);

        return $this->ok();
    }

}
