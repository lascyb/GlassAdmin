<?php
declare (strict_types = 1);

namespace crud\generator\ui\filter;

use crud\field\Naming;
use crud\Context;
use crud\Template;
use crud\generator\ui\FilterUiRenderer;

/**
 * NumberRange 筛选 UI：number_range
 *
 * tpl: controller/filter/number_range.php.tpl
 * params: prop → min/max vars
 * alias: 无
 */
class NumberRange implements FilterUiRenderer
{
    public string $prop = '';
    public string $minVar = '';
    public string $maxVar = '';

    public static function key(): string
    {
        return 'number_range';
    }

    public static function fromField(array $field): static
    {
        $p = new static();
        $p->prop = (string) $field['prop'];
        $camel = Naming::toCamel($p->prop);
        $p->minVar = '$' . $camel . 'Min';
        $p->maxVar = '$' . $camel . 'Max';

        return $p;
    }

    public static function validate(array $field): void
    {
        self::fromField($field);
    }

    public function render(array $field, Context $ctx): array
    {
        $p = self::fromField($field);

        return [
            'params' => [
                "        {$p->minVar} = \$request->get('{$p->prop}_min');",
                "        {$p->maxVar} = \$request->get('{$p->prop}_max');",
            ],
            'filter' => Template::render('controller/filter/number_range.php.tpl', [
                'prop' => $p->prop,
                'minVar' => $p->minVar,
                'maxVar' => $p->maxVar,
            ]),
        ];
    }
}
