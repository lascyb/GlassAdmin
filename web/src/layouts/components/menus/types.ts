export type Menu = {
    id: number
    name: string
    path: string
    path_full: string; //根据层级前端自动补全
    /** {menu:目录,page:页面(兼容),view:页面,route:路由,btn:按钮,iframe:内嵌,link:外链} */
    type: 'menu' | 'page' | 'view' | 'route' | 'btn' | 'iframe' | 'link'
    /** method 前端规则：route 表示仅作为路由、不入侧栏 */
    method?: string | null
    /** 图标存储串：el:组件名、url:图片地址、local:stem */
    icon?: string | null
    component?: string
    /** 是否已授权；catalog 模式下未授权可发现项为 false */
    authorized?: boolean
    // components?: { [key: string]: any }
    children?: Menu[]
}
export type Extend = {
    level: number
}
export const tree = {
    "menu": ["menu", "page", "view", "route", "btn", "iframe", "link"],
    "page": ["page", "view", "route", "btn"],
    "view": ["page", "view", "route", "btn"],
    "route": ["route", "btn"],
    "btn": [],
    "iframe": [],
    "link": [],
}