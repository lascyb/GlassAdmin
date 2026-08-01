
        if (<%raw%> !== null && <%raw%> !== '') {
            $v = <%castExpr%>;
            if (in_array($v, [<%valsPhp%>], true)) {
                $q->where('<%prop%>', $v);
            }
        }
