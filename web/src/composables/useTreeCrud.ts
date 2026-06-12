import type { TreeEditController } from '@/composables/types'
import { useConfirmAction } from '@/composables/useConfirmAction'
import { ElMessage } from 'element-plus'
import { computed, reactive, ref, shallowRef, type Ref } from 'vue'

export type UseTreeCrudOptions<T> = {
    /** getDeleteMessage 删除确认文案 */
    getDeleteMessage?: (row: T) => string
    /** getRowId 取节点 id，默认 row.id */
    getRowId?: (row: T) => number
    /** deleteSuccessMsg 删除成功提示 */
    deleteSuccessMsg?: string
}

/**
 * useTreeCrud 树形 load/remove/openEdit({id,pid}) 模式
 */
export function useTreeCrud<T extends { id: number; children?: T[] }>(
    fetchTree: () => Promise<T[]>,
    deleteItem: (id: number) => Promise<unknown>,
    options: UseTreeCrudOptions<T> = {},
) {
    const {
        getDeleteMessage = () => '确定删除该节点？',
        getRowId = (row) => row.id,
        deleteSuccessMsg = '已删除',
    } = options

    const treeData = shallowRef<T[]>([]) as Ref<T[]>
    const loading = ref(false)
    const editController = reactive<TreeEditController>({})

    const { confirm } = useConfirmAction({ successMsg: deleteSuccessMsg })

    /** topIds 默认展开第一层 */
    const topIds = computed(() => treeData.value.map((item) => item.id))

    /** load 加载树 */
    const load = async () => {
        loading.value = true
        try {
            treeData.value = await fetchTree()
        } catch (e: unknown) {
            ElMessage.error(e instanceof Error ? e.message : '加载失败')
        } finally {
            loading.value = false
        }
    }

    /** remove 确认后删除并刷新 */
    const remove = async (row: T) => {
        const id = getRowId(row)
        await confirm(getDeleteMessage(row), () => deleteItem(id), { onSuccess: load })
    }

    /** openEdit 打开编辑弹窗 */
    const openEdit = (id: number) => {
        Object.assign(editController, { show: true, id, pid: undefined })
    }

    /** openAdd 打开新增根节点 */
    const openAdd = () => {
        Object.assign(editController, { show: true, id: undefined, pid: undefined })
    }

    /** openAddChild 打开新增子节点 */
    const openAddChild = (pid: number) => {
        Object.assign(editController, { show: true, id: undefined, pid })
    }

    /** onSaved 保存后关闭弹窗并刷新 */
    const onSaved = () => {
        editController.show = false
        void load()
    }

    return {
        treeData,
        loading,
        editController,
        topIds,
        load,
        remove,
        openEdit,
        openAdd,
        openAddChild,
        onSaved,
    }
}
