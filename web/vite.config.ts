import tailwindcss from '@tailwindcss/vite'
import vue from '@vitejs/plugin-vue'
import path from 'path'
import AutoImport from 'unplugin-auto-import/vite'
import { ElementPlusResolver } from 'unplugin-vue-components/resolvers'
import Components from 'unplugin-vue-components/vite'
import { defineConfig } from 'vite'
import svgLoader from 'vite-svg-loader'
import { gaViewPlugin } from './vite-plugin-ga-view'

export default defineConfig({
    plugins: [
        gaViewPlugin(),
        vue(),
        AutoImport({
            resolvers: [ElementPlusResolver()],
        }),
        Components({
            resolvers: [ElementPlusResolver()],
            /** 不自动注册 src/components；所有本地组件须在页面内显式 import */
            dirs: [],
        }),
        tailwindcss(),
        svgLoader()
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, 'src'),
            '@components': path.resolve(__dirname, 'src/components')
        }
    },
    build: {
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (!id.includes('node_modules')) return
                    if (id.includes('monaco-editor')) return 'monaco'
                    if (id.includes('echarts')) return 'echarts'
                    if (id.includes('@element-plus/icons-vue')) return 'element-plus-icons'
                    if (id.includes('element-plus')) return 'element-plus'
                    if (id.includes('vue') || id.includes('pinia') || id.includes('vue-router')) return 'vue-vendor'
                },
            },
        },
    },
})
