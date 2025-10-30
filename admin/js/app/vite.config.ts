import { defineConfig } from 'vite';
import path from 'node:path';

export default defineConfig({
  build: {
    outDir: path.resolve(__dirname, '../../build'),
    emptyOutDir: false,
    manifest: true,
    rollupOptions: {
      input: {
        admin: path.resolve(__dirname, 'src/admin.tsx'),
      },
      output: {
        entryFileNames: `assets/[name].js`,
        chunkFileNames: `assets/[name]-[hash].js`,
        assetFileNames: `assets/[name]-[hash][extname]`,
      },
    },
  },
});


