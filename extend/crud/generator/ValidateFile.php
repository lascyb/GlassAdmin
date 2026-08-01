<?php
declare (strict_types = 1);

namespace crud\generator;

use crud\field\FieldMeta;
use crud\Context;
use crud\Template;

/**
 * ValidateFile 生成 Validate.php
 */
class ValidateFile
{
    /** render 渲染 Validate 内容 */
    public function render(Context $ctx): string
    {
        $rules = [];
        $messages = [];
        $only = [];

        foreach ($ctx->fields as $f) {
            $prop = (string) $f['prop'];
            $form = FieldMeta::formConfig($f);
            if ($form === null) {
                continue;
            }
            $ct = FieldMeta::columnType($f);
            $relation = FieldMeta::relationType($f);
            $render = FieldMeta::formRender($f);
            $only[] = "'{$prop}'";
            $parts = [];
            if (FieldMeta::isRequired($f)) {
                $parts[] = 'require';
                $messages[] = "        '{$prop}.require' => '{$f['label']}不能为空',";
            }
            if ($relation === 'belongs_to_many' || $ct === 'json' || $render === 'multi_select' || $render === 'remote_multi_select') {
                $parts[] = 'array';
            } elseif ($relation === 'belongs_to' || $render === 'remote_select') {
                if (Helper::remoteValueIsString($f)) {
                    $maxLen = FieldMeta::formMaxLength($f) ?? 255;
                    $parts[] = 'max:' . $maxLen;
                } else {
                    $parts[] = 'integer';
                    if (isset($form['numberMin'])) {
                        $parts[] = 'egt:' . (int) $form['numberMin'];
                    } else {
                        $parts[] = 'gt:0';
                    }
                }
            } elseif (FieldMeta::isBoolField($f)) {
                $parts = ['in:0,1'];
            } elseif (FieldMeta::isEnumField($f)) {
                $vals = $ctx->enumValues($f);
                // ThinkPHP in: 规则用逗号分隔；字符串值原样拼接
                $parts[] = 'in:' . implode(',', array_map(static fn ($v): string => (string) $v, $vals));
                $messages[] = "        '{$prop}.in' => '{$f['label']}无效',";
            } elseif (in_array($ct, ['decimal', 'float'], true)) {
                $parts[] = 'float';
                if (isset($form['numberMin'])) {
                    $parts[] = 'egt:' . (float) $form['numberMin'];
                }
            } elseif (FieldMeta::isUnixDatetime($f) || ($ct === 'bigint' && $render === 'datetime')) {
                $parts[] = 'integer';
                $parts[] = 'egt:0';
            } elseif (in_array($ct, ['bigint', 'int', 'tinyint'], true) || $render === 'number') {
                $parts[] = 'integer';
                if (isset($form['numberMin'])) {
                    $parts[] = 'egt:' . (int) $form['numberMin'];
                }
            } elseif ($ct === 'date' || $render === 'date') {
                $parts[] = 'date';
            } elseif (in_array($ct, ['datetime', 'timestamp'], true) || in_array($render, ['datetime', 'time'], true)) {
                $parts[] = 'date';
            } elseif ($render === 'image') {
                $parts[] = 'max:255';
            } else {
                $maxLen = FieldMeta::formMaxLength($f);
                if ($maxLen !== null) {
                    $parts[] = 'max:' . $maxLen;
                }
                if (FieldMeta::isUnique($f) || $prop === 'code') {
                    $parts[] = 'alphaDash';
                    $messages[] = "        '{$prop}.alphaDash' => '{$f['label']}仅允许字母、数字、下划线和中划线',";
                }
            }
            if ($parts === []) {
                $parts[] = 'max:255';
            }
            $rules[] = "        '{$prop}' => '" . implode('|', $parts) . "',";
        }

        $onlyList = implode(', ', $only);
        $uniqueProp = null;
        foreach ($ctx->fields as $f) {
            if (FieldMeta::isUnique($f)) {
                $uniqueProp = (string) $f['prop'];
                break;
            }
        }
        $sceneCreateBody = $uniqueProp === null
            ? "return \$this->only([\n                {$onlyList},\n            ]);"
            : "return \$this\n            ->only([\n                {$onlyList},\n            ])\n            ->append('{$uniqueProp}', 'unique:{$ctx->table}');";

        return Template::render('validate/validate.php.tpl', [
            'nsValidate' => $ctx->nsValidate,
            'entity' => $ctx->entity,
            'title' => $ctx->title,
            'ruleBlock' => implode("\n", $rules),
            'msgBlock' => implode("\n", array_unique($messages)),
            'sceneCreateBody' => $sceneCreateBody,
            'onlyList' => $onlyList,
        ]);
    }
}
