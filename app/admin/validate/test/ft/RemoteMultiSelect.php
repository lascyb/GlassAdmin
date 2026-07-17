<?php
declare (strict_types = 1);

namespace app\admin\validate\test\ft;

use think\Validate;

/**
 * RemoteMultiSelect 远程多选校验（CRUD 生成：sceneCreate / sceneUpdate）
 */
class RemoteMultiSelect extends Validate
{
    /** @var array<string, string> */
    protected $rule = [
        'id' => 'integer',
        'tag_ids' => 'array',
    ];

    /** @var array<string, string> */
    protected $message = [

    ];

    /** sceneCreate 新建场景 */
    public function sceneCreate(): static
    {
        return $this->only([
                'id', 'tag_ids',
            ]);
    }

    /** sceneUpdate 更新场景 */
    public function sceneUpdate(): static
    {
        return $this->only([
            'id', 'tag_ids',
        ]);
    }
}
