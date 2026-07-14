
        if (<%startVar%> !== null && <%startVar%> !== '') {
            $q->where('<%prop%>', '>=', (string) <%startVar%>);
        }
        if (<%endVar%> !== null && <%endVar%> !== '') {
            $q->where('<%prop%>', '<=', (string) <%endVar%>);
        }
