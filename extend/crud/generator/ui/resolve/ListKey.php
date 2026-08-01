<?php
declare (strict_types = 1);

namespace crud\generator\ui\resolve;

use crud\field\FieldMeta;
use crud\Context;

/**
 * ListKey 解析列表规范 UI 键（含旧键 alias）
 */
class ListKey
{
    /** resolve 解析规范键 */
    public static function resolve(array $field): string
    {
        $relation = FieldMeta::relationType($field);
        if ($relation === 'belongs_to') {
            return 'belongs_to';
        }
        if ($relation === 'belongs_to_many') {
            return 'belongs_to_many';
        }
        $lr = FieldMeta::listRender($field);
        if ($lr === 'status' || $lr === 'tag' || $lr === 'enum_tag') {
            return FieldMeta::enumKey($field) !== '' ? 'enum_tag' : 'text';
        }
        if ($lr === 'datetime') {
            return FieldMeta::isUnixDatetime($field) ? 'datetime' : 'text';
        }
        if ($lr === 'prop' || $lr === '' || $lr === 'text') {
            return 'text';
        }
        if (in_array($lr, ['bool', 'color', 'image', 'tags', 'number'], true)) {
            return $lr;
        }

        return 'text';
    }
}
