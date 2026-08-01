<?php
declare (strict_types = 1);

namespace crud\generator\ui\list;

use crud\field\FieldMeta;
use crud\Context;
use crud\Template;
use crud\generator\ui\UiRenderer;

/**
 * EnumTag 列表 UI：enum_tag
 *
 * tpl: index/col/enum_tag.vue.tpl
 * params: list.width/minWidth, ext.enum.key→optKey, label, prop
 * alias: (ListKey) status/tag → enum_tag
 */
class EnumTag implements UiRenderer
{
    public string $label = '';
    public string $prop = '';
    public string $width = '';
    public string $minWidth = '';
    public string $optKey = '';

    public static function key(): string
    {
        return 'enum_tag';
    }

    public static function fromField(array $field): static
    {
        $p = new static();
        $list = FieldMeta::listConfig($field) ?? [];
        $p->label = (string) $field['label'];
        $p->prop = (string) $field['prop'];
        $p->width = isset($list['width']) ? " width=\"{$list['width']}\"" : '';
        $p->minWidth = isset($list['minWidth']) ? " min-width=\"{$list['minWidth']}\"" : '';
        $p->optKey = FieldMeta::enumKey($field);

        return $p;
    }

    public static function validate(array $field): void
    {
        $p = self::fromField($field);
        $list = FieldMeta::listConfig($field) ?? [];
        foreach (['width', 'minWidth'] as $k) {
            if (isset($list[$k]) && !is_numeric($list[$k])) {
                throw new \InvalidArgumentException("字段 {$p->prop}（list:enum_tag）：{$k} 须为数字");
            }
        }
    }

    public function render(array $field, Context $ctx): string
    {
        $p = self::fromField($field);

        return Template::render('index/col/enum_tag.vue.tpl', [
            'label' => $p->label,
            'width' => $p->width,
            'minWidth' => $p->minWidth,
            'prop' => $p->prop,
            'optKey' => $p->optKey,
        ]) . "\n";
    }
}
