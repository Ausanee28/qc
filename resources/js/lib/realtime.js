let echoInstance = null;
let echoBootPromise = null;

const normalizeReverbHost = (host) => {
    const normalized = String(host ?? '').trim();
    if (normalized.toLowerCase() === 'localhost') {
        return '127.0.0.1';
    }

    return normalized || window.location.hostname;
};

export const getEcho = async () => {
    if (echoInstance) {
        return echoInstance;
    }

    const reverbKey = import.meta.env.VITE_REVERB_APP_KEY;
    if (!reverbKey) {
        return null;
    }

    if (!echoBootPromise) {
        echoBootPromise = Promise.all([
            import('laravel-echo'),
            import('pusher-js'),
        ]).then(([echoModule, pusherModule]) => {
            const Echo = echoModule.default;
            const Pusher = pusherModule.default;

            window.Pusher = Pusher;

            echoInstance = new Echo({
                broadcaster: 'reverb',
                key: reverbKey,
                wsHost: normalizeReverbHost(import.meta.env.VITE_REVERB_HOST),
                wsPort: Number(import.meta.env.VITE_REVERB_PORT ?? 8080),
                wssPort: Number(import.meta.env.VITE_REVERB_PORT ?? 8080),
                forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'http') === 'https',
                enabledTransports: ['ws', 'wss'],
            });

            window.Echo = echoInstance;
            return echoInstance;
        });
    }

    return echoBootPromise;
};
