<?php
declare (strict_types = 1);

namespace app\controller\system;

use app\BaseController;
use app\service\SystemConfigService;
use think\Request;
use think\Response;

/**
 * Config 系统配置管理控制器
 */
class Config extends BaseController
{
    /** list 默认每页条数 */
    private const int LIMIT_DEFAULT = 20;

    /**
     * list 配置列表（分页 + 筛选）
     */
    public function list(Request $request): Response
    {
        if (!$request->isGet()) {
            return json(['code' => 405, 'msg' => '仅支持 GET', 'data' => null]);
        }
        $page    = max(1, (int) $request->get('page', 1));
        $limit   = min(100, max(1, (int) $request->get('limit', self::LIMIT_DEFAULT)));
        $group   = trim((string) $request->get('group', ''));
        $keyword = trim((string) $request->get('keyword', ''));

        $paginator = SystemConfigService::getList($page, $limit, $group, $keyword);

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
     * read 配置详情
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
        $row = \app\model\SystemConfig::find($id);
        if (!$row) {
            return json(['code' => 404, 'msg' => '不存在', 'data' => null]);
        }

        return json(['code' => 0, 'msg' => 'ok', 'data' => $row->toArray()]);
    }

    /**
     * groups 获取所有配置分组
     */
    public function groups(Request $request): Response
    {
        if (!$request->isGet()) {
            return json(['code' => 405, 'msg' => '仅支持 GET', 'data' => null]);
        }
        $groups = SystemConfigService::getGroups();

        return json(['code' => 0, 'msg' => 'ok', 'data' => $groups]);
    }

    /**
     * save 新建配置
     */
    public function save(Request $request): Response
    {
        if (!$request->isPost()) {
            return json(['code' => 405, 'msg' => '仅支持 POST', 'data' => null]);
        }
        $data = $request->post();
        try {
            $this->validate($data, \app\validate\system\Config::class . '.save');
        } catch (\think\exception\ValidateException $e) {
            return json(['code' => 400, 'msg' => $e->getError() ?: $e->getMessage(), 'data' => null]);
        }
        $id = SystemConfigService::saveConfig($data);
        if ($id <= 0) {
            return json(['code' => 500, 'msg' => '创建失败', 'data' => null]);
        }

        return json(['code' => 0, 'msg' => 'ok', 'data' => ['id' => $id]]);
    }

    /**
     * update 更新配置
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
        $data = $request->put();

        $row = \app\model\SystemConfig::find($id);
        if (!$row) {
            return json(['code' => 404, 'msg' => '不存在', 'data' => null]);
        }

        // 系统内置配置仅允许编辑：说明、默认值、排序
        if ((int) $row->getAttr('status') === 2) {
            $data = array_intersect_key($data, array_flip(['value', 'description', 'sort']));
        }

        try {
            $this->validate($data, \app\validate\system\Config::class . '.update');
        } catch (\think\exception\ValidateException $e) {
            return json(['code' => 400, 'msg' => $e->getError() ?: $e->getMessage(), 'data' => null]);
        }
        $result = SystemConfigService::saveConfig($data, $id);
        if ($result <= 0) {
            return json(['code' => 404, 'msg' => '不存在或更新失败', 'data' => null]);
        }

        return json(['code' => 0, 'msg' => 'ok', 'data' => null]);
    }

    /**
     * delete 删除配置
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
        $row = \app\model\SystemConfig::find($id);
        if (!$row) {
            return json(['code' => 404, 'msg' => '不存在', 'data' => null]);
        }
        if ((int) $row->getAttr('status') === 2) {
            return json(['code' => 403, 'msg' => '系统内置配置不可删除', 'data' => null]);
        }
        $ok = SystemConfigService::deleteConfig($id);
        if (!$ok) {
            return json(['code' => 500, 'msg' => '删除失败', 'data' => null]);
        }

        return json(['code' => 0, 'msg' => 'ok', 'data' => null]);
    }

    /**
     * batchSave 批量保存配置（按 group）
     */
    public function batchSave(Request $request): Response
    {
        if (!$request->isPut()) {
            return json(['code' => 405, 'msg' => '仅支持 PUT', 'data' => null]);
        }
        $group = trim((string) $request->put('group', ''));
        $pairs = $request->put('pairs', []);
        if ($group === '') {
            return json(['code' => 400, 'msg' => 'group 不能为空', 'data' => null]);
        }
        if (!is_array($pairs) || $pairs === []) {
            return json(['code' => 400, 'msg' => 'pairs 不能为空', 'data' => null]);
        }
        SystemConfigService::batchSave($group, $pairs);

        return json(['code' => 0, 'msg' => 'ok', 'data' => null]);
    }

}
