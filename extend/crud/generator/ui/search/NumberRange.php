<?php
declare (strict_types = 1);

namespace crud\generator\ui\search;

use crud\Context;
use crud\Template;
use crud\generator\ui\UiRenderer;

/**
 * NumberRange 搜索 UI：number_range
 *
 * tpl: index/search/number_range.vue.tpl
 * params: label, prop
 * alias: 无
 */
class NumberRange implements UiRenderer
{
    public string $label = '';
    public string $prop = '';

    public static function key(): string
    {
        return 'number_range';
    }

    public static function fromField(array $field): static
    {
        $p = new static();
        $p->label = (string) $field['label'];
        $p->prop = (string) $field['prop'];

        return $p;
    }

    public static function validate(array $field): void
    {
        self::fromField($field);
    }

    public function render(array $field, Context $ctx): string
    {
        $p = self::fromField($field);

        return Template::render('index/search/number_range.vue.tpl', [
            'label' => $p->label,
            'prop' => $p->prop,
        ]) . "\n";
    }
}
