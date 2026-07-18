        <el-table-column label="<%label%>"<%width%><%minWidth%>>
            <template #default="{ row }">
                <el-tag v-if="row.<%method%>?.<%labelField%>" round>{{ row.<%method%>.<%labelField%> }}</el-tag>
                <span v-else class="text-gray-400">—</span>
            </template>
        </el-table-column>
