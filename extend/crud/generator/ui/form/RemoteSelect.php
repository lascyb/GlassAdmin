<?php
declare (strict_types = 1);

namespace crud\generator\ui\form;

use crud\field\FieldMeta;
use crud\field\Naming;
use crud\Context;
use crud\Template;
use crud\generator\Helper;
use crud\generator\ui\UiRenderer;

/**
 * RemoteSelect 表单 UI：remote_select
 *
 * tpl: edit/field/remote_select.vue.tpl
 * params: form.required, ext.remote.*, relationMethodName→initVar, label, prop
 * alias: (FormKey) belongs_to / remote_select
 */
class RemoteSelect implements UiRenderer
{
    public string $label = '';
    public string $prop = '';
    public bool $required = false;
    public string $placeholder = '';
    public string $url = '';
    public string $valueField = '';
    public string $labelField = '';
    public string $initVar = '';
    public string $key = 'rel';

    public static function key(): string
    {
        return 'remote_select';
    }

    public static function fromField(array $field): static
    {
        $p = new static();
        $form = FieldMeta::formConfig($field) ?? [];
        $method = Naming::relationMethodName($field);
        $p->label = (string) $field['label'];
        $p->prop = (string) $field['prop'];
        $p->required = !empty($form['required']);
        $p->placeholder = FieldMeta::formPlaceholder($field);
        $p->url = FieldMeta::remoteApi($field);
        $p->valueField = FieldMeta::remoteValueField($field);
        $p->labelField = FieldMeta::remoteLabelField($field);
        $p->initVar = Naming::toCamel($method) . 'Init';
        $p->key = 'rel';

        return $p;
    }

    public static function validate(array $field): void
    {
        $p = self::fromField($field);
        if ($p->url === '') {
            throw new \InvalidArgumentException("字段 {$p->prop}（form:remote_select）：ext.remote.api 不能为空");
        }
        if (Naming::parseRemoteApi($p->url) === null) {
            throw new \InvalidArgumentException("字段 {$p->prop}（form:remote_select）：ext.remote.api 格式无效（应为 module.Entity/list）");
        }
    }

    public function render(array $field, Context $ctx): string
    {
        $p = self::fromField($field);

        return Template::render('edit/field/remote_select.vue.tpl', [
            'label' => $p->label,
            'prop' => $p->prop,
            'required' => $p->required ? ' required' : '',
            'placeholder' => $p->placeholder,
            'key' => $p->key,
            'url' => Helper::adminHttpUrl($p->url),
            'valueField' => $p->valueField,
            'labelField' => $p->labelField,
            'initVar' => $p->initVar,
        ]) . "\n";
    }
}
