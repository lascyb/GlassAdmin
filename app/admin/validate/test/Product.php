<?php
declare (strict_types = 1);

namespace app\admin\validate\test;

use think\Validate;

/**
 * Product Product校验（CRUD 生成：sceneCreate / sceneUpdate）
 */
class Product extends Validate
{
    /** @var array<string, string> */
    protected $rule = [
        'id' => 'integer',
    ];

    /** @var array<string, string> */
    protected $message = [

    ];

    /** sceneCreate 新建场景 */
    public function sceneCreate(): static
    {
        return $this->only([
                'id',
            ]);
    }

    /** sceneUpdate 更新场景 */
    public function sceneUpdate(): static
    {
        return $this->only([
            'id',
        ]);
    }
}
