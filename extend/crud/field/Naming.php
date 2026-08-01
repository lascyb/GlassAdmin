<?php
declare (strict_types = 1);

namespace crud\field;

/**
 * Naming 命名与远程 API 解析工具
 */
class Naming
{
    public static function parseRemoteApi(?string $optionsKey): ?array
    {
        if ($optionsKey === null || $optionsKey === '') {
            return null;
        }
        // module 为点分小写路径（可多级），entity 为 PascalCase
        if (!preg_match('/^((?:[a-z][a-z0-9_]*)(?:\.[a-z][a-z0-9_]*)*)\.([A-Z][A-Za-z0-9]*)\/list$/', $optionsKey, $m)) {
            return null;
        }

        return [
            'module' => str_replace('.', '/', $m[1]),
            'entity' => $m[2],
            'url'    => $optionsKey,
        ];
    }
    public static function relationMethodName(array $field): string
    {
        $prop = (string) ($field['prop'] ?? '');
        if (str_ends_with($prop, '_ids')) {
            return substr($prop, 0, -4) . 's';
        }
        if (str_ends_with($prop, '_id')) {
            return substr($prop, 0, -3);
        }
        // 无 _id/_ids 后缀时退回 relationProp 首段，并规范为可调用方法名
        $rp = FieldMeta::relationProp($field);
        $name = $rp !== ''
            ? (str_contains($rp, '.') ? explode('.', $rp, 2)[0] : $rp)
            : $prop;
        if ($name === '') {
            return 'related';
        }
        // field_2 → field2，避免 with('field_2') 找不到 field_2() 而落到 Query->field2
        $name = str_contains($name, '_') ? self::toCamel($name) : $name;
        // 关联方法名不能与本地列名相同：否则模型属性与关联同名、Row 出现重复键、with() 覆盖标量列
        if ($name === $prop) {
            $name .= 'Related';
        }

        return $name;
    }
    public static function uploadActionName(string $prop): string
    {
        $parts = explode('_', $prop);
        $parts = array_map(static fn (string $p): string => ucfirst($p), $parts);

        return 'upload' . implode('', $parts);
    }
    public static function resolveEntityFile(string $entity, array $paths): string
    {
        $apis = trim(str_replace('\\', '/', (string) ($paths['apis'] ?? '')));
        if ($apis !== '') {
            $base = basename(preg_replace('/\.ts$/i', '', $apis) ?? $apis);
            if ($base !== '') {
                return $base;
            }
        }
        $views = trim(str_replace('\\', '/', (string) ($paths['viewsDir'] ?? '')));
        if ($views !== '') {
            $base = basename(rtrim($views, '/'));
            if ($base !== '') {
                return $base;
            }
        }

        return self::toSnake($entity);
    }
    public static function toSnake(string $name): string
    {
        $s = preg_replace('/([a-z0-9])([A-Z])/', '$1_$2', $name) ?? $name;
        $s = preg_replace('/([A-Z]+)([A-Z][a-z])/', '$1_$2', $s) ?? $s;

        return strtolower($s);
    }
    public static function toCamel(string $snake): string
    {
        return lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $snake))));
    }
}
