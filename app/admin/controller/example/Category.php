<?php
declare (strict_types = 1);

namespace app\admin\controller\example;

use app\admin\model\example\Category as CategoryModel;
use app\common\controller\AdminController;
use think\Request;
use think\Response;

/**
 * Category 示例分类（关联依赖：仅 list，供 ga-select-remote）
 * 不随主实体 Data 一键生成；belongs_to 目标需已存在。
 */
class Category extends AdminController
{
    /** list 默认每页条数 */
    private const int LIMIT_DEFAULT = 10;

    /**
     * list 分页列表（适配 ga-select-remote：keyword / init_values / page / limit）
     */
    public function list(Request $request): Response
    {
        if (!$request->isGet()) {
            return $this->fail(405, '仅支持 GET');
        }
        $page    = max(1, (int) $request->get('page', 1));
        $limit   = min(50, max(1, (int) $request->get('limit', self::LIMIT_DEFAULT)));
        $keyword = trim((string) $request->get('keyword', ''));
        $initIds = $this->parseInitValues($request->get('init_values'));

        $q = CategoryModel::where('status', 1)
            ->order(['sort' => 'asc', 'id' => 'asc'])
            ->field('id,name');

        if ($initIds !== []) {
            $q->whereIn('id', $initIds);
        } elseif ($keyword !== '') {
            $q->where('name', 'like', '%' . addcslashes($keyword, '%_\\') . '%');
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
     * parseInitValues 解析 ga-select-remote 回显值
     *
     * @return list<int>
     */
    private function parseInitValues(mixed $raw): array
    {
        if (is_string($raw)) {
            $raw = $raw === '' ? [] : explode(',', $raw);
        }
        if (!is_array($raw)) {
            if (is_numeric($raw)) {
                $raw = [(int) $raw];
            } else {
                return [];
            }
        }

        return array_values(array_unique(array_filter(array_map('intval', $raw), static fn (int $id): bool => $id > 0)));
    }
}
