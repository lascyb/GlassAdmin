import { LIST_RENDER } from '../constants/list-render'

/** ListRenderType 列表列渲染类型 */
export type ListRenderType = (typeof LIST_RENDER)[keyof typeof LIST_RENDER]
