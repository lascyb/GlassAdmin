
        $ids_<%prop%> = $this->parseIdList($data['<%prop%>'] ?? null);
        $err = $this->validateRelatedIds($ids_<%prop%>, <%relatedModel%>::class);
        if ($err !== null) {
            return $this->fail(400, $err);
        }
