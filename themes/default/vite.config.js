import { defineConfig } from 'vite';
import { resolve } from 'path'
import path from 'path'

export default defineConfig({
  root: resolve(import.meta.dirname, '.'),
  input: './js/main.js',
  build: {
    outDir: '../..', rollupOptions: {
      output: {
        // Removes hash from the main entry file (e.g., assets/index.js)
        entryFileNames: 'assets/[name].js',
        
        // Removes hash from code-split chunks (e.g., assets/vendor.js)
        chunkFileNames: 'assets/[name].js',
        
        // Removes hash from assets like CSS, images, and fonts (e.g., assets/index.css)
        assetFileNames: (assetInfo) => {
          if (assetInfo.name && assetInfo.name.endsWith('.css')) {
            return 'assets/[name].[ext]'; 
          }
          return 'assets/[name].[ext]'; 
        },
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
