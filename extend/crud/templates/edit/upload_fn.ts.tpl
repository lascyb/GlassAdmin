
const <%fn%> = async (options: UploadRequestOptions) => {
    const fd = new FormData()
    fd.append('file', options.file)
    try {
        const res = await apis.<%uploadApi%>(fd)
        form.<%prop%> = res.path
        ElMessage.success('上传成功')
    } catch {
        // 请求层已提示
    }
}
