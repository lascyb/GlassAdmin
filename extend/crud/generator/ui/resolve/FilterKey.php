<?php
declare (strict_types = 1);

namespace crud\generator\ui\resolve;

use crud\field\FieldMeta;
use crud\Context;

/**
 * FilterKey 解析后端筛选规范 UI 键
 */
class FilterKey
{
    /** resolve 解析规范键；空串表示跳过 */
    public static function resolve(array $field): string
    {
        $relation = FieldMeta::relationType($field);
        $sr = FieldMeta::searchRender($field);
        if ($relation === 'belongs_to_many' || $sr === 'remote_multi_select') {
            return 'remote_multi_select';
        }
        if ($sr === 'number_range') {
            return 'number_range';
        }
        if ($sr === 'date_range' || $sr === 'datetime_range') {
            return FieldMeta::isUnixDatetime($field) ? 'datetime_range' : 'date_range';
        }
        if ($relation === 'belongs_to' || $sr === 'remote_select') {
            return 'remote_select';
        }
        if ($sr === 'input') {
            return 'input';
        }
        if ($sr === 'bool' || $sr === 'select' || $sr === 'radio' || FieldMeta::isBoolField($field) || FieldMeta::isEnumField($field)) {
            return 'enum';
        }

        return '';
    }
}
