<?php
declare (strict_types = 1);

namespace app\admin\controller\example;

use app\admin\model\example\Data as DataModel;
use app\admin\model\example\Tag as TagModel;
use app\common\controller\AdminController;
use Symfony\Component\Filesystem\Exception\FileException;
use think\file\UploadedFile;
use think\Request;
use think\Response;

/**
 * Data 示例数据 CRUD 控制器（一键生成主实体范本）
 *
 * 生成文件对应：
 * - Model: app/admin/model/example/Data.php
 * - Validate: app/admin/validate/example/Data.php
 * - 前端: apis/example/data.ts + views/example/data/{index,edit}.vue + constants.ts
 *
 * API: list / read / create / update / delete（image 字段另有 uploadCover）
 * 关联 Category / Tag 为独立控制器，仅提供 list 供 ga-select-remote。
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
        $code         = trim((string) $request->get('code', ''));
        $categoryId   = (int) $request->get('category_id', 0);
        $statusRaw    = $request->get('status');
        $levelRaw     = $request->get('level');
        $isTopRaw     = $request->get('is_top');
        $tagIds       = $this->parseTagIds($request->get('tag_ids'));

        $q = DataModel::with([
            'category' => static function ($query): void {
                $query->field('id,name');
            },
            'tags' => static function ($query): void {
                $query->field('id,name');
            },
        ])->order(['sort' => 'asc', 'id' => 'desc']);

        // keyword ← schema.search=keyword 字段：title|code|remark
        if ($keyword !== '') {
            $kw = addcslashes($keyword, '%_\\');
            $q->where('title|code|remark', 'like', '%' . $kw . '%');
        }
        // 以下为 schema.search=advanced 字段
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
        if ($tagIds !== []) {
            $q->whereIn('id', static function ($query) use ($tagIds): void {
                $query->table('example_data_tag')->whereIn('tag_id', $tagIds)->field('data_id');
            });
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
     * create 新建
     */
    public function create(Request $request): Response
    {
        if (!$request->isPost()) {
            return $this->fail(405, '仅支持 POST');
        }
        $data = $request->post();
        try {
            $this->validate($data, \app\admin\validate\example\Data::class . '.create');
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
     * parseTagIds 解析标签 id 列表（数组或逗号分隔）
     *
     * @return list<int>
     */
    private function parseTagIds(mixed $raw): array
    {
        if (is_string($raw)) {
            $raw = $raw === '' ? [] : explode(',', $raw);
        }
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
