<?php
declare (strict_types = 1);

namespace app\admin\validate\test\ft;

use think\Validate;

/**
 * Number 数字校验（CRUD 生成：sceneCreate / sceneUpdate）
 */
class Number extends Validate
{
    /** @var array<string, string> */
    protected $rule = [
        'id' => 'integer',
        'quantity' => 'integer|egt:0',
    ];

    /** @var array<string, string> */
    protected $message = [

    ];

    /** sceneCreate 新建场景 */
    public function sceneCreate(): static
    {
        return $this->only([
                'id', 'quantity',
            ]);
    }

    /** sceneUpdate 更新场景 */
    public function sceneUpdate(): static
    {
        return $this->only([
            'id', 'quantity',
        ]);
    }
}
