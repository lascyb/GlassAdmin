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
        $mapBlock = '';
        foreach ($ctx->maps as $name => $map) {
            $entries = [];
            foreach ($map as $k => $item) {
                $label = addslashes((string) ($item['label'] ?? ''));
                $type = isset($item['type']) ? ", type: '" . addslashes((string) $item['type']) . "'" : '';
                $entries[] = "    {$k}: { label: '{$label}'{$type} },";
            }
            $mapBlock .= Template::render('constants/map.ts.tpl', [
                'name' => $name,
                'entries' => implode("\n", $entries),
            ]);
        }

        $optionsBlock = '';
        foreach ($ctx->options as $name => $opts) {
            $entries = [];
            foreach ($opts as $o) {
                $v = $o['value'];
                $vPhp = is_string($v) ? "'" . addslashes($v) . "'" : (string) $v;
                $label = addslashes((string) $o['label']);
                $entries[] = "    { value: {$vPhp}, label: '{$label}' },";
            }
            $optionsBlock .= Template::render('constants/options.ts.tpl', [
                'name' => $name,
                'entries' => implode("\n", $entries),
            ]);
        }

        return Template::render('constants/constants.ts.tpl', [
            'mapBlock' => $mapBlock,
            'optionsBlock' => $optionsBlock,
        ]);
    }
}
