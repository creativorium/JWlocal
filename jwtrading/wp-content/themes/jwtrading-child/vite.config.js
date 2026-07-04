import { defineConfig } from 'vite';
import { writeFileSync, unlinkSync, existsSync } from 'fs';

const hotFile = 'dist/hot';

export default defineConfig(({ command }) => ({
  build: {
    manifest: true,
    outDir: 'dist',
    rollupOptions: { input: 'src/main.js' },
  },
  server: { cors: true, origin: 'http://localhost:5173' },
  plugins: [
    {
      name: 'wp-hot-file',
      configureServer(server) {
        server.httpServer?.once('listening', () => {
          writeFileSync(hotFile, 'http://localhost:5173');
        });
        const clean = () => { if (existsSync(hotFile)) unlinkSync(hotFile); process.exit(); };
        process.on('SIGINT', clean);
        process.on('SIGTERM', clean);
      },
    },
  ],
}));
