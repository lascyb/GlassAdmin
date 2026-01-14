import type {Menu} from "../layouts/components/menus/types.ts";


type MenuSearchResult = {
    menu: Menu;
    path: Menu[]; // 从根节点到目标节点的路径数组
    breadcrumb: string[]; // 路径名称数组
};

export function findMenuByPath(menus: Menu[], targetPath: string): MenuSearchResult | null {

    function search(nodes: Menu[], currentPath: Menu[] = [], breadcrumb: string[] = []): MenuSearchResult | null {
        for (const node of nodes) {
            // 创建新的路径副本，避免引用问题
            const newPath = [...currentPath, node];
            const newBreadcrumb = [...breadcrumb, node.path];

            // 如果找到目标节点
            if (node.path === targetPath) {
                return {
                    menu: node,
                    path: newPath,
                    breadcrumb: newBreadcrumb
                };
            }

            // 递归搜索子节点
            if (node.children && node.children.length > 0) {
                const result = search(node.children, newPath, newBreadcrumb);
                if (result) {
                    return result;
                }
            }
        }
        return null;
    }

    return search(menus);
}