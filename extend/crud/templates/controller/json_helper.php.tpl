    /**
     * normalizeStringList 规范化字符串列表
     *
     * @return list<string>|null
     */
    private function normalizeStringList(mixed $value): ?array
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
