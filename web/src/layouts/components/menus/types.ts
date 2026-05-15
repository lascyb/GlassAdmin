export type Menu = {
    id: number
    name: string
    path: string
    path_full: string; //根据层级前端自动补全
    /** {menu:目录,page:页面(兼容),view:页面,route:路由,btn:按钮,iframe:内嵌,link:外链} */
    type: 'menu' | 'page' | 'view' | 'route' | 'btn' | 'iframe' | 'link'
    /** 图标存储串：el:组件名、url:图片地址、local:stem */
    icon?: string | null
    component?: string
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