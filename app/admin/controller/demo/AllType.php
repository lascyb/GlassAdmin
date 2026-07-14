<?php
declare (strict_types = 1);

namespace app\admin\controller\demo;

use Symfony\Component\Filesystem\Exception\FileException;
use app\admin\model\demo\AllType as AllTypeModel;
use app\admin\model\example\Tag as TagModel;
use app\common\controller\AdminController;
use think\Request;
use think\Response;
use think\file\UploadedFile;

/**
 * AllType 全字段类型 CRUD 控制器（CRUD 生成）
 *
 * API: list / read / create / update / delete
 */
class AllType extends AdminController
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
        $tagIds = $this->parseIdList($request->get('tag_ids'));
        $amountMin = $request->get('amount_min');
        $amountMax = $request->get('amount_max');
        $isTopRaw = $request->get('is_top');
        $statusRaw = $request->get('status');
        $levelRaw = $request->get('level');
        $publishTimeStart = $request->get('publish_time_start');
        $publishTimeEnd = $request->get('publish_time_end');
        $expireDateStart = $request->get('expire_date_start');
        $expireDateEnd = $request->get('expire_date_end');

        $q = AllTypeModel::with([
            'category' => static function ($query): void {
                $query->field('id,name');
            },
            'tags' => static function ($query): void {
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

        if ($tagIds !== []) {
            $q->whereIn('id', static function ($query) use ($tagIds): void {
                $query->table('demo_all_type_tag')->whereIn('tag_id', $tagIds)->field('all_type_id');
            });
        }

        if ($amountMin !== null && $amountMin !== '') {
            $q->where('amount', '>=', (float) $amountMin);
        }
        if ($amountMax !== null && $amountMax !== '') {
            $q->where('amount', '<=', (float) $amountMax);
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

        if ($levelRaw !== null && $levelRaw !== '') {
            $v = (int) $levelRaw;
            if (in_array($v, [1, 2, 3], true)) {
                $q->where('level', $v);
            }
        }

        if ($publishTimeStart !== null && $publishTimeStart !== '') {
            $q->where('publish_time', '>=', (int) $publishTimeStart);
        }
        if ($publishTimeEnd !== null && $publishTimeEnd !== '') {
            $q->where('publish_time', '<=', (int) $publishTimeEnd);
        }

        if ($expireDateStart !== null && $expireDateStart !== '') {
            $q->where('expire_date', '>=', (string) $expireDateStart);
        }
        if ($expireDateEnd !== null && $expireDateEnd !== '') {
            $q->where('expire_date', '<=', (string) $expireDateEnd);
        }


        $paginator = $q->paginate([
            'list_rows' => $limit,
            'page' => $page,
        ]);

        $items = array_map(static function (AllTypeModel $row): array {
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
        $row = AllTypeModel::with([
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
        $data['tag_ids'] = AllTypeModel::tagIdsFor($id);

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
            $this->validate($data, \app\admin\validate\demo\AllType::class . '.create');
        } catch (\think\exception\ValidateException $e) {
            return $this->fail(400, $e->getError() ?: $e->getMessage());
        }

        $ids_tag_ids = $this->parseIdList($data['tag_ids'] ?? null);
        $err = $this->validateRelatedIds($ids_tag_ids, TagModel::class);
        if ($err !== null) {
            return $this->fail(400, $err);
        }

        $m = new AllTypeModel();
        $m->save($this->buildPayload($data));
        $id = (int) $m->getAttr('id');
        AllTypeModel::syncTags($id, $ids_tag_ids);

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
        $row = AllTypeModel::find($id);
        if (!$row) {
            return $this->fail(404, '不存在');
        }

        $data = $request->put();
        try {
            $this->validate($data, \app\admin\validate\demo\AllType::class . '.update');
        } catch (\think\exception\ValidateException $e) {
            return $this->fail(400, $e->getError() ?: $e->getMessage());
        }

        if (array_key_exists('code', $data)) {
            $val = trim((string) $data['code']);
            if ($val !== '' && AllTypeModel::where('code', $val)->where('id', '<>', $id)->count() > 0) {
                return $this->fail(400, '编码已存在');
            }
        }

        $payload = $this->buildPayload($data, true);
        if ($payload !== []) {
            $row->save($payload);
        }

        if (array_key_exists('tag_ids', $data)) {
            $ids = $this->parseIdList($data['tag_ids']);
            $err = $this->validateRelatedIds($ids, TagModel::class);
            if ($err !== null) {
                return $this->fail(400, $err);
            }
            AllTypeModel::syncTags($id, $ids);
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
        $row = AllTypeModel::find($id);
        if (!$row) {
            return $this->fail(404, '不存在');
        }
        AllTypeModel::syncTags($id, []);
        AllTypeModel::destroy($id);

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
            'content' => fn (mixed $v): ?string => $this->nullableString($v),
            'amount' => static fn (mixed $v): float => round((float) $v, 2),
            'quantity' => static fn (mixed $v): int => (int) $v,
            'is_top' => static fn (mixed $v): int => (int) $v === 1 ? 1 : 0,
            'status' => static fn (mixed $v): int => in_array((int) $v, [1, 0], true) ? (int) $v : 1,
            'level' => static fn (mixed $v): int => in_array((int) $v, [1, 2, 3], true) ? (int) $v : 1,
            'color' => fn (mixed $v): ?string => $this->nullableString($v),
            'cover' => fn (mixed $v): ?string => $this->nullableString($v),
            'publish_time' => static fn (mixed $v): ?int => ($ts = (int) $v) > 0 ? $ts : null,
            'expire_date' => fn (mixed $v): ?string => $this->nullableString($v),
            'extra' => fn (mixed $v): ?array => $this->normalizeStringList($v),
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
     * parseIdList 解析 id 列表（数组或逗号分隔）
     *
     * @return list<int>
     */
    private function parseIdList(mixed $raw): array
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
     * validateRelatedIds 校验关联 id
     *
     * @param list<int> $ids
     * @param class-string<\think\Model> $modelClass
     */
    private function validateRelatedIds(array $ids, string $modelClass): ?string
    {
        if ($ids === []) {
            return null;
        }
        $count = (int) $modelClass::where('id', 'in', $ids)->count();
        if ($count !== count($ids)) {
            return '关联数据无效';
        }

        return null;
    }

    /**
     * normalizeStringList 规范化字符串列表
     *
     * @return list<string>|null
     */
    private function normalizeStringList(mixed $value): ?array
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
