    /** <%method%> belongs_to <%label%> */
    public function <%method%>(): BelongsTo
    {
        return $this->belongsTo(<%relatedClass%>::class, '<%fk%>', '<%localKey%>');
    }
