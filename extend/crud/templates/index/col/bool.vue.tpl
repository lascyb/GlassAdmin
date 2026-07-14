        <el-table-column label="<%label%>"<%width%><%minWidth%> align="center">
            <template #default="{ row }">
                <el-tag :type="row.<%prop%> ? 'warning' : 'info'" round>{{ row.<%prop%> ? '是' : '否' }}</el-tag>
            </template>
        </el-table-column>
