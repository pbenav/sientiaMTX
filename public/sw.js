self.addEventListener('push', function (event) {
    if (!(self.Notification && self.Notification.permission === 'granted')) {
        return;
    }

    if (!event.data) return;

    const data = event.data.json();
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
