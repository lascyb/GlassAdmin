    /** <%method%> belongs_to_many <%label%> */
    public function <%method%>(): BelongsToMany
    {
        return $this->belongsToMany(<%relatedClass%>::class, '<%pivot%>', '<%fk%>', '<%rk%>');
    }
