<?php
declare (strict_types = 1);

namespace crud\generator\ui\resolve;

use crud\field\FieldMeta;
use crud\Context;

/**
 * FormKey 解析表单规范 UI 键（含旧键 alias）
 */
class FormKey
{
    /** resolve 解析规范键 */
    public static function resolve(array $field): string
    {
        $relation = FieldMeta::relationType($field);
        $render = FieldMeta::formRender($field);
        if ($relation === 'belongs_to' || $render === 'remote_select') {
            return 'remote_select';
        }
        if ($relation === 'belongs_to_many' || $render === 'remote_multi_select') {
            return 'remote_multi_select';
        }
        if (in_array($render, ['multi_select', 'checkbox'], true)) {
            return 'multi_select';
        }
        if (in_array($render, ['image', 'file', 'image_upload'], true)) {
            return 'image_upload';
        }
        if (in_array($render, ['datetime', 'time'], true)) {
            return 'datetime';
        }
        if ($render === 'password' || $render === '') {
            return 'input';
        }

        return $render;
    }
}
