export type Menu = {
    id: number
    name: string
    path: string
    path_full: string; //根据层级前端自动补全
    /** {menu:目录,view:页面,route:路由(不入侧栏),btn:按钮,iframe:内嵌,link:外链} */
    type: 'menu' | 'view' | 'route' | 'btn' | 'iframe' | 'link'
    /** iframe 鉴权策略 ID（空 = 直链）；对应 embed/auth 提供方 */
    method?: string | null
    /** {0:侧栏可见,1:展示全部时可发现,2:不入侧栏（目录）} */
    visibility?: number
    /** {1:页面缓存,0:离开即销毁} 仅 view/route；缺省当缓存 */
    keep_alive?: number
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
    "menu": ["menu", "view", "route", "btn", "iframe", "link"],
    "view": ["view", "route", "btn"],
    "route": ["route", "btn"],
    "btn": [],
    "iframe": [],
    "link": [],
}
