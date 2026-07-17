<?php
declare (strict_types = 1);

namespace crud\generator;

use crud\Context;

/**
 * Helper 各文件生成器共用的命名/类型辅助
 */
class Helper
{
    /** idsForMethodName tag_ids → tagIdsFor */
    public static function idsForMethodName(string $prop): string
    {
        if (str_ends_with($prop, '_ids')) {
            return Context::toCamel(substr($prop, 0, -4) . '_ids') . 'For';
        }

        return Context::toCamel($prop) . 'IdsFor';
    }

    /** phpLiteral PHP 字面量（字符串自动加引号） */
    public static function phpLiteral(mixed $value): string
    {
        if (is_int($value)) {
            return (string) $value;
        }

        return "'" . addslashes((string) $value) . "'";
    }

    /** phpLiteralList 枚举值列表转 PHP 数组字面量 */
    public static function phpLiteralList(array $values): string
    {
        return implode(', ', array_map([self::class, 'phpLiteral'], $values));
    }

    /** enumPayloadFn enum 字段 payload 闭包 */
    public static function enumPayloadFn(Context $ctx, array $f): string
    {
        $vals = $ctx->enumValues($f);
        $valsPhp = self::phpLiteralList($vals);
        $default = self::phpLiteral($vals[0] ?? 1);
        $isString = $vals !== [] && !is_int($vals[0]);
        if ($isString) {
            return "static fn (mixed \$v): string => in_array((string) \$v, [{$valsPhp}], true) ? (string) \$v : {$default}";
        }

        return "static fn (mixed \$v): int => in_array((int) \$v, [{$valsPhp}], true) ? (int) \$v : {$default}";
    }

    /** tsType 字段 TS 类型（按 column.type / form.render） */
    public static function tsType(array $f, bool $required = false): string
    {
        $ct = Context::columnType($f);
        $render = Context::formRender($f);

        if ($ct === 'json' || $render === 'multi_select') {
            return 'string[] | null';
        }
        if (in_array($ct, ['bigint', 'int', 'tinyint', 'decimal'], true)
            || in_array($render, ['number', 'switch', 'radio', 'select', 'datetime', 'remote_select'], true)
            || Context::isUnixDatetime($f)
        ) {
            return 'number';
        }
        if (in_array($ct, ['date', 'datetime', 'timestamp', 'varchar', 'char', 'text', 'mediumtext', 'longtext'], true)
            || in_array($render, ['input', 'textarea', 'color', 'image', 'date', 'readonly'], true)
        ) {
            return $required ? 'string' : 'string | null';
        }

        return $required ? 'string' : 'string | null';
    }

    /** hasFormRender 是否存在指定 form.render */
    public static function hasFormRender(Context $ctx, string $type): bool
    {
        foreach ($ctx->fields as $f) {
            if (Context::formRender($f) === $type) {
                return true;
            }
        }

        return false;
    }

    /** titleProp 删除确认用展示字段 */
    public static function titleProp(Context $ctx): string
    {
        foreach ($ctx->fields as $f) {
            if (($f['prop'] ?? '') === 'title') {
                return 'title';
            }
        }
        foreach ($ctx->fields as $f) {
            if (Context::isRequired($f) && Context::isStringColumn($f)) {
                return (string) $f['prop'];
            }
        }

        return 'id';
    }

    /** constantsImport options 的 import 行 */
    public static function constantsImport(Context $ctx): string
    {
        $names = array_keys($ctx->options);
        if ($names === []) {
            return '';
        }

        return 'import { ' . implode(', ', $names) . " } from '@/views/{$ctx->module}/{$ctx->entityFile}/constants'\n";
    }

    /** modelNamespace 模块路径（/）转 Model 命名空间 */
    public static function modelNamespace(string $module): string
    {
        if ($module === '') {
            return 'app\\admin\\model';
        }

        return 'app\\admin\\model\\' . str_replace('/', '\\', $module);
    }
}
