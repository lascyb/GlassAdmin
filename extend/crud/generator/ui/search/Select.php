<?php
declare (strict_types = 1);

namespace crud\generator\ui\search;

use crud\field\FieldMeta;
use crud\Context;
use crud\Template;
use crud\generator\ui\UiRenderer;

/**
 * Select 搜索 UI：select
 *
 * tpl: index/search/select.vue.tpl
 * params: label, prop, ext.enum.key→optKey
 * alias: (SearchKey) radio → select
 */
class Select implements UiRenderer
{
    public string $label = '';
    public string $prop = '';
    public string $optKey = '';

    public static function key(): string
    {
        return 'select';
    }

    public static function fromField(array $field): static
    {
        $p = new static();
        $p->label = (string) $field['label'];
        $p->prop = (string) $field['prop'];
        $p->optKey = FieldMeta::enumKey($field);

        return $p;
    }

    public static function validate(array $field): void
    {
        self::fromField($field);
    }

    public function render(array $field, Context $ctx): string
    {
        $p = self::fromField($field);

        return Template::render('index/search/select.vue.tpl', [
            'label' => $p->label,
            'prop' => $p->prop,
            'optKey' => $p->optKey,
        ]) . "\n";
    }
}
