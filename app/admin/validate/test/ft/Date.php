<?php
declare (strict_types = 1);

namespace app\admin\validate\test\ft;

use think\Validate;

/**
 * Date 日期校验（CRUD 生成：sceneCreate / sceneUpdate）
 */
class Date extends Validate
{
    /** @var array<string, string> */
    protected $rule = [
        'id' => 'integer',
        'biz_date' => 'date',
    ];

    /** @var array<string, string> */
    protected $message = [

    ];

    /** sceneCreate 新建场景 */
    public function sceneCreate(): static
    {
        return $this->only([
                'id', 'biz_date',
            ]);
    }

    /** sceneUpdate 更新场景 */
    public function sceneUpdate(): static
    {
        return $this->only([
            'id', 'biz_date',
        ]);
    }
}
