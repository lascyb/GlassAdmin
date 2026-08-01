<?php
declare (strict_types = 1);

namespace crud\generator\ui\resolve;

use crud\field\FieldMeta;
use crud\Context;

/**
 * SearchKey 解析搜索规范 UI 键（含旧键 alias）
 */
class SearchKey
{
    /** resolve 解析规范键；空串表示不渲染 */
    public static function resolve(array $field): string
    {
        $relation = FieldMeta::relationType($field);
        $sr = FieldMeta::searchRender($field);
        if ($relation === 'belongs_to_many' || $sr === 'remote_multi_select') {
            return 'remote_multi_select';
        }
        if ($relation === 'belongs_to' || $sr === 'remote_select') {
            return 'remote_select';
        }
        if (in_array($sr, ['number_range', 'date_range', 'datetime_range', 'input', 'bool'], true)) {
            return $sr;
        }
        if (($sr === 'select' || $sr === 'radio') && FieldMeta::enumKey($field) !== '') {
            return 'select';
        }

        return '';
    }
}
