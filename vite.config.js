import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';

export default defineConfig({
  plugins: [
    laravel({
      input: [
        'resources/js/finances.js',
        // 'resources/css/app.css',
        'resources/scss/app.scss',
        'resources/js/app.js',
        'resources/js/landings.js',
        'resources/js/services.js',
        'resources/js/pages/profile/disable-2fa.js',
        'resources/js/pages/verify-email.js',
        'resources/js/pages/reset-password.js',
        'resources/js/pages/forgot-password.js',
        'resources/js/creatives/app.js',
        'resources/img/telegram.svg',
        'resources/img/viber.svg',
        'resources/img/whatsapp.svg',
        'resources/js/pages/register.js',
        'resources/js/tariffs-payments.js',
        'resources/js/tariffs.js',
        'resources/js/pages/blogs.js',
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

          // Alpine.js в отдельный чанк
          if (id.includes('alpinejs')) {
            return 'vendor-alpine';
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
