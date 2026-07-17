<?php
declare (strict_types = 1);

namespace app\admin\validate\test\product;

use think\Validate;

/**
 * Attrs Attrs校验（CRUD 生成：sceneCreate / sceneUpdate）
 */
class Attrs extends Validate
{
    /** @var array<string, string> */
    protected $rule = [
        'key' => 'require|max:100|alphaDash',
        'value' => 'require|max:100',
    ];

    /** @var array<string, string> */
    protected $message = [
        'key.require' => 'Key不能为空',
        'key.alphaDash' => 'Key仅允许字母、数字、下划线和中划线',
        'value.require' => 'Value不能为空',
    ];

    /** sceneCreate 新建场景 */
    public function sceneCreate(): static
    {
        return $this
            ->only([
                'key', 'value',
            ])
            ->append('key', 'unique:test_product_attrs');
    }

    /** sceneUpdate 更新场景 */
    public function sceneUpdate(): static
    {
        return $this->only([
            'key', 'value',
        ]);
    }
}
