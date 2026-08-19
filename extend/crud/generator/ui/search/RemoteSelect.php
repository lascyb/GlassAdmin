<?php
declare (strict_types = 1);

namespace crud\generator\ui\search;

use crud\field\FieldMeta;
use crud\field\Naming;
use crud\Context;
use crud\Template;
use crud\generator\Helper;
use crud\generator\ui\UiRenderer;

/**
 * RemoteSelect 搜索 UI：remote_select
 *
 * tpl: index/search/remote_select.vue.tpl
 * params: label, prop, ext.remote.*, searchPlaceholder
 * alias: (SearchKey) belongs_to
 */
class RemoteSelect implements UiRenderer
{
    public string $label = '';
    public string $prop = '';
    public string $url = '';
    public string $valueField = '';
    public string $labelField = '';
    public string $placeholder = '';

    public static function key(): string
    {
        return 'remote_select';
    }

    public static function fromField(array $field): static
    {
        $p = new static();
        $p->label = (string) $field['label'];
        $p->prop = (string) $field['prop'];
        $p->url = FieldMeta::remoteApi($field);
        $p->valueField = FieldMeta::remoteValueField($field);
        $p->labelField = FieldMeta::remoteLabelField($field);
        $p->placeholder = FieldMeta::searchPlaceholder($field, 'remote_select');

        return $p;
    }

    public static function validate(array $field): void
    {
        $p = self::fromField($field);
        if ($p->url === '') {
            throw new \InvalidArgumentException("字段 {$p->prop}（search:remote_select）：ext.remote.api 不能为空");
        }
        if (Naming::parseRemoteApi($p->url) === null) {
            throw new \InvalidArgumentException("字段 {$p->prop}（search:remote_select）：ext.remote.api 格式无效（应为 module.Entity/list）");
        }
    }

    public function render(array $field, Context $ctx): string
    {
        $p = self::fromField($field);

        return Template::render('index/search/remote_select.vue.tpl', [
            'label' => $p->label,
            'prop' => $p->prop,
            'url' => Helper::adminHttpUrl($p->url),
            'valueField' => $p->valueField,
            'labelField' => $p->labelField,
            'placeholder' => $p->placeholder,
        ]) . "\n";
    }
}
