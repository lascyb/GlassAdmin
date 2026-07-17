<?php
declare (strict_types = 1);

namespace app\admin\validate\test\ft;

use think\Validate;

/**
 * Radio 单选校验（CRUD 生成：sceneCreate / sceneUpdate）
 */
class Radio extends Validate
{
    /** @var array<string, string> */
    protected $rule = [
        'id' => 'integer',
        'status' => 'in:1,0',
    ];

    /** @var array<string, string> */
    protected $message = [
        'status.in' => '状态无效',
    ];

    /** sceneCreate 新建场景 */
    public function sceneCreate(): static
    {
        return $this->only([
                'id', 'status',
            ]);
    }

    /** sceneUpdate 更新场景 */
    public function sceneUpdate(): static
    {
        return $this->only([
            'id', 'status',
        ]);
    }
}
