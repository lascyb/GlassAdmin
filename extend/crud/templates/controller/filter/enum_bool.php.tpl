
        if (<%raw%> !== null && <%raw%> !== '') {
            $v = (int) <%raw%>;
            if (in_array($v, [<%valsPhp%>], true)) {
                $q->where('<%prop%>', $v);
            }
        }
