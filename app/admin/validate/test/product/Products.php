<?php
declare (strict_types = 1);

namespace app\admin\validate\test\product;

use think\Validate;

/**
 * Products Products校验（CRUD 生成：sceneCreate / sceneUpdate）
 */
class Products extends Validate
{
    /** @var array<string, string> */
    protected $rule = [
        'id' => 'integer',
        'name' => 'max:255',
        'category_id' => 'require|integer|gt:0',
    ];

    /** @var array<string, string> */
    protected $message = [
        'category_id.require' => '类型不能为空',
    ];

    /** sceneCreate 新建场景 */
    public function sceneCreate(): static
    {
        return $this->only([
                'id', 'name', 'category_id',
            ]);
    }

    /** sceneUpdate 更新场景 */
    public function sceneUpdate(): static
    {
        return $this->only([
            'id', 'name', 'category_id',
        ]);
    }
}
