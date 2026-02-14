import { defineConfig } from 'vite';
import tsconfigPaths from 'vite-tsconfig-paths';
import react from '@vitejs/plugin-react-swc';
import mkcert from 'vite-plugin-mkcert';

// https://vitejs.dev/config/
export default defineConfig({
  base: '/miniapp-one',
  css: {
    preprocessorOptions: {
      scss: {
        api: 'modern',
      },
    },
  },
  plugins: [
    // Allows using React dev server along with building a React application with Vite.
    // https://npmjs.com/package/@vitejs/plugin-react-swc
    react(),
    // Allows using the compilerOptions.paths property in tsconfig.json.
    // https://www.npmjs.com/package/vite-tsconfig-paths
    tsconfigPaths(),
    // Creates a custom SSL certificate valid for the local machine.
    // Using this plugin requires admin rights on the first dev-mode launch.
    // https://www.npmjs.com/package/vite-plugin-mkcert
    process.env.HTTPS && mkcert(),
  ],
  build: {
    target: 'esnext',
    // Enable CSS code splitting so lazy pages get their own CSS
    cssCodeSplit: true,
    // Minify with esbuild (default, fast) + remove console.log in production
    minify: 'esbuild',
    rollupOptions: {
      output: {
        // Aggressive chunk splitting for optimal caching & parallel loading
        manualChunks(id) {
          // React core — almost never changes, highly cacheable
          if (id.includes('node_modules/react-dom') || id.includes('node_modules/react/')) {
            return 'react-vendor';
          }
          // React Router
          if (id.includes('node_modules/react-router')) {
            return 'router';
          }
          // Telegram SDK — separate from UI lib for better caching
          if (id.includes('node_modules/@telegram-apps/sdk')) {
            return 'tma-sdk';
          }
          // Telegram UI components (heaviest dep)
          if (id.includes('node_modules/@telegram-apps/telegram-ui')) {
            return 'tma-ui';
          }
        },
      },
    },
    // Increase the asset inline limit slightly for small SVGs/icons
    assetsInlineLimit: 8192,
  },
  // Pre-bundle heavy deps for faster dev cold start
  optimizeDeps: {
    include: [
      'react',
      'react-dom',
      '@telegram-apps/sdk-react',
      '@telegram-apps/telegram-ui',
    ],
  },
  publicDir: './public',
  server: {
    // Exposes your dev server and makes it accessible for the devices in the same network.
    host: true,
  },
});
