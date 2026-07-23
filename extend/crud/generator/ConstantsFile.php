<?php
declare (strict_types = 1);

namespace crud\generator;

use crud\Context;
use crud\Template;

/**
 * ConstantsFile 生成 constants.ts
 */
class ConstantsFile
{
    /** render 渲染 constants 内容 */
    public function render(Context $ctx): string
    {
        $optionsBlock = '';
        foreach ($ctx->constantNames() as $name) {
            $opts = is_array($ctx->options[$name] ?? null) ? $ctx->options[$name] : [];
            $entries = [];
            foreach ($opts as $o) {
                if (!is_array($o)) {
                    continue;
                }
                $v = $o['value'] ?? '';
                $vPhp = is_string($v) ? "'" . addslashes($v) . "'" : (string) $v;
                $label = addslashes((string) ($o['label'] ?? ''));
                $type = isset($o['type']) ? ", type: '" . addslashes((string) $o['type']) . "'" : '';
                $entries[] = "    { value: {$vPhp}, label: '{$label}'{$type} },";
            }
            $optionsBlock .= Template::render('constants/options.ts.tpl', [
                'name' => $name,
                'entries' => implode("\n", $entries),
            ]);
        }

        return Template::render('constants/constants.ts.tpl', [
            'optionsBlock' => $optionsBlock,
        ]);
    }
}
