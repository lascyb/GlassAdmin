<?php
declare (strict_types = 1);

namespace crud\generator\ui\list;

use crud\field\FieldMeta;
use crud\Context;
use crud\Template;
use crud\generator\ui\UiRenderer;

/**
 * Text 列表 UI：text
 *
 * tpl: index/col/text.vue.tpl
 * params: list.width/minWidth, list.render, label, prop, overflow
 * alias: (ListKey) prop/未知 → text
 */
class Text implements UiRenderer
{
    public string $label = '';
    public string $prop = '';
    public string $width = '';
    public string $minWidth = '';
    public string $overflow = '';

    public static function key(): string
    {
        return 'text';
    }

    public static function fromField(array $field): static
    {
        $p = new static();
        $list = FieldMeta::listConfig($field) ?? [];
        $p->label = (string) $field['label'];
        $p->prop = (string) $field['prop'];
        $p->width = isset($list['width']) ? " width=\"{$list['width']}\"" : '';
        $p->minWidth = isset($list['minWidth']) ? " min-width=\"{$list['minWidth']}\"" : '';
        $lr = FieldMeta::listRender($field);
        $p->overflow = ((isset($list['minWidth']) || $lr === 'text' || $lr === 'prop') && $p->prop !== 'id')
            ? ' show-overflow-tooltip'
            : '';

        return $p;
    }

    public static function validate(array $field): void
    {
        $p = self::fromField($field);
        $list = FieldMeta::listConfig($field) ?? [];
        foreach (['width', 'minWidth'] as $k) {
            if (isset($list[$k]) && !is_numeric($list[$k])) {
                throw new \InvalidArgumentException("字段 {$p->prop}（list:text）：{$k} 须为数字");
            }
        }
    }

    public function render(array $field, Context $ctx): string
    {
        $p = self::fromField($field);

        return Template::render('index/col/text.vue.tpl', [
            'label' => $p->label,
            'width' => $p->width,
            'minWidth' => $p->minWidth,
            'prop' => $p->prop,
            'overflow' => $p->overflow,
        ]) . "\n";
    }
}
