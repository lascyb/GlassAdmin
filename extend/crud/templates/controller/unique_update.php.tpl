
        if (array_key_exists('<%prop%>', $data)) {
            $val = trim((string) $data['<%prop%>']);
            if ($val !== '' && <%modelAlias%>::where('<%prop%>', $val)->where('id', '<>', $id)->count() > 0) {
                return $this->fail(400, '<%label%>已存在');
            }
        }
