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
 * EnumFilter 筛选 UI：enum
 *
 * tpl: controller/filter/enum.php.tpl | enum_any.php.tpl
 * params: prop, enumValues / bool → valsPhp, castExpr
 * alias: (FilterKey) bool/select/radio/enum
 */
class EnumFilter implements FilterUiRenderer
{
    public string $prop = '';
    public string $raw = '';
    public string $valsPhp = '';
    public string $castExpr = '';
    public bool $any = false;

    public static function key(): string
    {
        return 'enum';
    }

    public static function fromField(array $field): static
    {
        $p = new static();
        $p->prop = (string) $field['prop'];
        $p->raw = '$' . Naming::toCamel($p->prop) . 'Raw';
        $p->valsPhp = '';
        $p->castExpr = '';
        $p->any = false;

        return $p;
    }

    public static function validate(array $field): void
    {
        self::fromField($field);
    }

    public function render(array $field, Context $ctx): array
    {
        $p = self::fromField($field);
        $sr = FieldMeta::searchRender($field);
        $vals = ($sr === 'bool' || FieldMeta::isBoolField($field)) ? [0, 1] : $ctx->enumValues($field);
        $p->valsPhp = Helper::phpLiteralList($vals);
        $params = ["        {$p->raw} = \$request->get('{$p->prop}');"];
        if ($p->valsPhp === '') {
            $p->any = true;
            $filter = Template::render('controller/filter/enum_any.php.tpl', [
                'prop' => $p->prop,
                'raw' => $p->raw,
            ]);
        } else {
            $p->any = false;
            $p->castExpr = Helper::enumIsString($vals)
                ? '(string) ' . $p->raw
                : '(int) ' . $p->raw;
            $filter = Template::render('controller/filter/enum.php.tpl', [
                'prop' => $p->prop,
                'raw' => $p->raw,
                'valsPhp' => $p->valsPhp,
                'castExpr' => $p->castExpr,
            ]);
        }

        return [
            'params' => $params,
            'filter' => $filter,
        ];
    }
}
