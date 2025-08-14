import { defineConfig } from 'vite';
import path from 'path';

export default defineConfig({
    root: '.', // raíz del plugin
    server: {
        host: '127.0.0.1',
        port: 5173,
        strictPort: true,
        origin: 'http://127.0.0.1:5173'
    },
    build: {
        outDir: 'assets/dist',
        emptyOutDir: true,
        sourcemap: true,
        cssCodeSplit: false,
        rollupOptions: {
            input: {
                admin: path.resolve(__dirname, 'assets/admin.js'),
            },
            output: {
                entryFileNames: 'admin.js',
                assetFileNames: 'admin.css',
            }
        }
    }
});
