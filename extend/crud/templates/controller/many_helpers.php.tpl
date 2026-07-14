    /**
     * parseIdList 解析 id 列表（数组或逗号分隔）
     *
     * @return list<int>
     */
    private function parseIdList(mixed $raw): array
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
    private function validateRelatedIds(array $ids, string $modelClass): ?string
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
