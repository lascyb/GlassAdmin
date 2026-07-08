import type { editor } from 'monaco-editor'

/** GaMonacoEditorProps 代码编辑器初始化与运行时配置 */
export type GaMonacoEditorProps = {
    /** initOptions 创建实例时一次性注入的底层配置 */
    initOptions?: editor.IStandaloneEditorConstructionOptions
    /** options 运行期可变更的编辑器选项 */
    options?: editor.IEditorOptions & editor.IGlobalEditorOptions
    /** language 编辑器语言模式 */
    language?: string
    /** theme 编辑器主题 {vs:浅色,vs-dark:深色,hc-black:高对比} */
    theme?: string
    /** height 容器高度 */
    height?: string
}
