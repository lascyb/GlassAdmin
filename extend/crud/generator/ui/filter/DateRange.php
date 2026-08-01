<?php
declare (strict_types = 1);

namespace crud\generator\ui\filter;

use crud\field\Naming;
use crud\Context;
use crud\Template;
use crud\generator\ui\FilterUiRenderer;

/**
 * DateRange 筛选 UI：date_range
 *
 * tpl: controller/filter/date_range.php.tpl
 * params: prop → start/end vars
 * alias: 无
 */
class DateRange implements FilterUiRenderer
{
    public string $prop = '';
    public string $startVar = '';
    public string $endVar = '';

    public static function key(): string
    {
        return 'date_range';
    }

    public static function fromField(array $field): static
    {
        $p = new static();
        $p->prop = (string) $field['prop'];
        $camel = Naming::toCamel($p->prop);
        $p->startVar = '$' . $camel . 'Start';
        $p->endVar = '$' . $camel . 'End';

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
                "        {$p->startVar} = \$request->get('{$p->prop}_start');",
                "        {$p->endVar} = \$request->get('{$p->prop}_end');",
            ],
            'filter' => Template::render('controller/filter/date_range.php.tpl', [
                'prop' => $p->prop,
                'startVar' => $p->startVar,
                'endVar' => $p->endVar,
            ]),
        ];
    }
}
