<?php
declare (strict_types = 1);

namespace app\admin\validate\test\ft;

use think\Validate;

/**
 * Image 图片校验（CRUD 生成：sceneCreate / sceneUpdate）
 */
class Image extends Validate
{
    /** @var array<string, string> */
    protected $rule = [
        'id' => 'integer',
        'cover' => 'max:255',
    ];

    /** @var array<string, string> */
    protected $message = [

    ];

    /** sceneCreate 新建场景 */
    public function sceneCreate(): static
    {
        return $this->only([
                'id', 'cover',
            ]);
    }

    /** sceneUpdate 更新场景 */
    public function sceneUpdate(): static
    {
        return $this->only([
            'id', 'cover',
        ]);
    }
}
