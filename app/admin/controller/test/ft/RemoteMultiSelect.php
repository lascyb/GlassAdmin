<?php
declare (strict_types = 1);

namespace app\admin\controller\test\ft;

use app\admin\model\test\ft\RemoteMultiSelect as RemoteMultiSelectModel;
use app\admin\model\test\ft\Text as TextModel;
use app\common\controller\AdminController;
use app\common\helper\CrudHelper;
use think\Request;
use think\Response;

/**
 * RemoteMultiSelect 远程多选 CRUD 控制器（CRUD 生成）
 *
 * API: list / read / create / update / delete
 */
class RemoteMultiSelect extends AdminController
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
        $tagIds = CrudHelper::parseIdList($request->get('tag_ids'));

        $q = RemoteMultiSelectModel::field('id')->with([
            'tags' => static function ($query): void {
                $query->field('id,title');
            },
        ])->order(['id' => 'desc']);

        if ($tagIds !== []) {
            $q->whereIn('id', static function ($query) use ($tagIds): void {
                $query->table('test_ft_remote_multi_select_tag')->whereIn('tag_id', $tagIds)->field('remote_multi_select_id');
            });
        }


        $paginator = $q->paginate([
            'list_rows' => $limit,
            'page' => $page,
        ]);

        $items = array_map(static function (RemoteMultiSelectModel $row): array {
            $data = $row->toArray();
            $tags = $data['tags'] ?? [];
            $data['tag_ids'] = array_map(static fn (array $t): int => (int) $t['id'], $tags);

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
        $row = RemoteMultiSelectModel::field('id')->find($id);
        if (!$row) {
            return $this->fail(404, '不存在');
        }
        $data = $row->toArray();
        $data['tag_ids'] = RemoteMultiSelectModel::tagIdsFor($id);

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
            $this->validate($data, \app\admin\validate\test\ft\RemoteMultiSelect::class . '.create');
        } catch (\think\exception\ValidateException $e) {
            return $this->fail(400, $e->getError() ?: $e->getMessage());
        }

        $ids_tag_ids = CrudHelper::parseIdList($data['tag_ids'] ?? null);
        $err = CrudHelper::validateRelatedIds($ids_tag_ids, TextModel::class);
        if ($err !== null) {
            return $this->fail(400, $err);
        }

        $payload = $this->buildPayload($data);

        $m = new RemoteMultiSelectModel();
        if ($payload === []) {
            $m->save(['id' => null]);
        } else {
            $m->save($payload);
        }
        $id = (int) $m->getAttr('id');
        RemoteMultiSelectModel::syncTags($id, $ids_tag_ids);

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
        $row = RemoteMultiSelectModel::find($id);
        if (!$row) {
            return $this->fail(404, '不存在');
        }

        $data = $request->put();
        try {
            $this->validate($data, \app\admin\validate\test\ft\RemoteMultiSelect::class . '.update');
        } catch (\think\exception\ValidateException $e) {
            return $this->fail(400, $e->getError() ?: $e->getMessage());
        }

        $payload = $this->buildPayload($data, true);
        if ($payload !== []) {
            $row->save($payload);
        }

        if (array_key_exists('tag_ids', $data)) {
            $ids = CrudHelper::parseIdList($data['tag_ids']);
            $err = CrudHelper::validateRelatedIds($ids, TextModel::class);
            if ($err !== null) {
                return $this->fail(400, $err);
            }
            RemoteMultiSelectModel::syncTags($id, $ids);
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
        $row = RemoteMultiSelectModel::find($id);
        if (!$row) {
            return $this->fail(404, '不存在');
        }
        RemoteMultiSelectModel::syncTags($id, []);
        RemoteMultiSelectModel::destroy($id);

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
