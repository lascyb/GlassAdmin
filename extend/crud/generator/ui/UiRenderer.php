<?php
declare (strict_types = 1);

namespace crud\generator\ui;

use crud\Context;

/**
 * UiRenderer form/list/search 渲染片段契约
 */
interface UiRenderer
{
    /** key 规范 UI 键 */
    public static function key(): string;

    /** fromField 从 field 灌入本控件属性，返回新实例 */
    public static function fromField(array $field): static;

    /** validate 基于 fromField 结果校验；失败抛 InvalidArgumentException */
    public static function validate(array $field): void;

    /** render 渲染模板片段 */
    public function render(array $field, Context $ctx): string;
}
