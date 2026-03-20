let echoInstance = null;
let echoBootPromise = null;

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
                wsHost: import.meta.env.VITE_REVERB_HOST ?? window.location.hostname,
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
