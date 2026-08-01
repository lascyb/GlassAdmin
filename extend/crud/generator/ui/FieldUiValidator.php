<?php
declare (strict_types = 1);

namespace crud\generator\ui;

use crud\Context;

/**
 * FieldUiValidator 生成前按 surface 校验各字段控件参数
 */
class FieldUiValidator
{
    /** validateContext 遍历 form/list/advanced 字段并调用对应 Render::validate */
    public static function validateContext(Context $ctx): void
    {
        foreach ($ctx->formFields as $f) {
            Registry::validateForm($f);
        }
        foreach ($ctx->listFields as $f) {
            Registry::validateList($f);
        }
        foreach ($ctx->advancedFields as $f) {
            Registry::validateSearch($f);
            Registry::validateFilter($f);
        }
    }
}
