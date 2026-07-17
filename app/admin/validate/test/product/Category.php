<?php
declare (strict_types = 1);

namespace app\admin\validate\test\product;

use think\Validate;

/**
 * Category Category校验（CRUD 生成：sceneCreate / sceneUpdate）
 */
class Category extends Validate
{
    /** @var array<string, string> */
    protected $rule = [
        'id' => 'integer',
        'name' => 'require|max:100',
    ];

    /** @var array<string, string> */
    protected $message = [
        'name.require' => '名称不能为空',
    ];

    /** sceneCreate 新建场景 */
    public function sceneCreate(): static
    {
        return $this->only([
                'id', 'name',
            ]);
    }

    /** sceneUpdate 更新场景 */
    public function sceneUpdate(): static
    {
        return $this->only([
            'id', 'name',
        ]);
    }
}
