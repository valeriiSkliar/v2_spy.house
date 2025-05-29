import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';

export default defineConfig({
  plugins: [
    laravel({
      input: [
        // 'resources/css/app.css',
        'resources/scss/app.scss',
        'resources/js/app.js',
        'resources/js/landings.js',
        'resources/js/services.js',
        'resources/js/pages/profile/disable-2fa.js',
        'resources/js/pages/verify-email.js',
        'resources/js/pages/reset-password.js',
        'resources/js/pages/forgot-password.js',
        'resources/img/telegram.svg',
        'resources/img/viber.svg',
        'resources/img/whatsapp.svg',
      ],
      refresh: true,
    }),
  ],
  resolve: {
    alias: {
      '@': '/resources/js',
      '@img': '/resources/img',
      '@pages': '/resources/js/pages',
      '@scss': '/resources/scss',
      jquery: 'jquery/dist/jquery.js',
    },
  },
  optimizeDeps: {
    include: ['jquery'],
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
        drop_console: true,
        drop_debugger: true,
        pure_funcs: [
          'console.log',
          'console.info',
          'logger',
          'loggerError',
          'loggerWarn',
          'loggerSuccess',
        ],
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
        moduleSideEffects: false,
        propertyReadSideEffects: false,
        tryCatchDeoptimization: false,
      },
      output: {
        manualChunks(id) {
          // Включаем loader в основной app чанк
          if (id.includes('components/loader')) {
            return 'app';
          }

          // Выносим jQuery в отдельный vendor чанк
          if (id.includes('node_modules/jquery')) {
            return 'vendor-jquery';
          }

          // Тяжелые UI библиотеки в отдельный чанк
          if (id.includes('sweetalert2') || id.includes('flatpickr')) {
            return 'vendor-ui';
          }

          // Bootstrap и связанные библиотеки
          if (id.includes('bootstrap')) {
            return 'vendor-bootstrap';
          }

          // Слайдеры и карусели (только если используются)
          if (id.includes('swiper')) {
            return 'vendor-sliders';
          }

          // Остальные vendor библиотеки
          if (id.includes('node_modules')) {
            return 'vendor-misc';
          }
        },
      },
    },
  },
});
