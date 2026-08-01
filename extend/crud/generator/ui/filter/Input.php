<?php
declare (strict_types = 1);

namespace crud\generator\ui\filter;

use crud\field\Naming;
use crud\Context;
use crud\Template;
use crud\generator\ui\FilterUiRenderer;

/**
 * Input 筛选 UI：input
 *
 * tpl: controller/filter/input.php.tpl
 * params: prop → var
 * alias: 无
 */
class Input implements FilterUiRenderer
{
    public string $prop = '';
    public string $var = '';

    public static function key(): string
    {
        return 'input';
    }

    public static function fromField(array $field): static
    {
        $p = new static();
        $p->prop = (string) $field['prop'];
        $p->var = '$' . Naming::toCamel($p->prop);

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
                "        {$p->var} = trim((string) \$request->get('{$p->prop}', ''));",
            ],
            'filter' => Template::render('controller/filter/input.php.tpl', [
                'prop' => $p->prop,
                'var' => $p->var,
            ]),
        ];
    }
}
