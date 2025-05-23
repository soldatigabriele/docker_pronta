import Pusher from 'pusher-js';

Pusher.logToConsole = true;

var pusher = new Pusher(import.meta.env.VITE_REVERB_APP_KEY, {
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
});

var channel = pusher.subscribe('my-channel');
channel.bind('my-event', function (data) {
    app.messages.push(JSON.stringify(data));
});
