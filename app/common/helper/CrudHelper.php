<?php
declare (strict_types = 1);

namespace app\common\helper;

/**
 * CrudHelper CRUD 控制器共用值处理（生成代码调用，避免每控制器重复私有方法）
 */
class CrudHelper
{
    /**
     * nullableString 去空白后空串转 null
     */
    public static function nullableString(mixed $value): ?string
    {
        $s = trim((string) $value);

        return $s === '' ? null : $s;
    }

    /**
     * normalizeStringList 规范化字符串列表
     *
     * @return list<string>|null
     */
    public static function normalizeStringList(mixed $value): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (!is_array($decoded)) {
                return null;
            }
            $value = $decoded;
        }
        if (!is_array($value)) {
            return null;
        }
        $items = array_values(array_filter(array_map(static fn ($v): string => trim((string) $v), $value)));

        return $items === [] ? null : $items;
    }

    /**
     * newUuid 生成 UUID v4
     */
    public static function newUuid(): string
    {
        $b = random_bytes(16);
        $b[6] = chr((ord($b[6]) & 0x0f) | 0x40);
        $b[8] = chr((ord($b[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($b), 4));
    }

    /**
     * parseIdList 解析 id 列表（数组或逗号分隔）
     *
     * @return list<int>
     */
    public static function parseIdList(mixed $raw): array
    {
        if (is_string($raw)) {
            $raw = $raw === '' ? [] : explode(',', $raw);
        }
        if (!is_array($raw)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map('intval', $raw), static fn (int $id): bool => $id > 0)));
    }

    /**
     * validateRelatedIds 校验关联 id
     *
     * @param list<int> $ids
     * @param class-string<\think\Model> $modelClass
     */
    public static function validateRelatedIds(array $ids, string $modelClass): ?string
    {
        if ($ids === []) {
            return null;
        }
        $count = (int) $modelClass::where('id', 'in', $ids)->count();
        if ($count !== count($ids)) {
            return '关联数据无效';
        }

        return null;
    }
}
