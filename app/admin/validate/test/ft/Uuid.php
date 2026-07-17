<?php
declare (strict_types = 1);

namespace app\admin\validate\test\ft;

use think\Validate;

/**
 * Uuid UUID校验（CRUD 生成：sceneCreate / sceneUpdate）
 */
class Uuid extends Validate
{
    /** @var array<string, string> */
    protected $rule = [
        'id' => 'integer',
        'uuid' => 'max:36|alphaDash',
    ];

    /** @var array<string, string> */
    protected $message = [
        'uuid.alphaDash' => 'UUID仅允许字母、数字、下划线和中划线',
    ];

    /** sceneCreate 新建场景 */
    public function sceneCreate(): static
    {
        return $this
            ->only([
                'id', 'uuid',
            ])
            ->append('uuid', 'unique:test_ft_uuid');
    }

    /** sceneUpdate 更新场景 */
    public function sceneUpdate(): static
    {
        return $this->only([
            'id', 'uuid',
        ]);
    }
}
