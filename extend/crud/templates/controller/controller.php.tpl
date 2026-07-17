<?php
declare (strict_types = 1);

namespace <%nsController%>;

<%useBlock%>

/**
 * <%entity%> <%title%> CRUD 控制器（CRUD 生成）
 *
 * API: list / read / create / update / delete
 */
class <%entity%> extends AdminController
{
    /** list 默认每页条数 */
    private const int LIMIT_DEFAULT = 20;
<%uploadConst%>
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
<%listParamsBlock%>

        $q = <%listQuery%>;
<%listFilters%>

        $paginator = $q->paginate([
            'list_rows' => $limit,
            'page' => $page,
        ]);

        $items = array_map(<%listMapFn%>, $paginator->items());

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
        <%pkParse%>
<%pkInvalid%>
        $row = <%readQuery%>;
        if (!$row) {
            return $this->fail(404, '不存在');
        }
        $data = $row->toArray();
<%manyRead%>
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
            $this->validate($data, \<%nsValidate%>\<%entity%>::class . '.create');
        } catch (\think\exception\ValidateException $e) {
            return $this->fail(400, $e->getError() ?: $e->getMessage());
        }
<%createManyBefore%>
        $payload = $this->buildPayload($data);
<%autoFillOnCreate%>
        $m = new <%modelAlias%>();
        if ($payload === []) {
            $m->save(['<%pk%>' => null]);
        } else {
            $m->save($payload);
        }
        $id = <%pkGetAttr%>;
<%createManyAfter%>
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
        <%pkParse%>
<%pkInvalid%>
        $row = <%modelAlias%>::find($id);
        if (!$row) {
            return $this->fail(404, '不存在');
        }

        $data = $request->put();
        try {
            $this->validate($data, \<%nsValidate%>\<%entity%>::class . '.update');
        } catch (\think\exception\ValidateException $e) {
            return $this->fail(400, $e->getError() ?: $e->getMessage());
        }
<%uniqueUpdate%>
        $payload = $this->buildPayload($data, true);
        if ($payload !== []) {
            $row->save($payload);
        }
<%manyUpdate%>

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
        <%pkParse%>
<%pkInvalid%>
        $row = <%modelAlias%>::find($id);
        if (!$row) {
            return $this->fail(404, '不存在');
        }
<%manyDelete%>        <%modelAlias%>::destroy($id);

        return $this->ok();
    }
<%uploadMethods%>
    /**
     * buildPayload 组装可写字段
     *
     * @return array<string, mixed>
     */
    private function buildPayload(array $data, bool $partial = false): array
    {
        $map = [
<%payloadMap%>
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
