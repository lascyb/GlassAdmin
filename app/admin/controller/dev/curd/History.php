<?php

namespace app\admin\controller\dev\curd;

use app\common\controller\AdminController;
use app\Request;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\Response;

class History extends AdminController
{
    /**
     * @return Response
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function index(Request $request): Response
    {
        if (!$request->isGet()) {
            return $this->fail(405, '仅支持 GET');
        }
        $request->middleware();
        $limit = $request->get("limit",10);
        $page = $request->get("page",1);
        $q = \app\admin\model\dev\curd\History::field("id,name,create_time")->order("id","desc");
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
}