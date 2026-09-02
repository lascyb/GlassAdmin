
/** <%fn%> <%label%>上传 */
export const <%fn%> = (formData: FormData) =>
    post<{ path: string }>(`${API_BASE}/<%fn%>`, formData)
