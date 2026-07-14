static function (<%modelAlias%> $row): array {
            $data = $row->toArray();<%listMapExtra%>

            return $data;
        }
