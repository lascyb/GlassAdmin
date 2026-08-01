<?php
declare (strict_types = 1);

namespace crud\generator\ui;

use crud\Context;

/**
 * FilterUiRenderer controller list 筛选片段契约
 */
interface FilterUiRenderer
{
    /** key 规范 UI 键 */
    public static function key(): string;

    /** fromField 从 field 灌入本控件属性，返回新实例 */
    public static function fromField(array $field): static;

    /** validate 基于 fromField 结果校验；失败抛 InvalidArgumentException */
    public static function validate(array $field): void;

    /**
     * render 渲染取参行与 where 片段
     *
     * @return array{params: list<string>, filter: string}
     */
    public function render(array $field, Context $ctx): array;
}
