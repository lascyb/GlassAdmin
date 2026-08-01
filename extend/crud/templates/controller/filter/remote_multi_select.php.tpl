
        if (<%var%> !== []) {
            $q->whereIn('<%pk%>', static function ($query) use (<%var%>): void {
                $query->table('<%pivot%>')->whereIn('<%rk%>', <%var%>)->field('<%fk%>');
            });
        }
