import { defineConfig } from 'vite';
import { resolve } from 'path'

export default defineConfig({
  input: 'js/main.js',
  root: resolve(__dirname, '.'),
  build: {
    outDir: './dist'
  },
  server: {
    port: 8080
  },
  // Optional: Silence Sass deprecation warnings. See note below.
  css: {
     preprocessorOptions: {
        scss: {
          silenceDeprecations: [
            'import',
            'mixed-decls',
            'color-functions',
            'global-builtin',
          ],
        },
     },
  },
});
