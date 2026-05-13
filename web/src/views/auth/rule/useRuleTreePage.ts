import * as ruleApi from '@/apis/auth/rule'
import type { RuleRow } from '@/apis/auth/rule'
import { ElMessage, ElMessageBox } from 'element-plus'
import { ref, shallowRef } from 'vue'

/**
 * useRuleTreeBase 加载规则树与删除（前后端页面共用）
 */
export function useRuleTreeBase(scope: 'backend' | 'frontend') {
    const treeData = shallowRef<RuleRow[]>([])
    const loading = ref(false)

    const load = async () => {
        loading.value = true
        try {
            treeData.value = await ruleApi.fetchTree(scope)
        } catch (e: unknown) {
            ElMessage.error(e instanceof Error ? e.message : '加载失败')
        } finally {
            loading.value = false
        }
    }

    const remove = async (row: RuleRow) => {
        try {
            await ElMessageBox.confirm(`确定删除「${row.name}」？`, '确认', { type: 'warning' })
        } catch {
            return
        }
        try {
            await ruleApi.deleteRule(row.id)
            ElMessage.success('已删除')
            await load()
        } catch (e: unknown) {
            ElMessage.error(e instanceof Error ? e.message : '删除失败')
        }
    }

    return { treeData, loading, load, remove }
}
