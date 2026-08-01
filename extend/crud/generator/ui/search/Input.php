<?php
declare (strict_types = 1);

namespace crud\generator\ui\search;

use crud\field\FieldMeta;
use crud\Context;
use crud\Template;
use crud\generator\ui\UiRenderer;

/**
 * Input 搜索 UI：input
 *
 * tpl: index/search/input.vue.tpl
 * params: label, prop, searchPlaceholder
 * alias: 无
 */
class Input implements UiRenderer
{
    public string $label = '';
    public string $prop = '';
    public string $placeholder = '';

    public static function key(): string
    {
        return 'input';
    }

    public static function fromField(array $field): static
    {
        $p = new static();
        $p->label = (string) $field['label'];
        $p->prop = (string) $field['prop'];
        $p->placeholder = FieldMeta::searchPlaceholder($field, 'input');

        return $p;
    }

    public static function validate(array $field): void
    {
        self::fromField($field);
    }

    public function render(array $field, Context $ctx): string
    {
        $p = self::fromField($field);

        return Template::render('index/search/input.vue.tpl', [
            'label' => $p->label,
            'prop' => $p->prop,
            'placeholder' => $p->placeholder,
        ]) . "\n";
    }
}
