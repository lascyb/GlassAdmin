<?php
declare (strict_types = 1);

namespace crud\generator\ui\filter;

use crud\field\FieldMeta;
use crud\field\Naming;
use crud\Context;
use crud\Template;
use crud\generator\Helper;
use crud\generator\ui\FilterUiRenderer;

/**
 * RemoteSelect 筛选 UI：remote_select
 *
 * tpl: controller/filter/remote_select.php.tpl
 * params: prop, remoteValueIsString→cast/check
 * alias: 无
 */
class RemoteSelect implements FilterUiRenderer
{
    public string $prop = '';
    public string $var = '';
    public string $check = '';
    public string $url = '';
    public bool $valueIsString = false;

    public static function key(): string
    {
        return 'remote_select';
    }

    public static function fromField(array $field): static
    {
        $p = new static();
        $p->prop = (string) $field['prop'];
        $p->var = '$' . Naming::toCamel($p->prop);
        $p->url = FieldMeta::remoteApi($field);
        $p->valueIsString = Helper::remoteValueIsString($field);
        $p->check = '';

        return $p;
    }

    public static function validate(array $field): void
    {
        $p = self::fromField($field);
        if ($p->url === '') {
            throw new \InvalidArgumentException("字段 {$p->prop}（filter:remote_select）：ext.remote.api 不能为空");
        }
        if (Naming::parseRemoteApi($p->url) === null) {
            throw new \InvalidArgumentException("字段 {$p->prop}（filter:remote_select）：ext.remote.api 格式无效（应为 module.Entity/list）");
        }
    }

    public function render(array $field, Context $ctx): array
    {
        $p = self::fromField($field);
        if ($p->valueIsString) {
            $params = ["        {$p->var} = trim((string) \$request->get('{$p->prop}', ''));"];
            $p->check = "{$p->var} !== ''";
        } else {
            $params = ["        {$p->var} = (int) \$request->get('{$p->prop}', 0);"];
            $p->check = "{$p->var} > 0";
        }

        return [
            'params' => $params,
            'filter' => Template::render('controller/filter/remote_select.php.tpl', [
                'prop' => $p->prop,
                'var' => $p->var,
                'check' => $p->check,
            ]),
        ];
    }
}
