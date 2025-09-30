// vite.config.ts
import vue from '@vitejs/plugin-vue'
import laravel from 'laravel-vite-plugin'
import path from 'path'
import { resolve } from 'node:path'
import { defineConfig } from 'vite'
import Components from 'unplugin-vue-components/vite'
import { BootstrapVueNextResolver } from 'bootstrap-vue-next'

export default defineConfig({
  plugins: [
    laravel({
      input: ['resources/js/app.ts'],
      ssr: 'resources/js/ssr.ts',
      refresh: true,
    }),
    vue({
      template: {
        transformAssetUrls: { base: null, includeAbsolute: false },
      },
    }),
    Components({ resolvers: [BootstrapVueNextResolver()] }),
  ],
  resolve: {
    alias: [
      { find: '@/', replacement: path.resolve(__dirname, './resources/js') },
      { find: '@/images', replacement: path.resolve(__dirname, 'resources/images') },
      { find: '@/scss', replacement: path.resolve(__dirname, 'resources/scss') },
      { find: 'ziggy-js', replacement: resolve(__dirname, 'vendor/tightenco/ziggy') },

      // ✅ IMPORTANT : rediriger TOUT import de bootstrap ESM JS vers le bundle (avec Popper inclus)
      { find: 'bootstrap/dist/js/bootstrap.esm.js', replacement: 'bootstrap/dist/js/bootstrap.bundle.min.js' },
      { find: /^bootstrap$/, replacement: 'bootstrap/dist/js/bootstrap.bundle.min.js' },
    ],
  },
})
