
        if (array_key_exists('<%prop%>', $data)) {
            $ids = $this->parseIdList($data['<%prop%>']);
            $err = $this->validateRelatedIds($ids, <%relatedModel%>::class);
            if ($err !== null) {
                return $this->fail(400, $err);
            }
            <%modelAlias%>::<%sync%>($id, $ids);
        }
