<?php
declare (strict_types = 1);

namespace app\admin\controller\test\ft;

use app\admin\model\test\ft\SwitchField as SwitchFieldModel;
use app\common\controller\AdminController;
use app\common\helper\CrudHelper;
use think\Request;
use think\Response;

/**
 * SwitchField 开关 CRUD 控制器（CRUD 生成）
 *
 * API: list / read / create / update / delete
 */
class SwitchField extends AdminController
{
    /** list 默认每页条数 */
    private const int LIMIT_DEFAULT = 20;

    /**
     * list 分页列表
     */
    public function list(Request $request): Response
    {
        if (!$request->isGet()) {
            return $this->fail(405, '仅支持 GET');
        }
        $page = max(1, (int) $request->get('page', 1));
        $limit = min(100, max(1, (int) $request->get('limit', self::LIMIT_DEFAULT)));
        $isTopRaw = $request->get('is_top');

        $q = SwitchFieldModel::field('id,is_top')->order(['id' => 'desc']);

        if ($isTopRaw !== null && $isTopRaw !== '') {
            $v = (int) $isTopRaw;
            if (in_array($v, [0, 1], true)) {
                $q->where('is_top', $v);
            }
        }


        $paginator = $q->paginate([
            'list_rows' => $limit,
            'page' => $page,
        ]);

        $items = array_map(static function (SwitchFieldModel $row): array {
            return $row->toArray();
        }, $paginator->items());

        return $this->paginate(
            $items,
            $paginator->total(),
            $paginator->currentPage(),
            $paginator->listRows()
        );
    }

    /**
     * read 详情
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
        $row = SwitchFieldModel::field('id,is_top')->find($id);
        if (!$row) {
            return $this->fail(404, '不存在');
        }
        $data = $row->toArray();

        return $this->ok($data);
    }

    /**
     * create 新建
     */
    public function create(Request $request): Response
    {
        if (!$request->isPost()) {
            return $this->fail(405, '仅支持 POST');
        }
        $data = $request->post();
        try {
            $this->validate($data, \app\admin\validate\test\ft\SwitchField::class . '.create');
        } catch (\think\exception\ValidateException $e) {
            return $this->fail(400, $e->getError() ?: $e->getMessage());
        }

        $payload = $this->buildPayload($data);

        $m = new SwitchFieldModel();
        if ($payload === []) {
            $m->save(['id' => null]);
        } else {
            $m->save($payload);
        }
        $id = (int) $m->getAttr('id');

        return $this->ok(['id' => $id]);
    }

    /**
     * update 更新
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
        $row = SwitchFieldModel::find($id);
        if (!$row) {
            return $this->fail(404, '不存在');
        }

        $data = $request->put();
        try {
            $this->validate($data, \app\admin\validate\test\ft\SwitchField::class . '.update');
        } catch (\think\exception\ValidateException $e) {
            return $this->fail(400, $e->getError() ?: $e->getMessage());
        }

        $payload = $this->buildPayload($data, true);
        if ($payload !== []) {
            $row->save($payload);
        }


        return $this->ok();
    }

    /**
     * delete 删除
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
        $row = SwitchFieldModel::find($id);
        if (!$row) {
            return $this->fail(404, '不存在');
        }
        SwitchFieldModel::destroy($id);

        return $this->ok();
    }

    /**
     * buildPayload 组装可写字段
     *
     * @return array<string, mixed>
     */
    private function buildPayload(array $data, bool $partial = false): array
    {
        $map = [
            'is_top' => static fn (mixed $v): int => (int) $v === 1 ? 1 : 0,
        ];

        $payload = [];
        foreach ($map as $key => $fn) {
            if ($partial && !array_key_exists($key, $data)) {
                continue;
            }
            if (!$partial || array_key_exists($key, $data)) {
                $payload[$key] = $fn($data[$key] ?? null);
            }
        }

        return $payload;
    }
}
