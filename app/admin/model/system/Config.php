<?php
declare (strict_types = 1);

namespace app\admin\model\system;

use think\Collection;
use think\Model;
use think\Paginator;

/**
 * SystemConfig 系统配置（数据访问）
 *
 * @property int         $id
 * @property string      $key        配置键名（唯一）
 * @property string      $value      配置值（纯文本存储）
 * @property string      $group      配置分组
 * @property string      $name       配置名称
 * @property string|null $description 配置说明
 * @property string      $type       输入类型
 * @property string|null $options    选项（JSON 数组）
 * @property int         $sort       排序
 * @property int         $status     {1:启用,0:禁用,2:只读启用}
 * @property int         $create_time
 * @property int         $update_time
 */
class Config extends Model
{
    /** @var string 表名 */
    protected $name = 'system_config';

    /** @var list<string>|true */
    protected array|bool $autoWriteTimestamp = true;

    /** @var array<string, string> */
    protected $type = [
        'id'          => 'integer',
        'sort'        => 'integer',
        'status'      => 'integer',
        'create_time' => 'integer',
        'update_time' => 'integer',
    ];

    /** 启用态（可读）：1 启用，2 只读启用 */
    private const array STATUS_ACTIVE = [1, 2];

    /**
     * findValueByKey 按 key 取启用配置的 value
     */
    public static function findValueByKey(string $key): ?string
    {
        $row = self::where('key', $key)
            ->whereIn('status', self::STATUS_ACTIVE)
            ->field('value')
            ->find();
        if (!$row) {
            return null;
        }

        return (string) $row->getAttr('value');
    }

    /**
     * findValuesByKeys 按 key 列表取启用配置键值
     *
     * @param list<string> $keys
     * @return array<string, string>
     */
    public static function findValuesByKeys(array $keys): array
    {
        if ($keys === []) {
            return [];
        }
        $rows = self::where('key', 'in', $keys)
            ->whereIn('status', self::STATUS_ACTIVE)
            ->field('key,value')
            ->select();
        $result = [];
        foreach ($rows as $row) {
            $result[(string) $row->getAttr('key')] = (string) $row->getAttr('value');
        }

        return $result;
    }

    /**
     * findActiveRows 全部启用配置（key/value/group）
     *
     * @return Collection<int, static>
     */
    public static function findActiveRows(): Collection
    {
        return self::whereIn('status', self::STATUS_ACTIVE)
            ->field('key,value,group')
            ->select();
    }

    /**
     * findGroups 启用配置的分组列表
     *
     * @return list<string>
     */
    public static function findGroups(): array
    {
        return self::distinct(true)
            ->whereIn('status', self::STATUS_ACTIVE)
            ->order('group', 'asc')
            ->column('group');
    }

    /**
     * findByKey 按 key 查单行（不限 status，供写操作）
     */
    public static function findByKey(string $key): ?static
    {
        /** @var static|null $row */
        $row = self::where('key', $key)->find();

        return $row;
    }

    /**
     * paginateList 管理列表分页
     */
    public static function paginateList(int $page, int $limit, string $group = '', string $keyword = ''): Paginator
    {
        $q = self::order('group', 'asc')->order('sort', 'asc')->order('id', 'asc');
        if ($group !== '') {
            $q->where('group', $group);
        }
        if ($keyword !== '') {
            $kw = addcslashes($keyword, '%_\\');
            $q->where('key|name', 'like', '%' . $kw . '%');
        }

        return $q->paginate([
            'list_rows' => $limit,
            'page'      => $page,
        ]);
    }

    /**
     * createRow 新建配置行，返回 id
     *
     * @param array<string, mixed> $data
     */
    public static function createRow(array $data): int
    {
        $m = new self();
        $m->save($data);

        return (int) $m->getAttr('id');
    }

    /**
     * upsertValueByKey 按 key 更新 value，不存在则按 group 新建
     */
    public static function upsertValueByKey(string $key, string $value, string $group): void
    {
        $row = self::findByKey($key);
        if ($row) {
            $row->save(['value' => $value]);

            return;
        }
        self::createRow([
            'key'    => $key,
            'value'  => $value,
            'group'  => $group,
            'name'   => $key,
            'type'   => 'input',
            'sort'   => 0,
            'status' => 1,
        ]);
    }
}
