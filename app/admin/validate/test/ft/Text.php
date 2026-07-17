<?php
declare (strict_types = 1);

namespace app\admin\validate\test\ft;

use think\Validate;

/**
 * Text 单行文本校验（CRUD 生成：sceneCreate / sceneUpdate）
 */
class Text extends Validate
{
    /** @var array<string, string> */
    protected $rule = [
        'id' => 'integer',
        'title' => 'max:255',
    ];

    /** @var array<string, string> */
    protected $message = [

    ];

    /** sceneCreate 新建场景 */
    public function sceneCreate(): static
    {
        return $this->only([
                'id', 'title',
            ]);
    }

    /** sceneUpdate 更新场景 */
    public function sceneUpdate(): static
    {
        return $this->only([
            'id', 'title',
        ]);
    }
}
