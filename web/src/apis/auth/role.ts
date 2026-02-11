export const get = (id: number) => {
    console.log("get",id);
    return new Promise((resolve, _) => {
       setTimeout(() => {
           resolve({
               pid: 20,
               id: id,
               name: `角色:${id}`,
               desc: `描述:${id}`,
           });
       },1000)
    })
}