<?php
declare (strict_types = 1);

namespace crud\generator\ui\form;

use crud\field\FieldMeta;
use crud\Context;
use crud\Template;
use crud\generator\ui\UiRenderer;

/**
 * Number 表单 UI：number
 *
 * tpl: edit/field/number.vue.tpl
 * params: form.required, form.numberMin/Max/Step, column.scale(decimal), label, prop, placeholder
 * alias: 无
 */
class Number implements UiRenderer
{
    public string $label = '';
    public string $prop = '';
    public bool $required = false;
    public string $placeholder = '';
    public int|float|null $numberMin = null;
    public int|float|null $numberMax = null;
    public int|float|null $numberStep = null;
    public ?int $precision = null;

    public static function key(): string
    {
        return 'number';
    }

    public static function fromField(array $field): static
    {
        $p = new static();
        $form = FieldMeta::formConfig($field) ?? [];
        $p->label = (string) $field['label'];
        $p->prop = (string) $field['prop'];
        $p->required = !empty($form['required']);
        $p->placeholder = FieldMeta::formPlaceholder($field);
        $p->numberMin = self::toNumberOrNull($form['numberMin'] ?? null);
        $p->numberMax = self::toNumberOrNull($form['numberMax'] ?? null);
        $p->numberStep = self::toNumberOrNull($form['numberStep'] ?? null);
        $p->precision = FieldMeta::columnType($field) === 'decimal' ? FieldMeta::decimalScale($field) : null;

        return $p;
    }

    /** toNumberOrNull 将配置值规范为数字或 null */
    private static function toNumberOrNull(mixed $v): int|float|null
    {
        if ($v === null || $v === '') {
            return null;
        }
        if (is_int($v) || is_float($v)) {
            return $v;
        }
        if (is_numeric($v)) {
            return str_contains((string) $v, '.') ? (float) $v : (int) $v;
        }

        return null;
    }

    public static function validate(array $field): void
    {
        $form = FieldMeta::formConfig($field) ?? [];
        $prop = (string) ($field['prop'] ?? '');
        foreach (['numberMin', 'numberMax', 'numberStep'] as $name) {
            if (!array_key_exists($name, $form) || $form[$name] === null || $form[$name] === '') {
                continue;
            }
            if (!is_int($form[$name]) && !is_float($form[$name]) && !is_numeric($form[$name])) {
                throw new \InvalidArgumentException("字段 {$prop}（form:number）：{$name} 须为数字");
            }
        }
        $p = self::fromField($field);
        if ($p->numberMin !== null && $p->numberMax !== null && (float) $p->numberMin > (float) $p->numberMax) {
            throw new \InvalidArgumentException("字段 {$p->prop}（form:number）：numberMin 不能大于 numberMax");
        }
    }

    public function render(array $field, Context $ctx): string
    {
        $p = self::fromField($field);
        $attrs = '';
        if ($p->numberMin !== null) {
            $attrs .= ' :min="' . $p->numberMin . '"';
        }
        if ($p->numberMax !== null) {
            $attrs .= ' :max="' . $p->numberMax . '"';
        }
        if ($p->numberStep !== null) {
            $attrs .= ' :step="' . $p->numberStep . '"';
        }
        if ($p->precision !== null) {
            $attrs .= ' :precision="' . $p->precision . '"';
        }

        return Template::render('edit/field/number.vue.tpl', [
            'label' => $p->label,
            'prop' => $p->prop,
            'required' => $p->required ? ' required' : '',
            'attrs' => $attrs,
            'placeholder' => $p->placeholder,
        ]) . "\n";
    }
}
