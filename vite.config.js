import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

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
        host: '127.0.0.1',
        port: 5173,
        strictPort: true,
        hmr: {
            host: '127.0.0.1',
            port: 5173,
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
