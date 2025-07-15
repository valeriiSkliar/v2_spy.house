import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';

export default defineConfig({
  plugins: [
    vue(),
    laravel({
      input: [
        'resources/js/finances.js',
        // 'resources/css/app.css',
        'resources/scss/app.scss',
        'resources/js/app.js',
        'resources/js/landings.js',
        'resources/js/services.js',
        'resources/js/pages/profile.js',
        'resources/js/pages/profile/disable-2fa.js',
        'resources/js/pages/verify-email.js',
        'resources/js/pages/reset-password.js',
        'resources/js/pages/forgot-password.js',
        'resources/img/telegram.svg',
        'resources/img/viber.svg',
        'resources/img/whatsapp.svg',
        'resources/js/pages/register.js',
        'resources/js/tariffs-payments.js',
        'resources/js/tariffs.js',
        'resources/js/pages/blogs.js',
        'resources/js/vue-islands.ts',
        // main page
        'resources/scss/img/main/1.mp4',
        'resources/scss/img/main/screen.mp4',
        'resources/scss/img/main/winner-2021.svg',
        'resources/scss/img/main/phone.webp',
        'resources/scss/img/main/offer-figure2.webp',
        // main page js (только custom логика, библиотеки подключены через <script>)
        'resources/js/pages/mainPage/main.js',
        'resources/js/pages/mainPage/home.js',
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
    include: ['jquery', 'alpinejs'],
  },
  build: {
    chunkSizeWarningLimit: 400,
    cssCodeSplit: true,
    cssMinify: 'lightningcss',
    lightningcss: {
      minify: true,
      removeCssComments: true,
    },
    minify: 'terser',
    terserOptions: {
      compress: {
        drop_console: process.env.NODE_ENV === 'production',
        drop_debugger: process.env.NODE_ENV === 'production',
        pure_funcs:
          process.env.NODE_ENV === 'production'
            ? [
                'console.log',
                'console.info',
                'logger',
                'loggerError',
                'loggerWarn',
                'loggerSuccess',
              ]
            : [],
      },
      mangle: {
        safari10: true,
      },
      format: {
        comments: false,
      },
    },
    rollupOptions: {
      treeshake: {
        moduleSideEffects: id => {
          const normalizedId = id.replace(/\\/g, '/');

          if (
            normalizedId.includes('.vue') ||
            normalizedId.includes('/stores/') ||
            normalizedId.includes('/composables/') ||
            normalizedId.includes('/pages/') ||
            normalizedId.includes('/components/') ||
            normalizedId.includes('/helpers/') ||
            normalizedId.includes('/utils/') ||
            normalizedId.includes('/validation/') ||
            normalizedId.includes('/validation-constants.js') ||
            normalizedId.includes('/validation-methods.js') ||
            normalizedId.includes('/validation-patterns.js') ||
            normalizedId.includes('/validation-messages.js') ||
            normalizedId.includes('/services/') ||
            normalizedId.includes('/api/') ||
            normalizedId.includes('/vue-components/') ||
            normalizedId.includes('/types/') ||
            normalizedId.includes('/libs/') ||
            normalizedId.includes('/creatives/') ||
            normalizedId.includes('vue-islands') ||
            normalizedId.includes('pinia') ||
            normalizedId.includes('useFiltersStore') ||
            normalizedId.includes('useCreatives') ||
            normalizedId.includes('useFiltersSynchronization') ||
            normalizedId.includes('useCreativesUrlSync') ||
            normalizedId.includes('base-select.js')
          ) {
            return true;
          }
          return false;
        },
        propertyReadSideEffects: false,
        tryCatchDeoptimization: false,
      },
      output: {
        manualChunks(id) {
          if (id.includes('components/loader')) {
            return 'app';
          }

          if (id.includes('base-select.js')) {
            return 'app';
          }

          if (id.includes('node_modules/jquery')) {
            return 'vendor-jquery';
          }

          if (id.includes('sweetalert2') || id.includes('flatpickr')) {
            return 'vendor-ui';
          }

          if (id.includes('bootstrap')) {
            return 'vendor-bootstrap';
          }

          if (id.includes('swiper')) {
            return 'vendor-sliders';
          }

          if (id.includes('alpinejs')) {
            return 'vendor-alpine';
          }

          if (id.includes('node_modules')) {
            return 'vendor-misc';
          }
        },
      },
    },
  },
});
