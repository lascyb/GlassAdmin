<?php
declare (strict_types = 1);

namespace app\admin\validate\test\ft;

use think\Validate;

/**
 * Color 颜色校验（CRUD 生成：sceneCreate / sceneUpdate）
 */
class Color extends Validate
{
    /** @var array<string, string> */
    protected $rule = [
        'id' => 'integer',
        'color' => 'max:255',
    ];

    /** @var array<string, string> */
    protected $message = [

    ];

    /** sceneCreate 新建场景 */
    public function sceneCreate(): static
    {
        return $this->only([
                'id', 'color',
            ]);
    }

    /** sceneUpdate 更新场景 */
    public function sceneUpdate(): static
    {
        return $this->only([
            'id', 'color',
        ]);
    }
}
