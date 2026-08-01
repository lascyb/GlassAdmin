<?php
declare (strict_types = 1);

namespace crud\generator\ui\list;

use crud\field\FieldMeta;
use crud\Context;
use crud\Template;
use crud\generator\ui\UiRenderer;

/**
 * Datetime 列表 UI：datetime
 *
 * tpl: index/col/datetime.vue.tpl
 * params: list.width/minWidth, label, prop
 * alias: 无（非 unix 在 ListKey 降级 text）
 */
class Datetime implements UiRenderer
{
    public string $label = '';
    public string $prop = '';
    public string $width = '';
    public string $minWidth = '';

    public static function key(): string
    {
        return 'datetime';
    }

    public static function fromField(array $field): static
    {
        $p = new static();
        $list = FieldMeta::listConfig($field) ?? [];
        $p->label = (string) $field['label'];
        $p->prop = (string) $field['prop'];
        $p->width = isset($list['width']) ? " width=\"{$list['width']}\"" : '';
        $p->minWidth = isset($list['minWidth']) ? " min-width=\"{$list['minWidth']}\"" : '';

        return $p;
    }

    public static function validate(array $field): void
    {
        $p = self::fromField($field);
        $list = FieldMeta::listConfig($field) ?? [];
        foreach (['width', 'minWidth'] as $k) {
            if (isset($list[$k]) && !is_numeric($list[$k])) {
                throw new \InvalidArgumentException("字段 {$p->prop}（list:datetime）：{$k} 须为数字");
            }
        }
    }

    public function render(array $field, Context $ctx): string
    {
        $p = self::fromField($field);

        return Template::render('index/col/datetime.vue.tpl', [
            'label' => $p->label,
            'width' => $p->width,
            'minWidth' => $p->minWidth,
            'prop' => $p->prop,
        ]) . "\n";
    }
}
