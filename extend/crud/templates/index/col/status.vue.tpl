        <el-table-column label="<%label%>"<%width%><%minWidth%>>
            <template #default="{ row }">
                <ga-status-tag :value="row.<%prop%>" :options="<%optKey%>" />
            </template>
        </el-table-column>
