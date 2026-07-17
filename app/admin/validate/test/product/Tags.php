<?php
declare (strict_types = 1);

namespace app\admin\validate\test\product;

use think\Validate;

/**
 * Tags Tags校验（CRUD 生成：sceneCreate / sceneUpdate）
 */
class Tags extends Validate
{
    /** @var array<string, string> */
    protected $rule = [
        'id' => 'integer',
        'name' => 'max:100',
    ];

    /** @var array<string, string> */
    protected $message = [

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
