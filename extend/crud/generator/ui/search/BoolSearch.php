<?php
declare (strict_types = 1);

namespace crud\generator\ui\search;

use crud\Context;
use crud\Template;
use crud\generator\ui\UiRenderer;

/**
 * BoolSearch 搜索 UI：bool
 *
 * tpl: index/search/bool.vue.tpl
 * params: label, prop
 * alias: 无
 */
class BoolSearch implements UiRenderer
{
    public string $label = '';
    public string $prop = '';

    public static function key(): string
    {
        return 'bool';
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

        return Template::render('index/search/bool.vue.tpl', [
            'label' => $p->label,
            'prop' => $p->prop,
        ]) . "\n";
    }
}
