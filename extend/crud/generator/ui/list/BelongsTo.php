<?php
declare (strict_types = 1);

namespace crud\generator\ui\list;

use crud\field\FieldMeta;
use crud\field\Naming;
use crud\Context;
use crud\Template;
use crud\generator\ui\UiRenderer;

/**
 * BelongsTo 列表 UI：belongs_to
 *
 * tpl: index/col/belongs_to.vue.tpl
 * params: list.width/minWidth, relationMethodName, remoteLabelField, label, prop
 * alias: (ListKey) relation belongs_to
 */
class BelongsTo implements UiRenderer
{
    public string $label = '';
    public string $prop = '';
    public string $width = '';
    public string $minWidth = '';
    public string $method = '';
    public string $labelField = '';

    public static function key(): string
    {
        return 'belongs_to';
    }

    public static function fromField(array $field): static
    {
        $p = new static();
        $list = FieldMeta::listConfig($field) ?? [];
        $p->label = (string) $field['label'];
        $p->prop = (string) $field['prop'];
        $p->width = isset($list['width']) ? " width=\"{$list['width']}\"" : '';
        $p->minWidth = isset($list['minWidth']) ? " min-width=\"{$list['minWidth']}\"" : '';
        $p->method = Naming::relationMethodName($field);
        $p->labelField = FieldMeta::remoteLabelField($field);

        return $p;
    }

    public static function validate(array $field): void
    {
        $p = self::fromField($field);
        $list = FieldMeta::listConfig($field) ?? [];
        foreach (['width', 'minWidth'] as $k) {
            if (isset($list[$k]) && !is_numeric($list[$k])) {
                throw new \InvalidArgumentException("字段 {$p->prop}（list:belongs_to）：{$k} 须为数字");
            }
        }
    }

    public function render(array $field, Context $ctx): string
    {
        $p = self::fromField($field);

        return Template::render('index/col/belongs_to.vue.tpl', [
            'label' => $p->label,
            'width' => $p->width,
            'minWidth' => $p->minWidth,
            'prop' => $p->prop,
            'method' => $p->method,
            'labelField' => $p->labelField,
        ]) . "\n";
    }
}
