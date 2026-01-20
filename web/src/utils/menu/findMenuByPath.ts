import type {Menu} from "@/layouts/components/menus/types.ts";


export const findMenuByPath = (ms: Menu[], path: string, prefix: string = "/"): Menu[] | undefined => {
    for (const m of ms) {
        if (m.type == "btn") {
            continue
        }
        if (m.path.startsWith("/")) {
            if (m.path == path) {
                return [m]
            } else if (m.children && m.children.length > 0) {
                let res = findMenuByPath(m.children, path, m.path)
                if (res) {
                    return [m, ...res]
                }
            }
        } else {
            let currentPath = prefix == "/" ? prefix + m.path : prefix + "/" + m.path
            console.log("currentPath", currentPath)
            if (currentPath == path) {
                return [m]
            } else if (m.children && m.children.length > 0) {
                let res = findMenuByPath(m.children, path, currentPath)
                if (res) {
                    return [m, ...res]
                }
            }
        }
    }
    return undefined
}