import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time web applications.
 */

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

// Configure Pusher settings from environment variables
window.pusherConfig = {
    key: process.env.MIX_PUSHER_APP_KEY,
    cluster: process.env.MIX_PUSHER_APP_CLUSTER,
    host: process.env.MIX_PUSHER_HOST,
    port: process.env.MIX_PUSHER_PORT,
    scheme: process.env.MIX_PUSHER_SCHEME
};

// Initialize Echo only if Pusher is configured
if (window.pusherConfig.key) {
    window.Echo = new Echo({
        broadcaster: 'pusher',
        key: window.pusherConfig.key,
        cluster: window.pusherConfig.cluster,
        wsHost: window.pusherConfig.host,
        wsPort: window.pusherConfig.port,
        wssPort: window.pusherConfig.port,
        forceTLS: (window.pusherConfig.scheme === 'https'),
        encrypted: true,
        disableStats: true,
        enabledTransports: ['ws', 'wss']
    });
} else {
    console.warn('Pusher configuration not found. Real-time features will be disabled.');
}
