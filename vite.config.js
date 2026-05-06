import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

const devHost = process.env.VITE_DEV_SERVER_HOST || '127.0.0.1';
const devPort = Number(process.env.VITE_DEV_SERVER_PORT || 5173);

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    server: {
        host: devHost,
        port: devPort,
        strictPort: true,
        hmr: {
            host: devHost,
            port: devPort,
        },
    },
    build: {
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (!id.includes('node_modules')) {
                        return;
                    }

                    if (id.includes('chart.js') || id.includes('vue-chartjs')) {
                        return 'charts-vendor';
                    }

                    if (id.includes('laravel-echo') || id.includes('pusher-js')) {
                        return 'realtime-vendor';
                    }

                    if (id.includes('@inertiajs') || id.includes('/vue/') || id.includes('\\vue\\') || id.includes('@vue')) {
                        return 'framework-vendor';
                    }

                    if (id.includes('axios')) {
                        return 'http-vendor';
                    }

                    if (id.includes('ziggy')) {
                        return 'ziggy-vendor';
                    }
                },
            },
        },
    },
});
