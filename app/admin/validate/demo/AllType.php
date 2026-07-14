<?php
declare (strict_types = 1);

namespace app\admin\validate\demo;

use think\Validate;

/**
 * AllType 全字段类型校验（CRUD 生成：sceneCreate / sceneUpdate）
 */
class AllType extends Validate
{
    /** @var array<string, string> */
    protected $rule = [
        'title' => 'require|max:100',
        'code' => 'require|max:50|alphaDash',
        'category_id' => 'integer|gt:0',
        'tag_ids' => 'array',
        'content' => 'max:2000',
        'amount' => 'float|egt:0',
        'quantity' => 'integer|egt:0',
        'is_top' => 'in:0,1',
        'status' => 'in:1,0',
        'level' => 'in:1,2,3',
        'color' => 'max:32',
        'cover' => 'max:255',
        'publish_time' => 'integer|egt:0',
        'expire_date' => 'date',
        'extra' => 'array',
        'remark' => 'max:500',
        'sort' => 'integer|egt:0',
    ];

    /** @var array<string, string> */
    protected $message = [
        'title.require' => '标题不能为空',
        'code.require' => '编码不能为空',
        'code.alphaDash' => '编码仅允许字母、数字、下划线和中划线',
        'status.in' => '状态无效',
        'level.in' => '等级无效',
    ];

    /** sceneCreate 新建场景 */
    public function sceneCreate(): static
    {
        return $this
            ->only([
                'title', 'code', 'category_id', 'tag_ids', 'content', 'amount', 'quantity', 'is_top', 'status', 'level', 'color', 'cover', 'publish_time', 'expire_date', 'extra', 'remark', 'sort',
            ])
            ->append('code', 'unique:demo_all_type');
    }

    /** sceneUpdate 更新场景 */
    public function sceneUpdate(): static
    {
        return $this->only([
            'title', 'code', 'category_id', 'tag_ids', 'content', 'amount', 'quantity', 'is_top', 'status', 'level', 'color', 'cover', 'publish_time', 'expire_date', 'extra', 'remark', 'sort',
        ]);
    }
}
