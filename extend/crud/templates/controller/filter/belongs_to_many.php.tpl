
        if (<%var%> !== []) {
            $q->whereIn('id', static function ($query) use (<%var%>): void {
                $query->table('<%pivot%>')->whereIn('<%rk%>', <%var%>)->field('<%fk%>');
            });
        }
