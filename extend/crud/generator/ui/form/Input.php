<?php
declare (strict_types = 1);

namespace crud\generator\ui\form;

use crud\field\FieldMeta;
use crud\Context;
use crud\Template;
use crud\generator\ui\UiRenderer;

/**
 * Input 表单 UI：input
 *
 * tpl: edit/field/input.vue.tpl
 * params: form.required, form.maxLength / column.length, label, prop, placeholder
 * alias: (FormKey) password → input
 */
class Input implements UiRenderer
{
    public string $label = '';
    public string $prop = '';
    public bool $required = false;
    public string $placeholder = '';
    public ?int $maxLength = null;

    public static function key(): string
    {
        return 'input';
    }

    public static function fromField(array $field): static
    {
        $p = new static();
        $form = FieldMeta::formConfig($field) ?? [];
        $p->label = (string) $field['label'];
        $p->prop = (string) $field['prop'];
        $p->required = !empty($form['required']);
        $p->placeholder = FieldMeta::formPlaceholder($field);
        $p->maxLength = FieldMeta::formMaxLength($field);

        return $p;
    }

    public static function validate(array $field): void
    {
        self::fromField($field);
    }

    public function render(array $field, Context $ctx): string
    {
        $p = self::fromField($field);
        $max = $p->maxLength !== null ? " maxlength=\"{$p->maxLength}\" show-word-limit" : '';

        return Template::render('edit/field/input.vue.tpl', [
            'label' => $p->label,
            'prop' => $p->prop,
            'required' => $p->required ? ' required' : '',
            'placeholder' => $p->placeholder,
            'max' => $max,
        ]) . "\n";
    }
}
