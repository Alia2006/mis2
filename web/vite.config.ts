import vue from '@vitejs/plugin-vue'
import { resolve } from 'path'
import type { ConfigEnv, UserConfig } from 'vite'
import { loadEnv } from 'vite'
import { svgBuilder } from './src/components/icon/svg/index'
import { customHotUpdate, isProd } from './src/utils/vite'

const pathResolve = (dir: string): any => {
    return resolve(__dirname, '.', dir)
}

// https://vitejs.cn/config/
const viteConfig = ({ mode }: ConfigEnv): UserConfig => {
    const { VITE_PORT, VITE_OPEN, VITE_BASE_PATH, VITE_OUT_DIR, VITE_PROXY_TARGET } = loadEnv(mode, process.cwd())

    const alias: Record<string, string> = {
        '/@': pathResolve('./src/'),
        assets: pathResolve('./src/assets'),
        'vue-i18n': isProd(mode) ? 'vue-i18n/dist/vue-i18n.cjs.prod.js' : 'vue-i18n/dist/vue-i18n.cjs.js',
    }

    /**
     * 本地开发代理：接口与上传文件静态资源均走目标服务器，路径原样透传
     * nginx 侧 /admin、/api 不剥前缀直转 Workerman(8030)，本地与线上 URL 完全一致
     * - /admin   后台 API（ThinkPHP admin 应用）
     * - /api     前台 API（ThinkPHP api 应用）
     * - /storage 上传文件（nginx alias 到 Forguncy Upload 目录）
     */
    const proxy: Record<string, object> = VITE_PROXY_TARGET
        ? {
              '/admin':   { target: VITE_PROXY_TARGET, changeOrigin: true },
              '/api':     { target: VITE_PROXY_TARGET, changeOrigin: true },
              '/storage': { target: VITE_PROXY_TARGET, changeOrigin: true },
          }
        : {}

    return {
        plugins: [vue(), svgBuilder('./src/assets/icons/'), customHotUpdate()],
        root: process.cwd(),
        resolve: { alias },
        base: VITE_BASE_PATH,
        server: {
            port: parseInt(VITE_PORT),
            open: VITE_OPEN != 'false',
            proxy,
        },
        build: {
            cssCodeSplit: false,
            sourcemap: false,
            outDir: VITE_OUT_DIR,
            emptyOutDir: true,
            chunkSizeWarningLimit: 1500,
        },
    }
}

export default viteConfig
