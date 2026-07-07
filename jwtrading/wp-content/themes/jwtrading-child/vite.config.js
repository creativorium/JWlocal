import { defineConfig } from 'vite';
import { writeFileSync, unlinkSync, existsSync, cpSync } from 'fs';

const hotFile = 'dist/hot';

// The compiled CSS references fonts/images with paths relative to dist/assets/
// (e.g. `url(../fonts/space-grotesk-var.woff2)` → dist/fonts/…, `../img/…` →
// dist/img/…). Vite leaves those URLs untouched (they live outside its module
// graph), so the referenced files must physically exist under dist/. Copy the
// static asset folders into dist after each build so fonts + the square-grid
// pattern actually load in production.
const staticCopies = [
  ['src/fonts', 'dist/fonts'],
  ['src/img', 'dist/img'],
];

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
      name: 'wp-copy-static-assets',
      closeBundle() {
        for (const [from, to] of staticCopies) {
          if (existsSync(from)) cpSync(from, to, { recursive: true });
        }
      },
    },
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
