<?php
declare (strict_types = 1);

namespace crud\generator\ui\form;

use crud\field\FieldMeta;
use crud\Context;
use crud\Template;
use crud\generator\ui\UiRenderer;

/**
 * MultiSelect 表单 UI：multi_select
 *
 * tpl: edit/field/multi_select.vue.tpl
 * params: form.required, ext.enum.key→optKey, label, prop, placeholder
 * alias: (FormKey) checkbox → multi_select
 */
class MultiSelect implements UiRenderer
{
    public string $label = '';
    public string $prop = '';
    public bool $required = false;
    public string $placeholder = '';
    public string $optKey = '';

    public static function key(): string
    {
        return 'multi_select';
    }

    public static function fromField(array $field): static
    {
        $p = new static();
        $form = FieldMeta::formConfig($field) ?? [];
        $p->label = (string) $field['label'];
        $p->prop = (string) $field['prop'];
        $p->required = !empty($form['required']);
        $p->placeholder = FieldMeta::formPlaceholder($field);
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

        return Template::render('edit/field/multi_select.vue.tpl', [
            'label' => $p->label,
            'prop' => $p->prop,
            'required' => $p->required ? ' required' : '',
            'placeholder' => $p->placeholder,
            'optKey' => $p->optKey,
        ]) . "\n";
    }
}
