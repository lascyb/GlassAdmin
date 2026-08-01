<?php
declare (strict_types = 1);

namespace crud\generator\ui\filter;

use crud\field\FieldMeta;
use crud\field\Naming;
use crud\Context;
use crud\Template;
use crud\generator\ui\FilterUiRenderer;

/**
 * RemoteMultiSelect 筛选 UI：remote_multi_select
 *
 * tpl: controller/filter/remote_multi_select.php.tpl
 * params: prop, relation.pivot/fk/rk, pk
 * alias: (FilterKey) belongs_to_many
 */
class RemoteMultiSelect implements FilterUiRenderer
{
    public string $prop = '';
    public string $var = '';
    public string $pivot = '';
    public string $fk = '';
    public string $rk = '';
    public string $pk = '';
    public string $url = '';

    public static function key(): string
    {
        return 'remote_multi_select';
    }

    public static function fromField(array $field): static
    {
        $p = new static();
        $p->prop = (string) $field['prop'];
        $p->var = '$' . Naming::toCamel($p->prop);
        $rel = FieldMeta::relationConfig($field) ?? [];
        $p->pivot = (string) ($rel['pivotTable'] ?? '');
        $p->fk = (string) ($rel['foreignKey'] ?? '');
        $p->rk = (string) ($rel['relatedKey'] ?? '');
        $p->url = FieldMeta::remoteApi($field);
        $p->pk = '';

        return $p;
    }

    public static function validate(array $field): void
    {
        $p = self::fromField($field);
        if ($p->url === '') {
            throw new \InvalidArgumentException("字段 {$p->prop}（filter:remote_multi_select）：ext.remote.api 不能为空");
        }
        if (Naming::parseRemoteApi($p->url) === null) {
            throw new \InvalidArgumentException("字段 {$p->prop}（filter:remote_multi_select）：ext.remote.api 格式无效（应为 module.Entity/list）");
        }
    }

    public function render(array $field, Context $ctx): array
    {
        $p = self::fromField($field);
        $p->pk = $ctx->primaryKey();

        return [
            'params' => [
                "        {$p->var} = CrudHelper::parseIdList(\$request->get('{$p->prop}'));",
            ],
            'filter' => Template::render('controller/filter/remote_multi_select.php.tpl', [
                'var' => $p->var,
                'pivot' => $p->pivot,
                'fk' => $p->fk,
                'rk' => $p->rk,
                'pk' => $p->pk,
            ]),
        ];
    }
}
