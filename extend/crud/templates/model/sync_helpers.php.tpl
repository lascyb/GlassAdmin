    /**
     * <%sync%> 同步<%label%>关联
     *
     * @param list<int> $ids
     */
    public static function <%sync%>(int $ownerId, array $ids): void
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn (int $id): bool => $id > 0)));
        Db::name('<%pivot%>')->where('<%fk%>', $ownerId)->delete();
        if ($ids === []) {
            return;
        }
        $rows = [];
        foreach ($ids as $rid) {
            $rows[] = ['<%fk%>' => $ownerId, '<%rk%>' => $rid];
        }
        Db::name('<%pivot%>')->insertAll($rows);
    }

    /**
     * <%idsFor%> 读取已绑定<%label%> id
     *
     * @return list<int>
     */
    public static function <%idsFor%>(int $ownerId): array
    {
        return array_map('intval', Db::name('<%pivot%>')->where('<%fk%>', $ownerId)->column('<%rk%>'));
    }
