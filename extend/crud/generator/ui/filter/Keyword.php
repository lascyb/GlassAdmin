<?php
declare (strict_types = 1);

namespace crud\generator\ui\filter;

use crud\Context;
use crud\Template;
use crud\generator\ui\FilterUiRenderer;

/**
 * Keyword 筛选 UI：keyword
 *
 * tpl: controller/filter/keyword.php.tpl
 * params: field._keywordExpr（编排器注入）
 * alias: 无
 */
class Keyword implements FilterUiRenderer
{
    public string $keywordExpr = '';

    public static function key(): string
    {
        return 'keyword';
    }

    public static function fromField(array $field): static
    {
        $p = new static();
        $p->keywordExpr = (string) ($field['_keywordExpr'] ?? '');

        return $p;
    }

    public static function validate(array $field): void
    {
        $p = self::fromField($field);
        if ($p->keywordExpr === '') {
            throw new \InvalidArgumentException('字段 （filter:keyword）：keywordExpr 不能为空');
        }
    }

    public function render(array $field, Context $ctx): array
    {
        $p = self::fromField($field);

        return [
            'params' => [
                "        \$keyword = trim((string) \$request->get('keyword', ''));",
            ],
            'filter' => Template::render('controller/filter/keyword.php.tpl', [
                'keywordExpr' => $p->keywordExpr,
            ]),
        ];
    }
}
