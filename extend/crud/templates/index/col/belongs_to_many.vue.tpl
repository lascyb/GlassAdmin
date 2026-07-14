        <el-table-column label="<%label%>"<%width%><%minWidth%>>
            <template #default="{ row }">
                <template v-if="row.<%method%>?.length">
                    <el-tag v-for="t in row.<%method%>" :key="t.id" round class="mr-1 mb-1">{{ t.name }}</el-tag>
                </template>
                <span v-else class="text-gray-400">—</span>
            </template>
        </el-table-column>
