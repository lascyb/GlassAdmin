import { SEARCH_RENDER } from '../constants/search-render'

/** SearchRenderType 搜索控件渲染类型（筛选语义，与表单不同） */
export type SearchRenderType = (typeof SEARCH_RENDER)[keyof typeof SEARCH_RENDER]
