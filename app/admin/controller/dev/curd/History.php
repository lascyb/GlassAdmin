<?php
declare (strict_types = 1);

namespace app\admin\controller\dev\curd;

use app\common\controller\AdminController;
use app\Request;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\Response;

/**
 * History CRUD 生成历史
 */
class History extends AdminController
{
    /**
     * index 历史列表（分页）
     *
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function index(Request $request): Response
    {
        if (!$request->isGet()) {
            return $this->fail(405, '仅支持 GET');
        }
        $limit = min(100, max(1, (int) $request->get('limit', 10)));
        $page = max(1, (int) $request->get('page', 1));
        $q = \app\admin\model\dev\curd\History::field('id,name,create_time')->order('id', 'desc');
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
     * read 历史详情（含完整 config）
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
        $row = \app\admin\model\dev\curd\History::find($id);
        if (!$row) {
            return $this->fail(404, '不存在');
        }
        $data = $row->toArray();
        $config = json_decode((string) ($data['config'] ?? ''), true);
        $data['configParsed'] = is_array($config) ? $config : null;

        return $this->ok($data);
    }
}
