import type {editor} from "monaco-editor";
/**
 * Props
 * MonacoEditor 组件的属性接口
 * 描述单页 Monaco 编辑器的初始化与运行时配置场景
 */
export interface Props {
    /**
     * initOptions
     * 创建实例时一次性注入的底层配置，适合容器尺寸、初始主题等一次性参数
     */
    initOptions?:  editor.IStandaloneEditorConstructionOptions;
    /**
     * options
     * 运行期可变更的编辑器选项，通过 watch 同步调用 updateOptions 实时生效
     */
    options?: editor.IEditorOptions & editor.IGlobalEditorOptions

    /**
     * language
     * 指定编辑器语言模式，便于调用 setModelLanguage 动态切换
     * @default undefined
     */
    language?: string

    /**
     * theme
     * 控制组件级主题，底层通过 monaco.editor.setTheme 应用
     * @default 'vs-dark'
     */
    theme?: string
}

