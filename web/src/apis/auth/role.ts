export const get = (id: number) => {
    return new Promise((resolve, _) => {
        resolve({
            pid: 1,
            id: id,
            name: `角色:${id}`,
            desc: `描述:${id}`,
        });
    })
}