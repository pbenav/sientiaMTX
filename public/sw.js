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

const CACHE_NAME = 'sientiamtx-cdn-cache-v1';
const CDN_DOMAINS = [
    'fonts.googleapis.com',
    'fonts.gstatic.com',
    'cdn.tailwindcss.com',
    'cdn.jsdelivr.net',
    'unpkg.com',
    'cdnjs.cloudflare.com'
];

self.addEventListener('install', event => {
    self.skipWaiting();
});

self.addEventListener('activate', event => {
    event.waitUntil(self.clients.claim());
});

self.addEventListener('fetch', event => {
    const url = new URL(event.request.url);
    
    // Check if the request is for a CDN domain
    if (CDN_DOMAINS.some(domain => url.hostname === domain)) {
        event.respondWith(
            caches.match(event.request).then(cachedResponse => {
                if (cachedResponse) {
                    return cachedResponse;
                }
                
                return fetch(event.request).then(networkResponse => {
                    // Check if we received a valid response
                    if (!networkResponse || networkResponse.status !== 200 || networkResponse.type !== 'basic' && networkResponse.type !== 'cors') {
                        return networkResponse;
                    }

                    // Clone the response because the stream can only be read once
                    const responseToCache = networkResponse.clone();
                    
                    caches.open(CACHE_NAME).then(cache => {
                        cache.put(event.request, responseToCache);
                    });

                    return networkResponse;
                }).catch(error => {
                    console.error('[Service Worker] CDN fetch failed:', error);
                    // You could potentially return a fallback here
                    throw error;
                });
            })
        );
    }
});
