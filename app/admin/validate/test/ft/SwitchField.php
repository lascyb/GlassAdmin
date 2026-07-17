<?php
declare (strict_types = 1);

namespace app\admin\validate\test\ft;

use think\Validate;

/**
 * SwitchField 开关校验（CRUD 生成：sceneCreate / sceneUpdate）
 */
class SwitchField extends Validate
{
    /** @var array<string, string> */
    protected $rule = [
        'id' => 'integer',
        'is_top' => 'in:0,1',
    ];

    /** @var array<string, string> */
    protected $message = [

    ];

    /** sceneCreate 新建场景 */
    public function sceneCreate(): static
    {
        return $this->only([
                'id', 'is_top',
            ]);
    }

    /** sceneUpdate 更新场景 */
    public function sceneUpdate(): static
    {
        return $this->only([
            'id', 'is_top',
        ]);
    }
}
