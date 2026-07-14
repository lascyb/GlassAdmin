
        if ($keyword !== '') {
            $kw = addcslashes($keyword, '%_\\');
            $q->where('<%keywordExpr%>', 'like', '%' . $kw . '%');
        }
