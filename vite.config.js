import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';

export default defineConfig({
  plugins: [
    vue(),
    laravel({
      input: [
        //     // ВАЖНО: CSS файлы ПЕРВЫМИ для правильного каскада
        //     'resources/scss/app.scss',
        //     // Затем JS файлы
        'resources/js/app.js',
        'resources/js/finances.js',
        'resources/js/landings.js',
        'resources/js/services.js',
        'resources/js/pages/profile.js',
        'resources/js/pages/profile/disable-2fa.js',
        'resources/js/pages/verify-email.js',
        'resources/js/pages/reset-password.js',
        'resources/js/pages/forgot-password.js',
        'resources/js/pages/register.js',
        'resources/js/tariffs-payments.js',
        'resources/js/tariffs.js',
        'resources/js/pages/blogs.js',
        //     'resources/js/vue-islands.ts',
        //     // Статические ресурсы
        'resources/img/telegram.svg',
        'resources/img/viber.svg',
        'resources/img/whatsapp.svg',
        //     // main page assets
        'resources/scss/img/main/1.mp4',
        'resources/scss/img/main/screen.mp4',
        'resources/scss/img/main/winner-2021.svg',
        'resources/scss/img/main/phone.webp',
        'resources/scss/img/main/offer-figure2.webp',
        //     // main page js
        'resources/js/pages/mainPage/main.js',
        //     'resources/js/pages/mainPage/home.js',
      ],
      refresh: true,
    }),
  ],
  esbuild: {
    target: 'es2020',
  },
  resolve: {
    alias: {
      '@': '/resources/js',
      '@img': '/resources/img',
      '@pages': '/resources/js/pages',
      '@scss': '/resources/scss',
      '@creatives': '/resources/js/creatives',
      jquery: 'jquery/dist/jquery.js',
    },
  },
  optimizeDeps: {
    include: ['jquery', 'slick-carousel'],
    exclude: ['bootstrap'],
  },
  define: {
    global: 'globalThis',
    'process.env.NODE_ENV': JSON.stringify(process.env.NODE_ENV || 'development'),
  },
  build: {
    // chunkSizeWarningLimit: 400,
    // cssCodeSplit: false,
    // cssMinify: true,
    // lightningcss: {
    //   minify: true,
    //   removeCssComments: true,
    // },
    // minify: 'terser',
    // terserOptions: {
    //   compress: {
    //     drop_console: process.env.NODE_ENV === 'production',
    //     drop_debugger: process.env.NODE_ENV === 'production',
    //     pure_funcs:
    //       process.env.NODE_ENV === 'production'
    //         ? [
    //             'console.log',
    //             'console.info',
    //             'logger',
    //             'loggerError',
    //             'loggerWarn',
    //             'loggerSuccess',
    //           ]
    //         : [],
    //   },
    //   mangle: {
    //     safari10: true,
    //   },
    //   format: {
    //     comments: false,
    //   },
    // },
    // rollupOptions: {
    //   treeshake: {
    //     moduleSideEffects: id => {
    //       const normalizedId = id.replace(/\\/g, '/');
    //       // Все файлы в resources/js/ должны попасть в бандл
    //       if (normalizedId.includes('resources/js/')) {
    //         return true;
    //       }
    //       // Специальная обработка для jQuery и Bootstrap
    //       if (normalizedId.includes('jquery') || normalizedId.includes('bootstrap')) {
    //         return false;
    //       }
    //       // Дополнительные условия для специфичных файлов
    //       if (
    //         normalizedId.includes('.vue') ||
    //         normalizedId.includes('vue-islands') ||
    //         normalizedId.includes('pinia') ||
    //         normalizedId.includes('useFiltersStore') ||
    //         normalizedId.includes('useCreatives') ||
    //         normalizedId.includes('useFiltersSynchronization') ||
    //         normalizedId.includes('useCreativesUrlSync')
    //       ) {
    //         return true;
    //       }
    //       return false;
    //     },
    //     propertyReadSideEffects: false,
    //     tryCatchDeoptimization: false,
    //   },
    //   output: {
    //     manualChunks: {
    //       'vendor-jquery': ['jquery', 'slick-carousel'],
    //       'vendor-ui': ['sweetalert2', 'flatpickr'],
    //     },
    //     globals: {
    //       jquery: 'jQuery',
    //       $: 'jQuery',
    //     },
    //     format: 'es',
    //   },
    //   external: [],
    // },
  },
});
