self.addEventListener('push', function (event) {
    console.log('[Service Worker] Push Received.');
    
    if (!(self.Notification && self.Notification.permission === 'granted')) {
        console.warn('[Service Worker] Notification permission not granted.');
        return;
    }

    if (!event.data) {
        console.warn('[Service Worker] Push event but no data.');
        return;
    }

    const data = event.data.json();
    console.log('[Service Worker] Push Data:', data);

    const title = data.title || 'SientiaMTX';
    const body = data.body || 'Nueva notificación';
    const icon = data.icon || '/images/logo-icon.png';
    const url = data.url || '/';

    event.waitUntil(
        self.registration.showNotification(title, {
            body: body,
            icon: icon,
            data: { url: url }
        })
    );
});

self.addEventListener('notificationclick', function (event) {
    event.notification.close();
    event.waitUntil(
        clients.openWindow(event.notification.data.url)
    );
});
