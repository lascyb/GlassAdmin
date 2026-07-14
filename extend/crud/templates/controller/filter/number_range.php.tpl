
        if (<%minVar%> !== null && <%minVar%> !== '') {
            $q->where('<%prop%>', '>=', (float) <%minVar%>);
        }
        if (<%maxVar%> !== null && <%maxVar%> !== '') {
            $q->where('<%prop%>', '<=', (float) <%maxVar%>);
        }
