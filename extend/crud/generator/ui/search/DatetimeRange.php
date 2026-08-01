<?php
declare (strict_types = 1);

namespace crud\generator\ui\search;

use crud\field\FieldMeta;
use crud\Context;
use crud\Template;
use crud\generator\ui\UiRenderer;

/**
 * DatetimeRange 搜索 UI：datetime_range
 *
 * tpl: index/search/datetime_range.vue.tpl
 * params: label, prop, isUnixDatetime→valueFormat
 * alias: 无
 */
class DatetimeRange implements UiRenderer
{
    public string $label = '';
    public string $prop = '';
    public string $valueFormat = '';

    public static function key(): string
    {
        return 'datetime_range';
    }

    public static function fromField(array $field): static
    {
        $p = new static();
        $p->label = (string) $field['label'];
        $p->prop = (string) $field['prop'];
        $p->valueFormat = FieldMeta::isUnixDatetime($field) ? 'X' : 'YYYY-MM-DD HH:mm:ss';

        return $p;
    }

    public static function validate(array $field): void
    {
        self::fromField($field);
    }

    public function render(array $field, Context $ctx): string
    {
        $p = self::fromField($field);

        return Template::render('index/search/datetime_range.vue.tpl', [
            'label' => $p->label,
            'prop' => $p->prop,
            'valueFormat' => $p->valueFormat,
        ]) . "\n";
    }
}
