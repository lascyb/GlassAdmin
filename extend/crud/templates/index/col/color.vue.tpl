        <el-table-column label="<%label%>"<%width%><%minWidth%> align="center">
            <template #default="{ row }">
                <span
                    v-if="row.<%prop%>"
                    class="inline-block h-5 w-5 rounded border border-[var(--el-border-color)]"
                    :style="{ background: row.<%prop%> }"
                />
                <span v-else class="text-gray-400">—</span>
            </template>
        </el-table-column>
