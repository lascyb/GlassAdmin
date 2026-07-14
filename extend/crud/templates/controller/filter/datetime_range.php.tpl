
        if (<%startVar%> !== null && <%startVar%> !== '') {
            $q->where('<%prop%>', '>=', (int) <%startVar%>);
        }
        if (<%endVar%> !== null && <%endVar%> !== '') {
            $q->where('<%prop%>', '<=', (int) <%endVar%>);
        }
