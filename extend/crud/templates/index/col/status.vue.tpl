        <el-table-column label="<%label%>"<%width%><%minWidth%>>
            <template #default="{ row }">
                <ga-status-tag :value="row.<%prop%>" :map="<%mapKey%>" />
            </template>
        </el-table-column>
