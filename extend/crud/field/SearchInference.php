<?php
declare (strict_types = 1);

namespace crud\field;

/**
 * SearchInference 表单控件 → 默认搜索控件映射
 *
 * 新增 form 渲染且需默认高级搜索时，只改 formToSearchRender。
 */
class SearchInference
{
    public static function formToSearchRender(string $formRender): string
    {
        return match ($formRender) {
            'input', 'password' => 'input',
            'number' => 'number_range',
            'switch' => 'bool',
            'radio', 'select' => 'select',
            'remote_select' => 'remote_select',
            'remote_multi_select' => 'remote_multi_select',
            'date' => 'date_range',
            'datetime', 'time' => 'datetime_range',
            'image', 'file', 'image_upload' => '',
            default => '',
        };
    }
}
