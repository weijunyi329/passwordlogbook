import { fileURLToPath, URL } from 'node:url'

import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueDevTools from 'vite-plugin-vue-devtools'

// https://vite.dev/config/
export default defineConfig({
  server: {
    proxy: {
      '^/admin': 'http://localhost:8080', // 转发 PHP 接口请求
      '^/security': 'http://localhost:8080' ,// 转发 PHP 接口请求
      '^/.*\.php': 'http://localhost:8080/admin'
    }
  },
  plugins: [
    vue(),
    vueDevTools(),
  ],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url))
    },
  },
  build: {
    outDir: 'admin/static', // 将输出目录改为 "build"

  },
  base: './' // 设置打包后的静态资源路径
})
