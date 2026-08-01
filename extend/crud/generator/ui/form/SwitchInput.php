<?php
declare (strict_types = 1);

namespace crud\generator\ui\form;

use crud\field\FieldMeta;
use crud\Context;
use crud\Template;
use crud\generator\ui\UiRenderer;

/**
 * SwitchInput 表单 UI：switch
 *
 * tpl: edit/field/switch.vue.tpl
 * params: form.required, label, prop, placeholder
 * alias: 无
 */
class SwitchInput implements UiRenderer
{
    public string $label = '';
    public string $prop = '';
    public bool $required = false;
    public string $placeholder = '';

    public static function key(): string
    {
        return 'switch';
    }

    public static function fromField(array $field): static
    {
        $p = new static();
        $form = FieldMeta::formConfig($field) ?? [];
        $p->label = (string) $field['label'];
        $p->prop = (string) $field['prop'];
        $p->required = !empty($form['required']);
        $p->placeholder = FieldMeta::formPlaceholder($field);

        return $p;
    }

    public static function validate(array $field): void
    {
        self::fromField($field);
    }

    public function render(array $field, Context $ctx): string
    {
        $p = self::fromField($field);

        return Template::render('edit/field/switch.vue.tpl', [
            'label' => $p->label,
            'prop' => $p->prop,
            'required' => $p->required ? ' required' : '',
            'placeholder' => $p->placeholder,
        ]) . "\n";
    }
}
