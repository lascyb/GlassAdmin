<?php
declare (strict_types = 1);

namespace app\admin\validate\test\ft;

use think\Validate;

/**
 * Select 下拉单选校验（CRUD 生成：sceneCreate / sceneUpdate）
 */
class Select extends Validate
{
    /** @var array<string, string> */
    protected $rule = [
        'id' => 'integer',
        'level' => 'in:1,2,3',
    ];

    /** @var array<string, string> */
    protected $message = [
        'level.in' => '等级无效',
    ];

    /** sceneCreate 新建场景 */
    public function sceneCreate(): static
    {
        return $this->only([
                'id', 'level',
            ]);
    }

    /** sceneUpdate 更新场景 */
    public function sceneUpdate(): static
    {
        return $this->only([
            'id', 'level',
        ]);
    }
}
