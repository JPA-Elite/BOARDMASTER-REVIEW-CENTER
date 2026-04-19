import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');
    const port = Number(env.VITE_PORT || 5173);
    const pollInterval = Number(env.VITE_POLL_INTERVAL || 600);
    const hmrHost = env.VITE_HMR_HOST || undefined;
    const backendUrl = env.VITE_BACKEND_URL || 'http://web';
    const appUrl = process.env.APP_URL || env.APP_URL || 'http://boardmasters.local:8080';
    const allowedOrigins = (env.VITE_ALLOWED_ORIGINS || appUrl)
        .split(',')
        .map((origin) => origin.trim())
        .filter(Boolean);

    return {
        plugins: [
            laravel({
                input: 'resources/js/app.jsx',
                refresh: true,
            }),
            react(),
        ],
        optimizeDeps: {
            entries: [
                'resources/js/**/*.js',
                'resources/js/**/*.jsx',
            ],
            include: [
                '@inertiajs/react',
                'lucide-react',
                'react-toastify',
                'recharts',
            ],
        },
        server: {
            host: env.VITE_HOST || '127.0.0.1',
            port,
            strictPort: true,
            watch: {
                usePolling: env.VITE_USE_POLLING === 'true',
                interval: pollInterval,
                ignored: [
                    '**/.git/**',
                    '**/node_modules/**',
                    '**/vendor/**',
                    '**/storage/**',
                    '**/bootstrap/cache/**',
                    '**/public/build/**',
                ],
            },
            warmup: {
                clientFiles: [
                    './resources/js/app.jsx',
                    './resources/js/Pages/Welcome.jsx',
                    './resources/js/Pages/Auth/Login.jsx',
                    './resources/js/Pages/Admin/Dashboard.jsx',
                ],
            },
            cors: {
                origin: allowedOrigins,
            },
            proxy: {
                '^/(?!@vite|@react-refresh|__vite_ping|resources/|node_modules/).*': {
                    target: backendUrl,
                    changeOrigin: true,
                    secure: false,
                },
            },
            hmr: hmrHost
                ? {
                    host: hmrHost,
                    port,
                }
                : undefined,
            origin: env.VITE_DEV_SERVER_URL || (hmrHost ? `http://${hmrHost}:${port}` : undefined),
        },
    };
});
