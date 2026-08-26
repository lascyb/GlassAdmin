<script setup lang="ts">
<%importBlock%>
<%keywordRef%><%advBlock%><%queryBlock%>
const { list, total, page, limit, loading, load, search } = usePaginatedList<<%entity%>Row>(
    (params) => entityApi.list(params),
    { params: queryParams },
)

const { confirm } = useConfirmAction({ successMsg: '已删除', onSuccess: load })

const editController = reactive<{ show?: boolean; id?: <%pkTs%> }>({})

const clickEdit = (id: <%pkTs%>) => Object.assign(editController, { show: true, id })
const clickAdd = () => Object.assign(editController, { show: true, id: undefined })
const clickDel = (row: <%entity%>Row) => {
    void confirm(`确定删除「${row.<%titleProp%>}」？`, () => entityApi.delete(row.<%pk%>))
}
const onSaved = () => {
    editController.show = false
    void load()
}
<%resetFn%><%extraLabelFn%></script>

<template>
    <ga-page padding="sm" min-height="max(calc(100vh - 100px), 400px)">
    <ga-table
        v-model:page="page"
        v-model:limit="limit"
        :loading="loading"
        :list="list"
        :total="total"
        @load="load"
    >
        <template #toolbar>
            <ga-search-bar<%searchBarKeyword%><%searchBarShowKeyword%>
                :loading="loading"
                keyword-placeholder="<%keywordPlaceholder%>"
                @refresh="load"
                @search="search"<%searchBarReset%>
            ><%advancedSlot%>
                <template #actions>
                    <el-button v-auth="'add'" type="primary" :icon="Plus" @click="clickAdd">新增</el-button>
                </template>
            </ga-search-bar>
        </template>

<%columns%>
        <el-table-column label="操作" fixed="right" width="140">
            <template #default="{ row }">
                <el-button v-auth="'edit'" title="编辑" plain type="primary" :icon="Edit" @click="clickEdit(row.<%pk%>)" />
                <el-button v-auth="'del'" title="删除" plain type="danger" :icon="Delete" @click="clickDel(row)" />
            </template>
        </el-table-column>

        <template #footer>
            <edit-view
                v-if="editController.show"
                v-model="editController.show"
                :id="editController.id"
                @saved="onSaved"
            />
        </template>
    </ga-table>
    </ga-page>
</template>
