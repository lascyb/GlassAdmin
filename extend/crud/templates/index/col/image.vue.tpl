        <el-table-column label="<%label%>"<%width%><%minWidth%> align="center">
            <template #default="{ row }">
                <el-image
                    v-if="row.<%prop%>"
                    :src="resolveStorageUrl(row.<%prop%>)"
                    fit="cover"
                    class="h-10 w-10 rounded"
                    :preview-src-list="[resolveStorageUrl(row.<%prop%>)]"
                    preview-teleported
                />
                <span v-else class="text-gray-400">—</span>
            </template>
        </el-table-column>
