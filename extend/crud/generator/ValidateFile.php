<?php
declare (strict_types = 1);

namespace crud\generator;

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
            $form = Context::formConfig($f);
            if ($form === null) {
                continue;
            }
            $ct = Context::columnType($f);
            $relation = Context::relationType($f);
            $render = Context::formRender($f);
            $only[] = "'{$prop}'";
            $parts = [];
            if (Context::isRequired($f)) {
                $parts[] = 'require';
                $messages[] = "        '{$prop}.require' => '{$f['label']}不能为空',";
            }
            if ($relation === 'belongs_to_many' || $ct === 'json' || $render === 'multi_select' || $render === 'remote_multi_select') {
                $parts[] = 'array';
            } elseif ($relation === 'belongs_to' || $render === 'remote_select') {
                $parts[] = 'integer';
                if (isset($form['numberMin'])) {
                    $parts[] = 'egt:' . (int) $form['numberMin'];
                } else {
                    $parts[] = 'gt:0';
                }
            } elseif (Context::isBoolField($f)) {
                $parts = ['in:0,1'];
            } elseif (Context::isEnumField($f)) {
                $vals = $ctx->enumValues($f);
                $parts[] = 'in:' . implode(',', $vals);
                $messages[] = "        '{$prop}.in' => '{$f['label']}无效',";
            } elseif ($ct === 'decimal') {
                $parts[] = 'float';
                if (isset($form['numberMin'])) {
                    $parts[] = 'egt:' . (float) $form['numberMin'];
                }
            } elseif (Context::isUnixDatetime($f) || ($ct === 'bigint' && $render === 'datetime')) {
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
                $maxLen = Context::formMaxLength($f);
                if ($maxLen !== null) {
                    $parts[] = 'max:' . $maxLen;
                }
                if (Context::isUnique($f) || $prop === 'code') {
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
            if (Context::isUnique($f)) {
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
