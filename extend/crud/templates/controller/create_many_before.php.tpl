
        $ids_<%prop%> = CrudHelper::parseIdList($data['<%prop%>'] ?? null);
        $err = CrudHelper::validateRelatedIds($ids_<%prop%>, <%relatedModel%>::class);
        if ($err !== null) {
            return $this->fail(400, $err);
        }
