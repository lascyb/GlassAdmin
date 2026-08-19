<?php
declare (strict_types = 1);

namespace crud\generator;

use crud\field\FieldMeta;
use crud\field\Naming;
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
            return Naming::toCamel(substr($prop, 0, -4) . '_ids') . 'For';
        }

        return Naming::toCamel($prop) . 'IdsFor';
    }

    /** phpLiteral PHP 字面量（字符串自动加引号） */
    public static function phpLiteral(mixed $value): string
    {
        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return "'" . addslashes((string) $value) . "'";
    }

    /** jsLiteral TS/JS 字面量（字符串自动加引号） */
    public static function jsLiteral(mixed $value): string
    {
        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return "'" . addslashes((string) $value) . "'";
    }

    /** jsLiteralList 枚举值列表转 JS 数组字面量 */
    public static function jsLiteralList(array $values): string
    {
        return implode(', ', array_map([self::class, 'jsLiteral'], $values));
    }

    /**
     * adminHttpUrl 前端请求路径（ThinkPHP 多应用前缀 admin/）
     * 配置里仍存 module.Entity/list，写入 .vue/.ts 时再加前缀
     */
    public static function adminHttpUrl(string $path): string
    {
        $path = ltrim($path, '/');
        if ($path === '' || str_starts_with($path, 'admin/')) {
            return $path;
        }

        return 'admin/' . $path;
    }

    /** enumIsString 枚举值是否为字符串语义 */
    public static function enumIsString(array $vals): bool
    {
        return $vals !== [] && is_string($vals[0]);
    }

    /** remoteValueIsString 远程关联存值是否为字符串（按本地列类型） */
    public static function remoteValueIsString(array $f): bool
    {
        return FieldMeta::isStringColumn($f);
    }

    /** remoteValueTsType 远程/关联字段 TS 类型 */
    public static function remoteValueTsType(array $f, bool $nullable = true): string
    {
        $base = self::remoteValueIsString($f) ? 'string' : 'number';

        return $nullable ? "{$base} | null" : $base;
    }

    /** remoteRelatedRowTs 远程回显选项行 TS 类型 */
    public static function remoteRelatedRowTs(array $f): string
    {
        $vf = FieldMeta::remoteValueField($f);
        $lf = FieldMeta::remoteLabelField($f);
        $vTs = self::remoteValueIsString($f) ? 'string' : 'number';
        if ($vf === $lf) {
            return "{ {$vf}: {$vTs} }";
        }

        return "{ {$vf}: {$vTs}; {$lf}: string }";
    }

    /** remotePayloadFn belongs_to / remote_select 写入闭包 */
    public static function remotePayloadFn(array $f): string
    {
        if (self::remoteValueIsString($f)) {
            return 'static fn (mixed $v): ?string => ($s = trim((string) $v)) !== \'\' ? $s : null';
        }

        return 'static fn (mixed $v): ?int => ($id = (int) $v) > 0 ? $id : null';
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
        $isString = self::enumIsString($vals);
        if ($isString) {
            return "static fn (mixed \$v): string => in_array((string) \$v, [{$valsPhp}], true) ? (string) \$v : {$default}";
        }

        return "static fn (mixed \$v): int => in_array((int) \$v, [{$valsPhp}], true) ? (int) \$v : {$default}";
    }

    /** tsType 字段 TS 类型（按 column.type / form.render / 枚举值类型） */
    public static function tsType(array $f, bool $required = false, ?Context $ctx = null): string
    {
        $ct = FieldMeta::columnType($f);
        $render = FieldMeta::formRender($f);

        if ($ct === 'json' || $render === 'multi_select' || $render === 'checkbox') {
            return 'string[] | null';
        }
        // 枚举：按 options 值类型（可为 string 或 number）
        if ($ctx !== null && FieldMeta::isEnumField($f)) {
            $vals = $ctx->enumValues($f);
            if (self::enumIsString($vals)) {
                return $required ? 'string' : 'string | null';
            }

            return 'number';
        }
        // 远程关联：按本地列类型
        if (FieldMeta::isRemoteField($f) || $render === 'remote_select' || FieldMeta::relationType($f) === 'belongs_to') {
            return self::remoteValueTsType($f, !$required);
        }
        // Unix 秒时间字段
        if (FieldMeta::isUnixDatetime($f)) {
            return 'number';
        }
        // MySQL date/datetime/timestamp 存字符串
        if (in_array($ct, ['date', 'datetime', 'timestamp'], true)
            || in_array($render, ['date', 'datetime', 'time'], true)
        ) {
            return $required ? 'string' : 'string | null';
        }
        if (in_array($ct, ['bigint', 'int', 'tinyint', 'decimal', 'float'], true)
            || in_array($render, ['number', 'switch', 'radio', 'select'], true)
        ) {
            return 'number';
        }
        if (in_array($ct, ['varchar', 'char', 'text', 'mediumtext', 'longtext'], true)
            || in_array($render, ['input', 'textarea', 'color', 'image', 'readonly'], true)
        ) {
            return $required ? 'string' : 'string | null';
        }

        return $required ? 'string' : 'string | null';
    }

    /** hasFormRender 是否存在指定 form.render */
    public static function hasFormRender(Context $ctx, string $type): bool
    {
        foreach ($ctx->fields as $f) {
            if (FieldMeta::formRender($f) === $type) {
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
            if (FieldMeta::isRequired($f) && FieldMeta::isStringColumn($f)) {
                return (string) $f['prop'];
            }
        }

        return 'id';
    }

    /** constantsImport options 的 import 行 */
    public static function constantsImport(Context $ctx): string
    {
        $names = $ctx->constantNames();
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
