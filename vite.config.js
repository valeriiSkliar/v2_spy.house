import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';

export default defineConfig({
  plugins: [
    laravel({
      input: [
        // 'resources/css/app.css',
        'resources/scss/app.scss',
        'resources/js/app.js',
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
    chunkSizeWarningLimit: 600,
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
        pure_funcs: ['console.log', 'console.info', 'logger'],
      },
      mangle: {
        safari10: true,
      },
      format: {
        comments: false,
      },
    },
  },
});
