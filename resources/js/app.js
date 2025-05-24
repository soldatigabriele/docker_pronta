import './bootstrap';

import './../css/app.scss';
import { createApp } from 'vue';
import App from './App.vue';
import router from './router';

// PWA Service Worker Registration
import { registerSW } from 'virtual:pwa-register';

const updateSW = registerSW({
    onNeedRefresh() {
        console.log('New content available, please refresh the page.');
        // You can show a notification to the user here
        // For now, we'll auto-update
        updateSW(true);
    },
    onOfflineReady() {
        console.log('App ready to work offline');
    },
    onRegistered(r) {
        console.log('SW Registered: ' + r);
    },
    onRegisterError(error) {
        console.log('SW registration error', error);
    }
});

createApp(App).use(router).mount('#app');

