
            $tags = $data['<%method%>'] ?? [];
            $data['<%prop%>'] = array_map(static fn (array $t): int => (int) $t['id'], $tags);
