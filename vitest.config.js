import { resolve } from 'path';
import { defineConfig } from 'vitest/config';

export default defineConfig({
  test: {
    globals: true,
    environment: 'jsdom',
    setupFiles: ['./tests/setup.js'],
    include: ['tests/**/*.{test,spec}.{js,mjs,cjs,ts,mts,cts,jsx,tsx}'],
    exclude: ['node_modules', 'vendor', 'storage', 'bootstrap/cache'],
    coverage: {
      provider: 'v8',
      reporter: ['text', 'json', 'html'],
      include: ['resources/js/**/*.{js,ts,jsx,tsx}'],
      exclude: ['node_modules', 'vendor', 'tests', '**/*.config.{js,ts}', '**/*.d.ts'],
    },
  },
  resolve: {
    alias: {
      '@': resolve(__dirname, 'resources/js'),
      '@img': resolve(__dirname, 'resources/img'),
      '@pages': resolve(__dirname, 'resources/js/pages'),
      '@scss': resolve(__dirname, 'resources/scss'),
      '@creatives': resolve(__dirname, 'resources/js/creatives'),
      jquery: resolve(__dirname, 'node_modules/jquery/dist/jquery.js'),
    },
  },
});
