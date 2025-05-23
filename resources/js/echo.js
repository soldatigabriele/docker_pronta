import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

// Log current configuration
console.log('Echo Configuration:', {
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT,
    scheme: import.meta.env.VITE_REVERB_SCHEME,
});

const echoConfig = {
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 6001,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 6001,
    forceTLS: false,
    useTLS: false,
    encrypted: false,
    enabledTransports: ['ws'],
    disableStats: true,
    cluster: '',
    authEndpoint: 'https://pronta.test/broadcasting/auth',
    auth: {
        headers: {
            Authorization: `Bearer ${localStorage.getItem('authToken')}`,
            Accept: 'application/json',
        },
    },
};

console.log('Full Echo Config:', echoConfig);

window.Echo = new Echo(echoConfig);

// Wait for Echo to be fully initialized
setTimeout(() => {
    console.log('🔍 Checking Echo connection after initialization...');
    console.log('Echo instance:', window.Echo);
    console.log('Pusher instance:', window.Echo?.connector?.pusher);
    
    if (window.Echo?.connector?.pusher) {
        const pusher = window.Echo.connector.pusher;
        console.log('Pusher connection state:', pusher.connection?.state);
        console.log('Pusher config:', pusher.config);
        
        // Debug WebSocket connection events
        pusher.connection.bind('state_change', function(states) {
            console.log('🔄 Connection state changed:', states.previous + ' -> ' + states.current);
        });
        
        pusher.connection.bind('connected', function() {
            console.log('✅ WebSocket connected successfully!');
        });
        
        pusher.connection.bind('disconnected', function() {
            console.log('❌ WebSocket disconnected');
        });
        
        pusher.connection.bind('failed', function() {
            console.log('❌ WebSocket connection failed');
        });
        
        pusher.connection.bind('error', function(error) {
            console.log('❌ WebSocket error:', error);
        });
        
        pusher.connection.bind('connecting', function() {
            console.log('🔄 WebSocket connecting...');
        });
        
        pusher.connection.bind('unavailable', function() {
            console.log('❌ WebSocket unavailable');
        });
        
        // Check current connection state
        if (pusher.connection.state === 'connected') {
            console.log('✅ Already connected!');
        } else {
            console.log('📡 Current connection state:', pusher.connection.state);
            if (pusher.connection.state === 'initialized') {
                console.log('🔄 Manually triggering connection...');
                pusher.connect();
            }
        }
    } else {
        console.log('❌ Echo or Pusher not properly initialized');
    }
}, 1000);
