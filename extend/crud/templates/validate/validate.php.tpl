<?php
declare (strict_types = 1);

namespace <%nsValidate%>;

use think\Validate;

/**
 * <%entity%> <%title%>校验（CRUD 生成：sceneCreate / sceneUpdate）
 */
class <%entity%> extends Validate
{
    /** @var array<string, string> */
    protected $rule = [
<%ruleBlock%>
    ];

    /** @var array<string, string> */
    protected $message = [
<%msgBlock%>
    ];

    /** sceneCreate 新建场景 */
    public function sceneCreate(): static
    {
        <%sceneCreateBody%>
    }

    /** sceneUpdate 更新场景 */
    public function sceneUpdate(): static
    {
        return $this->only([
            <%onlyList%>,
        ]);
    }
}
