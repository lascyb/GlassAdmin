export type Menu = {
    id: number
    name: string
    path: string
    type: string
    component?: string
    // components?: { [key: string]: any }
    children?: Menu[]
}
export type Extend = {
    level: number
}