
        if (<%var%> !== '') {
            $q->where('<%prop%>', 'like', '%' . addcslashes(<%var%>, '%_\\') . '%');
        }
