        <el-table-column label="<%label%>"<%width%><%minWidth%>>
            <template #default="{ row }">
                <template v-if="row.<%prop%>?.length">
                    <el-tag v-for="v in row.<%prop%>" :key="v" round class="mr-1 mb-1">{{ <%optKey%>.find((o) => o.value === v)?.label ?? v }}</el-tag>
                </template>
                <span v-else class="text-gray-400">—</span>
            </template>
        </el-table-column>
