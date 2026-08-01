<?php
declare (strict_types = 1);

namespace crud\field;

use crud\field\SearchInference;

/**
 * FieldMeta 字段配置读取与判定（供 Generator / ui 渲染器使用）
 */
class FieldMeta
{
    public static function columnType(array $field): string
    {
        $col = self::columnConfig($field);

        return is_array($col) ? (string) ($col['type'] ?? '') : '';
    }
    public static function columnConfig(array $field): ?array
    {
        $col = $field['column'] ?? null;
        if (!is_array($col) || (($col['enabled'] ?? true) === false)) {
            return null;
        }

        return $col;
    }
    public static function relationType(array $field): string
    {
        $rel = $field['relation'] ?? null;
        if (!is_array($rel) || (($rel['enabled'] ?? true) === false)) {
            return '';
        }

        return (string) ($rel['type'] ?? '');
    }
    public static function relationConfig(array $field): ?array
    {
        $rel = $field['relation'] ?? null;
        if (!is_array($rel) || (($rel['enabled'] ?? true) === false)) {
            return null;
        }

        return $rel;
    }
    public static function formConfig(array $field): ?array
    {
        $form = $field['form'] ?? null;

        return is_array($form) ? $form : null;
    }
    public static function listConfig(array $field): ?array
    {
        $list = $field['list'] ?? null;

        return is_array($list) ? $list : null;
    }
    public static function searchConfig(array $field): ?array
    {
        $search = $field['search'] ?? null;

        return is_array($search) ? $search : null;
    }
    public static function formRender(array $field): string
    {
        $form = self::formConfig($field);

        return $form !== null ? (string) ($form['render'] ?? '') : '';
    }
    public static function listRender(array $field): string
    {
        $list = self::listConfig($field);

        return $list !== null ? (string) ($list['render'] ?? 'text') : '';
    }
    public static function searchRender(array $field): string
    {
        $search = self::searchConfig($field);
        if ($search !== null && !empty($search['render'])) {
            return (string) $search['render'];
        }
        $form = self::formConfig($field);
        if ($form !== null && ($form['enabled'] ?? true) !== false) {
            $mapped = SearchInference::formToSearchRender((string) ($form['render'] ?? ''));
            if ($mapped !== '') {
                return $mapped;
            }
        }
        $col = self::columnConfig($field);
        $ct = is_array($col) ? (string) ($col['type'] ?? '') : '';

        return match ($ct) {
            'tinyint' => 'bool',
            'int', 'bigint', 'decimal', 'float' => 'number_range',
            'date' => 'date_range',
            'datetime', 'timestamp' => 'datetime_range',
            default => 'input',
        };
    }
    public static function isPrimary(array $field): bool
    {
        $col = self::columnConfig($field);

        return $col !== null && !empty($col['primary']);
    }
    public static function isRequired(array $field): bool
    {
        $form = self::formConfig($field);

        return $form !== null && !empty($form['required']);
    }
    public static function isUnique(array $field): bool
    {
        $col = self::columnConfig($field);

        return $col !== null && !empty($col['unique']);
    }
    public static function isBoolField(array $field): bool
    {
        return self::formRender($field) === 'switch'
            || self::listRender($field) === 'bool'
            || self::searchRender($field) === 'bool';
    }
    public static function isEnumField(array $field): bool
    {
        if (self::isRemoteField($field)) {
            return false;
        }

        return self::enumKey($field) !== '';
    }
    public static function isUnixDatetime(array $field): bool
    {
        return self::columnType($field) === 'bigint'
            && (self::formRender($field) === 'datetime' || self::listRender($field) === 'datetime');
    }
    public static function isStringColumn(array $field): bool
    {
        return in_array(self::columnType($field), ['varchar', 'char', 'text', 'mediumtext', 'longtext'], true);
    }
    public static function isIntColumn(array $field): bool
    {
        return in_array(self::columnType($field), ['bigint', 'int', 'tinyint'], true);
    }
    public static function isRangeSearch(array $field): bool
    {
        return in_array(self::searchRender($field), ['number_range', 'date_range', 'datetime_range'], true);
    }
    public static function fieldExt(array $field): array
    {
        return is_array($field['ext'] ?? null) ? $field['ext'] : [];
    }
    public static function enumExt(array $field): ?array
    {
        $ext = self::fieldExt($field);

        return is_array($ext['enum'] ?? null) ? $ext['enum'] : null;
    }
    public static function remoteExt(array $field): ?array
    {
        $ext = self::fieldExt($field);

        return is_array($ext['remote'] ?? null) ? $ext['remote'] : null;
    }
    public static function isRemoteField(array $field): bool
    {
        if (in_array(self::formRender($field), ['remote_select', 'remote_multi_select'], true)) {
            return true;
        }
        $search = self::searchConfig($field);
        if ($search !== null && in_array((string) ($search['render'] ?? ''), ['remote_select', 'remote_multi_select'], true)) {
            return true;
        }
        if (self::remoteApi($field) !== '') {
            return true;
        }

        return false;
    }
    public static function enumKey(array $field): string
    {
        if (self::isRemoteField($field)) {
            return '';
        }
        $enum = self::enumExt($field);
        if ($enum !== null && !empty($enum['key'])) {
            return (string) $enum['key'];
        }

        return '';
    }
    public static function remoteApi(array $field): string
    {
        $remote = self::remoteExt($field);
        if ($remote !== null && !empty($remote['api'])) {
            return (string) $remote['api'];
        }

        return '';
    }
    public static function remoteValueField(array $field): string
    {
        $remote = self::remoteExt($field);
        $v = $remote['valueField'] ?? 'id';

        return $v === '' ? 'id' : (string) $v;
    }
    public static function remoteLabelField(array $field): string
    {
        $remote = self::remoteExt($field);
        $v = $remote['labelField'] ?? 'name';

        return $v === '' ? 'name' : (string) $v;
    }
    public static function relationProp(array $field): string
    {
        $list = self::listConfig($field);

        return $list !== null ? (string) ($list['relationProp'] ?? '') : '';
    }
    public static function formMaxLength(array $field): ?int
    {
        $form = self::formConfig($field);
        if ($form !== null && isset($form['maxLength'])) {
            return (int) $form['maxLength'];
        }
        $col = self::columnConfig($field);
        if ($col !== null && isset($col['length'])) {
            return (int) $col['length'];
        }

        return null;
    }
    public static function formPlaceholder(array $field): string
    {
        $label = trim((string) ($field['label'] ?? ''));
        $render = self::formRender($field);

        return match ($render) {
            'input', 'password', 'textarea', 'number', 'readonly' => $label !== '' ? "请输入{$label}" : '请输入',
            'select', 'multi_select', 'checkbox', 'radio' => '请选择',
            'remote_select', 'remote_multi_select' => $label !== '' ? "输入关键词搜索{$label}" : '输入关键词搜索',
            'date' => '选择日期',
            'datetime', 'time' => '选择日期时间',
            default => '',
        };
    }
    public static function searchPlaceholder(array $field, string $searchRender): string
    {
        $label = trim((string) ($field['label'] ?? ''));

        return match ($searchRender) {
            'remote_select', 'remote_multi_select' => $label !== '' ? "搜索{$label}" : '搜索',
            'input' => '模糊匹配',
            default => '',
        };
    }
    public static function decimalScale(array $field): int
    {
        $col = self::columnConfig($field);
        if ($col !== null && isset($col['scale'])) {
            return (int) $col['scale'];
        }

        return 2;
    }
}
