import { del, get, post, put } from '@/apis/request'

/** <%entity%>Row 列表/详情行 */
export type <%entity%>Row = {
<%rowBlock%>
}

/** <%entity%>ListRes 分页列表 */
export type <%entity%>ListRes = {
    list: <%entity%>Row[]
    total: number
    page: number
    limit: number
}

/** <%entity%>Payload 新建/更新载荷 */
export type <%entity%>Payload = {
<%payloadBlock%>
}

/** API_BASE 控制器路径前缀 */
const API_BASE = '<%apiBase%>'

/** list 分页列表 */
export const list = (params: {
<%listParams%>
}) => get<<%entity%>ListRes>(`${API_BASE}/list`, { params })

/** read 详情 */
export const read = (id: <%pkTs%>) => get<<%entity%>Row>(`${API_BASE}/read`, { params: { id } })

/** create 新建 */
export const create = (payload: <%entity%>Payload) =>
    post<{ id: <%pkTs%> }>(`${API_BASE}/create`, payload)

/** update 更新 */
export const update = (id: <%pkTs%>, payload: <%entity%>Payload) =>
    put<null>(`${API_BASE}/update?id=${encodeURIComponent(String(id))}`, payload)

/** remove 删除（export 为 delete，因 delete 为保留字） */
const remove = (id: <%pkTs%>) => del<null>(`${API_BASE}/delete`, { params: { id } })

export { remove as delete }
<%uploadExports%>
