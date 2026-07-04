import { defineConfig } from 'vite';
import { writeFileSync, unlinkSync, existsSync } from 'fs';

const hotFile = 'dist/hot';

export default defineConfig({
  build: {
    manifest: true,
    outDir: 'dist',
    rollupOptions: {
      input: ['src/main.js', 'src/editor.jsx'],
    },
  },
  css: {
    preprocessorOptions: {
      scss: { api: 'modern' },
    },
  },
  esbuild: {
    // Editor JSX compiles straight to WordPress globals — no React bundle.
    jsxFactory: 'wp.element.createElement',
    jsxFragment: 'wp.element.Fragment',
  },
  server: { cors: true, origin: 'http://localhost:5173' },
  plugins: [
    {
      name: 'wp-hot-file',
      configureServer(server) {
        server.httpServer?.once('listening', () => {
          writeFileSync(hotFile, 'http://localhost:5173');
        });
        const clean = () => {
          if (existsSync(hotFile)) unlinkSync(hotFile);
          process.exit();
        };
        process.on('SIGINT', clean);
        process.on('SIGTERM', clean);
      },
    },
  ],
});
