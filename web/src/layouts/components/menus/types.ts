export type Menu = {
    id: number
    name: string
    path: string
    children?: Menu[]
}
export type Extend = {
    level: number
}