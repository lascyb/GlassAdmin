<?php
declare (strict_types = 1);

namespace app\admin\controller\example;

use app\admin\model\example\Category as CategoryModel;
use app\admin\model\example\Data as DataModel;
use app\admin\model\example\Tag as TagModel;
use app\common\controller\AdminController;
use Symfony\Component\Filesystem\Exception\FileException;
use think\file\UploadedFile;
use think\Request;
use think\Response;

/**
 * Data 示例数据 CRUD（全字段类型 / 关联类型模板）
 */
class Data extends AdminController
{
    /** list 默认每页条数 */
    private const int LIMIT_DEFAULT = 20;

    /** uploadCover 封面最大字节 */
    private const int COVER_MAX_BYTES = 2 * 1024 * 1024;

    /**
     * list 分页列表
     */
    public function list(Request $request): Response
    {
        if (!$request->isGet()) {
            return $this->fail(405, '仅支持 GET');
        }
        $page         = max(1, (int) $request->get('page', 1));
        $limit        = min(100, max(1, (int) $request->get('limit', self::LIMIT_DEFAULT)));
        $keyword      = trim((string) $request->get('keyword', ''));
        $title        = trim((string) $request->get('title', ''));
        $code         = trim((string) $request->get('code', ''));
        $categoryId   = (int) $request->get('category_id', 0);
        $statusRaw    = $request->get('status');
        $levelRaw     = $request->get('level');
        $isTopRaw     = $request->get('is_top');

        $q = DataModel::with([
            'category' => static function ($query): void {
                $query->field('id,name');
            },
            'tags' => static function ($query): void {
                $query->field('id,name');
            },
        ])->order(['sort' => 'asc', 'id' => 'desc']);

        if ($keyword !== '') {
            $kw = addcslashes($keyword, '%_\\');
            $q->where('title|code|remark', 'like', '%' . $kw . '%');
        }
        if ($title !== '') {
            $q->where('title', 'like', '%' . addcslashes($title, '%_\\') . '%');
        }
        if ($code !== '') {
            $q->where('code', 'like', '%' . addcslashes($code, '%_\\') . '%');
        }
        if ($categoryId > 0) {
            $q->where('category_id', $categoryId);
        }
        if ($statusRaw !== null && $statusRaw !== '') {
            $status = (int) $statusRaw;
            if (in_array($status, [0, 1], true)) {
                $q->where('status', $status);
            }
        }
        if ($levelRaw !== null && $levelRaw !== '') {
            $level = (int) $levelRaw;
            if (in_array($level, [1, 2, 3], true)) {
                $q->where('level', $level);
            }
        }
        if ($isTopRaw !== null && $isTopRaw !== '') {
            $isTop = (int) $isTopRaw;
            if (in_array($isTop, [0, 1], true)) {
                $q->where('is_top', $isTop);
            }
        }

        $paginator = $q->paginate([
            'list_rows' => $limit,
            'page'      => $page,
        ]);

        $items = array_map(static function (DataModel $row): array {
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
        $row = DataModel::with([
            'category' => static function ($query): void {
                $query->field('id,name');
            },
            'tags' => static function ($query): void {
                $query->field('id,name');
            },
        ])->find($id);
        if (!$row) {
            return $this->fail(404, '不存在');
        }
        $data = $row->toArray();
        $data['tag_ids'] = DataModel::tagIdsFor($id);

        return $this->ok($data);
    }

    /**
     * options 表单下拉选项（belongs_to / belongs_to_many）
     */
    public function options(Request $request): Response
    {
        if (!$request->isGet()) {
            return $this->fail(405, '仅支持 GET');
        }
        $categories = CategoryModel::where('status', 1)
            ->order(['sort' => 'asc', 'id' => 'asc'])
            ->field('id,name')
            ->select()
            ->toArray();
        $tags = TagModel::where('status', 1)
            ->order(['sort' => 'asc', 'id' => 'asc'])
            ->field('id,name')
            ->select()
            ->toArray();

        return $this->ok([
            'categories' => $categories,
            'tags'       => $tags,
        ]);
    }

    /**
     * save 新建
     */
    public function save(Request $request): Response
    {
        if (!$request->isPost()) {
            return $this->fail(405, '仅支持 POST');
        }
        $data = $request->post();
        try {
            $this->validate($data, \app\admin\validate\example\Data::class . '.save');
        } catch (\think\exception\ValidateException $e) {
            return $this->fail(400, $e->getError() ?: $e->getMessage());
        }

        $tagIds = $this->parseTagIds($data['tag_ids'] ?? null);
        $tagErr = $this->validateTagIds($tagIds);
        if ($tagErr !== null) {
            return $this->fail(400, $tagErr);
        }

        $m = new DataModel();
        $m->save($this->buildPayload($data));
        $id = (int) $m->getAttr('id');
        DataModel::syncTags($id, $tagIds);

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
        $row = DataModel::find($id);
        if (!$row) {
            return $this->fail(404, '不存在');
        }

        $data = $request->put();
        try {
            $this->validate($data, \app\admin\validate\example\Data::class . '.update');
        } catch (\think\exception\ValidateException $e) {
            return $this->fail(400, $e->getError() ?: $e->getMessage());
        }

        if (array_key_exists('code', $data)) {
            $code = trim((string) $data['code']);
            if ($code !== '' && DataModel::where('code', $code)->where('id', '<>', $id)->count() > 0) {
                return $this->fail(400, '编码已存在');
            }
        }

        $payload = $this->buildPayload($data, true);
        if ($payload !== []) {
            $row->save($payload);
        }
        if (array_key_exists('tag_ids', $data)) {
            $tagIds = $this->parseTagIds($data['tag_ids']);
            $tagErr = $this->validateTagIds($tagIds);
            if ($tagErr !== null) {
                return $this->fail(400, $tagErr);
            }
            DataModel::syncTags($id, $tagIds);
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
        $row = DataModel::find($id);
        if (!$row) {
            return $this->fail(404, '不存在');
        }
        DataModel::syncTags($id, []);
        DataModel::destroy($id);

        return $this->ok();
    }

    /**
     * uploadCover 上传封面（image 字段模板）
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
        if ($size <= 0 || $size > self::COVER_MAX_BYTES) {
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

        $dir = rtrim(root_path('public/storage/example'), DIRECTORY_SEPARATOR);
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return $this->fail(500, '存储目录不可用');
        }
        $baseName = bin2hex(random_bytes(16)) . '.' . $rawExt;
        try {
            $saved = $upload->move($dir, $baseName);
        } catch (FileException) {
            return $this->fail(500, '保存失败');
        }

        return $this->ok(['path' => 'example/' . $saved->getFilename()]);
    }

    /**
     * buildPayload 组装可写字段
     *
     * @return array<string, mixed>
     */
    private function buildPayload(array $data, bool $partial = false): array
    {
        $map = [
            'title'        => static fn (mixed $v): string => trim((string) $v),
            'code'         => static fn (mixed $v): string => trim((string) $v),
            'category_id'  => static fn (mixed $v): ?int => ($id = (int) $v) > 0 ? $id : null,
            'content'      => fn (mixed $v): ?string => $this->nullableString($v),
            'amount'       => static fn (mixed $v): float => round((float) $v, 2),
            'quantity'     => static fn (mixed $v): int => (int) $v,
            'is_top'       => static fn (mixed $v): int => (int) $v === 1 ? 1 : 0,
            'status'       => static fn (mixed $v): int => (int) $v === 0 ? 0 : 1,
            'level'        => static fn (mixed $v): int => in_array((int) $v, [1, 2, 3], true) ? (int) $v : 1,
            'color'        => fn (mixed $v): ?string => $this->nullableString($v),
            'cover'        => fn (mixed $v): ?string => $this->nullableString($v),
            'publish_time' => static fn (mixed $v): ?int => ($ts = (int) $v) > 0 ? $ts : null,
            'expire_date'  => fn (mixed $v): ?string => $this->nullableString($v),
            'remark'       => fn (mixed $v): ?string => $this->nullableString($v),
            'sort'         => static fn (mixed $v): int => (int) $v,
            'extra'        => fn (mixed $v): ?array => $this->normalizeExtra($v),
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
     * parseTagIds 解析标签 id 列表
     *
     * @return list<int>
     */
    private function parseTagIds(mixed $raw): array
    {
        if (!is_array($raw)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map('intval', $raw), static fn (int $id): bool => $id > 0)));
    }

    /**
     * validateTagIds 校验标签 id
     */
    private function validateTagIds(array $tagIds): ?string
    {
        if ($tagIds === []) {
            return null;
        }
        $count = (int) TagModel::where('id', 'in', $tagIds)->count();
        if ($count !== count($tagIds)) {
            return '标签无效';
        }

        return null;
    }

    /**
     * normalizeExtra 规范化 json 扩展项
     *
     * @return list<string>|null
     */
    private function normalizeExtra(mixed $value): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (!is_array($decoded)) {
                return null;
            }
            $value = $decoded;
        }
        if (!is_array($value)) {
            return null;
        }
        $items = array_values(array_filter(array_map(static fn ($v): string => trim((string) $v), $value)));

        return $items === [] ? null : $items;
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
