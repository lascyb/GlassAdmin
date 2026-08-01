<?php
declare (strict_types = 1);

namespace crud\generator\ui\form;

use crud\Context;
use crud\Template;
use crud\generator\ui\UiRenderer;

/**
 * ImageUpload 表单 UI：image_upload
 *
 * tpl: edit/field/image_upload.vue.tpl
 * params: label, prop → uploadFn
 * alias: (FormKey) image/file → image_upload
 */
class ImageUpload implements UiRenderer
{
    public string $label = '';
    public string $prop = '';
    public string $uploadFn = '';

    public static function key(): string
    {
        return 'image_upload';
    }

    public static function fromField(array $field): static
    {
        $p = new static();
        $p->label = (string) $field['label'];
        $p->prop = (string) $field['prop'];
        $p->uploadFn = 'onUpload' . str_replace(' ', '', ucwords(str_replace('_', ' ', $p->prop)));

        return $p;
    }

    public static function validate(array $field): void
    {
        self::fromField($field);
    }

    public function render(array $field, Context $ctx): string
    {
        $p = self::fromField($field);

        return Template::render('edit/field/image_upload.vue.tpl', [
            'label' => $p->label,
            'prop' => $p->prop,
            'uploadFn' => $p->uploadFn,
        ]) . "\n";
    }
}
