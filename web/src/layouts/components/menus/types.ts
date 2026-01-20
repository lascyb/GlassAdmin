export type Menu = {
    id: number
    name: string
    path: string
    path_full: string; //根据层级前端自动补全
    type: 'menu' | 'page' | 'route' | 'btn' | 'iframe' | 'link'
    component?: string
    // components?: { [key: string]: any }
    children?: Menu[]
}
export type Extend = {
    level: number
}
export const tree = {
    "menu": ["menu", "page", "route", "btn", "iframe", "link"],
    "page": ["page", "route", "btn"],
    "route": ["route", "btn"],
    "btn": [],
    "iframe": [],
    "link": [],
}