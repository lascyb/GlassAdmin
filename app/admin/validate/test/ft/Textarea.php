<?php
declare (strict_types = 1);

namespace app\admin\validate\test\ft;

use think\Validate;

/**
 * Textarea 多行文本校验（CRUD 生成：sceneCreate / sceneUpdate）
 */
class Textarea extends Validate
{
    /** @var array<string, string> */
    protected $rule = [
        'id' => 'integer',
        'content' => 'max:2000',
    ];

    /** @var array<string, string> */
    protected $message = [

    ];

    /** sceneCreate 新建场景 */
    public function sceneCreate(): static
    {
        return $this->only([
                'id', 'content',
            ]);
    }

    /** sceneUpdate 更新场景 */
    public function sceneUpdate(): static
    {
        return $this->only([
            'id', 'content',
        ]);
    }
}
