import { defineConfig } from 'vite';
import { resolve } from 'path'

export default defineConfig({
  input: 'js/main.js',
  root: resolve(import.meta.dirname, '.'),
  build: {
    outDir: './dist',rollupOptions: {
      output: {
        // Removes hash from the main entry file (e.g., assets/index.js)
        entryFileNames: 'assets/[name].js',
        
        // Removes hash from code-split chunks (e.g., assets/vendor.js)
        chunkFileNames: 'assets/[name].js',
        
        // Removes hash from assets like CSS, images, and fonts (e.g., assets/index.css)
        assetFileNames: 'assets/[name].[ext]'
      }
    }
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
