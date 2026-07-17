<?php
declare (strict_types = 1);

namespace app\admin\validate\test\ft;

use think\Validate;

/**
 * Checkbox 多选校验（CRUD 生成：sceneCreate / sceneUpdate）
 */
class Checkbox extends Validate
{
    /** @var array<string, string> */
    protected $rule = [
        'id' => 'integer',
        'flags' => 'array',
    ];

    /** @var array<string, string> */
    protected $message = [

    ];

    /** sceneCreate 新建场景 */
    public function sceneCreate(): static
    {
        return $this->only([
                'id', 'flags',
            ]);
    }

    /** sceneUpdate 更新场景 */
    public function sceneUpdate(): static
    {
        return $this->only([
            'id', 'flags',
        ]);
    }
}
