<?php
declare (strict_types = 1);

namespace app\admin\validate\test\a\b;

use think\Validate;

/**
 * E E校验（CRUD 生成：sceneCreate / sceneUpdate）
 */
class E extends Validate
{
    /** @var array<string, string> */
    protected $rule = [
        'id' => 'integer|egt:0',
        'name' => 'max:255',
        'age' => 'integer|egt:0',
    ];

    /** @var array<string, string> */
    protected $message = [

    ];

    /** sceneCreate 新建场景 */
    public function sceneCreate(): static
    {
        return $this->only([
                'id', 'name', 'age',
            ]);
    }

    /** sceneUpdate 更新场景 */
    public function sceneUpdate(): static
    {
        return $this->only([
            'id', 'name', 'age',
        ]);
    }
}
