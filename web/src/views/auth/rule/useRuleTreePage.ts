import * as ruleApi from '@/apis/auth/rule'
import type { RuleRow } from '@/apis/auth/rule'
import { useTreeCrud } from '@/composables/useTreeCrud'

/**
 * useRuleTreeBase 加载规则树与删除（前后端页面共用）
 */
export function useRuleTreeBase(scope: 'backend' | 'frontend') {
    const { treeData, loading, load, remove, topIds } = useTreeCrud<RuleRow>(
        () => ruleApi.fetchTree(scope),
        (id) => ruleApi.deleteRule(id),
        {
            getDeleteMessage: (row) => `确定删除「${row.name}」？`,
            deleteSuccessMsg: '已删除',
        },
    )

    return { treeData, loading, load, remove, top_ids: topIds }
}
