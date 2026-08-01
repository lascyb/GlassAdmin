<?php
declare (strict_types = 1);

namespace crud\generator\ui\form;

use crud\field\FieldMeta;
use crud\Context;
use crud\Template;
use crud\generator\ui\UiRenderer;

/**
 * Datetime 表单 UI：datetime
 *
 * tpl: edit/field/datetime.vue.tpl
 * params: form.required, isUnixDatetime→valueFormat, label, prop, placeholder
 * alias: (FormKey) time → datetime
 */
class Datetime implements UiRenderer
{
    public string $label = '';
    public string $prop = '';
    public bool $required = false;
    public string $placeholder = '';
    public string $valueFormat = '';

    public static function key(): string
    {
        return 'datetime';
    }

    public static function fromField(array $field): static
    {
        $p = new static();
        $form = FieldMeta::formConfig($field) ?? [];
        $p->label = (string) $field['label'];
        $p->prop = (string) $field['prop'];
        $p->required = !empty($form['required']);
        $p->placeholder = FieldMeta::formPlaceholder($field);
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

        return Template::render('edit/field/datetime.vue.tpl', [
            'label' => $p->label,
            'prop' => $p->prop,
            'required' => $p->required ? ' required' : '',
            'placeholder' => $p->placeholder,
            'valueFormat' => $p->valueFormat,
        ]) . "\n";
    }
}
