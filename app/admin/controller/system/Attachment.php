<?php
declare (strict_types = 1);

namespace app\admin\controller\system;

use app\admin\model\system\Attachment as AttachmentModel;
use app\common\controller\AdminController;
use think\Request;
use think\Response;

/**
 * Attachment 系统附件管理（列表 / 详情 / 删除；上传另议）
 */
class Attachment extends AdminController
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
        $page    = max(1, (int) $request->get('page', 1));
        $limit   = min(100, max(1, (int) $request->get('limit', self::LIMIT_DEFAULT)));
        $keyword = trim((string) $request->get('keyword', ''));
        $scene   = trim((string) $request->get('scene', ''));
        $mime    = trim((string) $request->get('mime', ''));
        $adminRaw = $request->get('admin_id');

        $q = AttachmentModel::with([
            'admin' => static function ($query): void {
                $query->field('id,username,nickname');
            },
        ])->order('id', 'desc');

        if ($keyword !== '') {
            $kw = addcslashes($keyword, '%_\\');
            $q->where('name|path', 'like', '%' . $kw . '%');
        }
        if ($scene !== '') {
            $q->where('scene', $scene);
        }
        if ($mime !== '') {
            $q->where('mime', 'like', addcslashes($mime, '%_\\') . '%');
        }
        if ($adminRaw !== null && $adminRaw !== '') {
            $adminId = (int) $adminRaw;
            if ($adminId > 0) {
                $q->where('admin_id', $adminId);
            }
        }

        $paginator = $q->paginate([
            'list_rows' => $limit,
            'page'      => $page,
        ]);

        return $this->paginate(
            $paginator->items(),
            $paginator->total(),
            $paginator->currentPage(),
            $paginator->listRows()
        );
    }

    /**
     * read 附件详情
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
        $row = AttachmentModel::with([
            'admin' => static function ($query): void {
                $query->field('id,username,nickname');
            },
        ])->find($id);
        if (!$row) {
            return $this->fail(404, '不存在');
        }

        return $this->ok($row->toArray());
    }

    /**
     * delete 删除附件（库记录 + 磁盘文件）
     */
    public function delete(Request $request): Response
    {
        if (!$request->isDelete()) {
            return $this->fail(405, '仅支持 DELETE');
        }
        $id = (int) $request->param('id', 0);
        $err = AttachmentModel::deleteById($id);
        if ($err !== null) {
            $code = $err === '不存在' ? 404 : 400;

            return $this->fail($code, $err);
        }

        return $this->ok(null);
    }
}
