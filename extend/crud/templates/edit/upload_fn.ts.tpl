
const <%fn%> = async (options: UploadRequestOptions) => {
    const fd = new FormData()
    fd.append('file', options.file)
    try {
        const res = await postFormDataData<{ path: string }>(apis.<%constName%>, fd)
        form.<%prop%> = res.path
        ElMessage.success('上传成功')
    } catch (e: unknown) {
        ElMessage.error(e instanceof Error ? e.message : '上传失败')
    }
}
