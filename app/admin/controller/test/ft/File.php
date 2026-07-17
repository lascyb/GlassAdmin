<?php
declare (strict_types = 1);

namespace app\admin\controller\test\ft;

use Symfony\Component\Filesystem\Exception\FileException;
use app\admin\model\test\ft\File as FileModel;
use app\common\controller\AdminController;
use app\common\helper\CrudHelper;
use think\Request;
use think\Response;
use think\file\UploadedFile;

/**
 * File 文件 CRUD 控制器（CRUD 生成）
 *
 * API: list / read / create / update / delete
 */
class File extends AdminController
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


        $q = FileModel::field('id,attachment')->order(['id' => 'desc']);


        $paginator = $q->paginate([
            'list_rows' => $limit,
            'page' => $page,
        ]);

        $items = array_map(static function (FileModel $row): array {
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
        $row = FileModel::field('id,attachment')->find($id);
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
            $this->validate($data, \app\admin\validate\test\ft\File::class . '.create');
        } catch (\think\exception\ValidateException $e) {
            return $this->fail(400, $e->getError() ?: $e->getMessage());
        }

        $payload = $this->buildPayload($data);

        $m = new FileModel();
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
        $row = FileModel::find($id);
        if (!$row) {
            return $this->fail(404, '不存在');
        }

        $data = $request->put();
        try {
            $this->validate($data, \app\admin\validate\test\ft\File::class . '.update');
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
        $row = FileModel::find($id);
        if (!$row) {
            return $this->fail(404, '不存在');
        }
        FileModel::destroy($id);

        return $this->ok();
    }

    /**
     * uploadAttachment 上传附件
     */
    public function uploadAttachment(Request $request): Response
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

        $dir = rtrim(root_path('public/storage/test/ft'), DIRECTORY_SEPARATOR);
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return $this->fail(500, '存储目录不可用');
        }
        $baseName = bin2hex(random_bytes(16)) . '.' . $rawExt;
        try {
            $saved = $upload->move($dir, $baseName);
        } catch (FileException) {
            return $this->fail(500, '保存失败');
        }

        return $this->ok(['path' => 'test/ft/' . $saved->getFilename()]);
    }

    /**
     * buildPayload 组装可写字段
     *
     * @return array<string, mixed>
     */
    private function buildPayload(array $data, bool $partial = false): array
    {
        $map = [
            'attachment' => fn (mixed $v): ?string => CrudHelper::nullableString($v),
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
