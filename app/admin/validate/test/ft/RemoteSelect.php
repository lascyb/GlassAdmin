<?php
declare (strict_types = 1);

namespace app\admin\validate\test\ft;

use think\Validate;

/**
 * RemoteSelect 远程下拉校验（CRUD 生成：sceneCreate / sceneUpdate）
 */
class RemoteSelect extends Validate
{
    /** @var array<string, string> */
    protected $rule = [
        'id' => 'integer',
        'category_id' => 'integer|gt:0',
    ];

    /** @var array<string, string> */
    protected $message = [

    ];

    /** sceneCreate 新建场景 */
    public function sceneCreate(): static
    {
        return $this->only([
                'id', 'category_id',
            ]);
    }

    /** sceneUpdate 更新场景 */
    public function sceneUpdate(): static
    {
        return $this->only([
            'id', 'category_id',
        ]);
    }
}
