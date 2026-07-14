<?php
declare (strict_types = 1);

namespace app\admin\controller\demo;

use Symfony\Component\Filesystem\Exception\FileException;
use app\admin\model\demo\Item as ItemModel;
use app\common\controller\AdminController;
use think\Request;
use think\Response;
use think\file\UploadedFile;

/**
 * Item 演示条目 CRUD 控制器（CRUD 生成）
 *
 * API: list / read / create / update / delete
 */
class Item extends AdminController
{
    /** list 默认每页条数 */
    private const int LIMIT_DEFAULT = 20;

    /** UPLOAD_MAX_BYTES 上传最大字节 */
    private const int UPLOAD_MAX_BYTES = 2 * 1024 * 1024;

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
        $code = trim((string) $request->get('code', ''));
        $categoryId = (int) $request->get('category_id', 0);
        $isTopRaw = $request->get('is_top');
        $statusRaw = $request->get('status');

        $q = ItemModel::with([
            'category' => static function ($query): void {
                $query->field('id,name');
            },
        ])->order(['sort' => 'asc', 'id' => 'desc']);

        if ($keyword !== '') {
            $kw = addcslashes($keyword, '%_\\');
            $q->where('title|remark', 'like', '%' . $kw . '%');
        }

        if ($code !== '') {
            $q->where('code', 'like', '%' . addcslashes($code, '%_\\') . '%');
        }

        if ($categoryId > 0) {
            $q->where('category_id', $categoryId);
        }

        if ($isTopRaw !== null && $isTopRaw !== '') {
            $v = (int) $isTopRaw;
            if (in_array($v, [0, 1], true)) {
                $q->where('is_top', $v);
            }
        }

        if ($statusRaw !== null && $statusRaw !== '') {
            $v = (int) $statusRaw;
            if (in_array($v, [1, 0], true)) {
                $q->where('status', $v);
            }
        }


        $paginator = $q->paginate([
            'list_rows' => $limit,
            'page' => $page,
        ]);

        $items = array_map(static function (ItemModel $row): array {
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
        $row = ItemModel::with([
            'category' => static function ($query): void {
                $query->field('id,name');
            },
        ])->find($id);
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
            $this->validate($data, \app\admin\validate\demo\Item::class . '.create');
        } catch (\think\exception\ValidateException $e) {
            return $this->fail(400, $e->getError() ?: $e->getMessage());
        }

        $m = new ItemModel();
        $m->save($this->buildPayload($data));
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
        $row = ItemModel::find($id);
        if (!$row) {
            return $this->fail(404, '不存在');
        }

        $data = $request->put();
        try {
            $this->validate($data, \app\admin\validate\demo\Item::class . '.update');
        } catch (\think\exception\ValidateException $e) {
            return $this->fail(400, $e->getError() ?: $e->getMessage());
        }

        if (array_key_exists('code', $data)) {
            $val = trim((string) $data['code']);
            if ($val !== '' && ItemModel::where('code', $val)->where('id', '<>', $id)->count() > 0) {
                return $this->fail(400, '编码已存在');
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
        $id = (int) $request->get('id', 0);
        if ($id <= 0) {
            return $this->fail(400, 'id 无效');
        }
        $row = ItemModel::find($id);
        if (!$row) {
            return $this->fail(404, '不存在');
        }
        ItemModel::destroy($id);

        return $this->ok();
    }

    /**
     * uploadCover 上传封面
     */
    public function uploadCover(Request $request): Response
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
        if ($size <= 0 || $size > self::UPLOAD_MAX_BYTES) {
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

        $dir = rtrim(root_path('public/storage/demo'), DIRECTORY_SEPARATOR);
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return $this->fail(500, '存储目录不可用');
        }
        $baseName = bin2hex(random_bytes(16)) . '.' . $rawExt;
        try {
            $saved = $upload->move($dir, $baseName);
        } catch (FileException) {
            return $this->fail(500, '保存失败');
        }

        return $this->ok(['path' => 'demo/' . $saved->getFilename()]);
    }

    /**
     * buildPayload 组装可写字段
     *
     * @return array<string, mixed>
     */
    private function buildPayload(array $data, bool $partial = false): array
    {
        $map = [
            'title' => static fn (mixed $v): string => trim((string) $v),
            'code' => static fn (mixed $v): string => trim((string) $v),
            'category_id' => static fn (mixed $v): ?int => ($id = (int) $v) > 0 ? $id : null,
            'quantity' => static fn (mixed $v): int => (int) $v,
            'is_top' => static fn (mixed $v): int => (int) $v === 1 ? 1 : 0,
            'status' => static fn (mixed $v): int => in_array((int) $v, [1, 0], true) ? (int) $v : 1,
            'cover' => fn (mixed $v): ?string => $this->nullableString($v),
            'publish_time' => static fn (mixed $v): ?int => ($ts = (int) $v) > 0 ? $ts : null,
            'remark' => fn (mixed $v): ?string => $this->nullableString($v),
            'sort' => static fn (mixed $v): int => (int) $v,
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
