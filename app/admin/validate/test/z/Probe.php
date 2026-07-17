<?php
declare (strict_types = 1);

namespace app\admin\validate\test\z;

use think\Validate;

/**
 * Probe SchemaProbe校验（CRUD 生成：sceneCreate / sceneUpdate）
 */
class Probe extends Validate
{
    /** @var array<string, string> */
    protected $rule = [
        'id' => 'integer',
        'title' => 'max:255',
        'new_col' => 'max:80',
    ];

    /** @var array<string, string> */
    protected $message = [

    ];

    /** sceneCreate 新建场景 */
    public function sceneCreate(): static
    {
        return $this->only([
                'id', 'title', 'new_col',
            ]);
    }

    /** sceneUpdate 更新场景 */
    public function sceneUpdate(): static
    {
        return $this->only([
            'id', 'title', 'new_col',
        ]);
    }
}
