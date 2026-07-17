<?php
declare (strict_types = 1);

namespace app\admin\validate\test\ft;

use think\Validate;

/**
 * MultiSelect 下拉多选校验（CRUD 生成：sceneCreate / sceneUpdate）
 */
class MultiSelect extends Validate
{
    /** @var array<string, string> */
    protected $rule = [
        'id' => 'integer',
        'extra' => 'array',
    ];

    /** @var array<string, string> */
    protected $message = [

    ];

    /** sceneCreate 新建场景 */
    public function sceneCreate(): static
    {
        return $this->only([
                'id', 'extra',
            ]);
    }

    /** sceneUpdate 更新场景 */
    public function sceneUpdate(): static
    {
        return $this->only([
            'id', 'extra',
        ]);
    }
}
