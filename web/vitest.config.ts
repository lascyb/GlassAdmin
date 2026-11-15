import { defineConfig, mergeConfig } from 'vitest/config'
import viteConfig from './vite.config'

/**
 * 测试配置单独存放：vite.config.ts 只服务生产构建，不依赖 vitest。
 * 复用 vite.config.ts 的 plugins / alias，保证 @ 与 @components 在测试中可解析。
 */
export default mergeConfig(
    viteConfig,
    defineConfig({
        test: {
            environment: 'node',
            // Windows 沙箱下 fork 子进程会被拒绝（spawn EPERM），threads 无此限制
            pool: 'threads',
            include: ['src/**/*.test.ts'],
            // color.test.ts 是 node --experimental-strip-types 脚本，由 test:theme 单独跑
            exclude: ['node_modules', 'dist', 'src/theme/color.test.ts']
        }
    })
)
