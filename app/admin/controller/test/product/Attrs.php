<?php
declare (strict_types = 1);

namespace app\admin\controller\test\product;

use app\admin\model\test\product\Attrs as AttrsModel;
use app\common\controller\AdminController;
use think\Request;
use think\Response;

/**
 * Attrs Attrs CRUD 控制器（CRUD 生成）
 *
 * API: list / read / create / update / delete
 */
class Attrs extends AdminController
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
        $keyword = trim((string) $request->get('keyword', ''));
        $key = trim((string) $request->get('key', ''));
        $value = trim((string) $request->get('value', ''));

        $q = AttrsModel::order(['key' => 'desc']);

        if ($keyword !== '') {
            $kw = addcslashes($keyword, '%_\\');
            $q->where('key|value', 'like', '%' . $kw . '%');
        }

        if ($key !== '') {
            $q->where('key', 'like', '%' . addcslashes($key, '%_\\') . '%');
        }

        if ($value !== '') {
            $q->where('value', 'like', '%' . addcslashes($value, '%_\\') . '%');
        }


        $paginator = $q->paginate([
            'list_rows' => $limit,
            'page' => $page,
        ]);

        $items = array_map(static function (AttrsModel $row): array {
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
        $id = trim((string) $request->get('id', ''));
        if ($id === '') {
            return $this->fail(400, 'id 无效');
        }
        $row = AttrsModel::find($id);
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
            $this->validate($data, \app\admin\validate\test\product\Attrs::class . '.create');
        } catch (\think\exception\ValidateException $e) {
            return $this->fail(400, $e->getError() ?: $e->getMessage());
        }

        $m = new AttrsModel();
        $m->save($this->buildPayload($data));
        $id = (string) $m->getAttr('key');

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
        $id = trim((string) $request->get('id', ''));
        if ($id === '') {
            return $this->fail(400, 'id 无效');
        }
        $row = AttrsModel::find($id);
        if (!$row) {
            return $this->fail(404, '不存在');
        }

        $data = $request->put();
        try {
            $this->validate($data, \app\admin\validate\test\product\Attrs::class . '.update');
        } catch (\think\exception\ValidateException $e) {
            return $this->fail(400, $e->getError() ?: $e->getMessage());
        }

        if (array_key_exists('key', $data)) {
            $val = trim((string) $data['key']);
            if ($val !== '' && AttrsModel::where('key', $val)->where('key', '<>', $id)->count() > 0) {
                return $this->fail(400, 'Key已存在');
            }
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
        $id = trim((string) $request->get('id', ''));
        if ($id === '') {
            return $this->fail(400, 'id 无效');
        }
        $row = AttrsModel::find($id);
        if (!$row) {
            return $this->fail(404, '不存在');
        }
        AttrsModel::destroy($id);

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
            'key' => static fn (mixed $v): string => trim((string) $v),
            'value' => static fn (mixed $v): string => trim((string) $v),
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

    /**
     * nullableString 去空白后空串转 null
     */
    private function nullableString(mixed $value): ?string
    {
        $s = trim((string) $value);

        return $s === '' ? null : $s;
    }
}
