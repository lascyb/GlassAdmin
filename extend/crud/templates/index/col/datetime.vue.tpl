        <!-- Unix 秒（bigint + datetime 渲染）专用；MySQL datetime 列走 prop 文本列 -->
        <el-table-column label="<%label%>"<%width%><%minWidth%>>
            <template #default="{ row }">{{ formatUnixTime(row.<%prop%>) }}</template>
        </el-table-column>
