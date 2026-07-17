
        if (array_key_exists('<%prop%>', $data)) {
            $ids = CrudHelper::parseIdList($data['<%prop%>']);
            $err = CrudHelper::validateRelatedIds($ids, <%relatedModel%>::class);
            if ($err !== null) {
                return $this->fail(400, $err);
            }
            <%modelAlias%>::<%sync%>($id, $ids);
        }
