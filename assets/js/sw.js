/**
 * Cyborg Push - Service Worker
 * Handles push notifications in the background
 */

self.addEventListener('install', function (event) {
    console.log('Cyborg Push SW: Installing...');
    self.skipWaiting();
});

self.addEventListener('activate', function (event) {
    console.log('Cyborg Push SW: Activated');
    event.waitUntil(clients.claim());
});

self.addEventListener('push', function (event) {
    console.log('Cyborg Push SW: Push received');

    let data = {
        title: 'Nova Notificação',
        body: 'Você tem uma nova notificação',
        icon: '/assets/images/logo.png',
        badge: '/assets/images/badge.png',
        data: {}
    };

    if (event.data) {
        try {
            data = Object.assign(data, event.data.json());
        } catch (e) {
            data.body = event.data.text();
        }
    }

    const options = {
        body: data.body,
        icon: data.icon,
        badge: data.badge,
        tag: data.tag || 'cyborg-push-notification',
        data: data.data,
        vibrate: [100, 50, 100],
        requireInteraction: false,
        actions: data.actions || []
    };

    event.waitUntil(
        self.registration.showNotification(data.title, options)
    );
});

self.addEventListener('notificationclick', function (event) {
    console.log('Cyborg Push SW: Notification clicked');

    event.notification.close();

    const urlToOpen = event.notification.data && event.notification.data.link
        ? event.notification.data.link
        : '/';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true })
            .then(function (clientList) {
                // Check if there's already a window open
                for (let i = 0; i < clientList.length; i++) {
                    const client = clientList[i];
                    if (client.url === urlToOpen && 'focus' in client) {
                        return client.focus();
                    }
                }
                // Open new window
                if (clients.openWindow) {
                    return clients.openWindow(urlToOpen);
                }
            })
    );
});

self.addEventListener('notificationclose', function (event) {
    console.log('Cyborg Push SW: Notification closed');
});

self.addEventListener('pushsubscriptionchange', function (event) {
    console.log('Cyborg Push SW: Subscription changed');
    // Handle subscription change - re-subscribe
});
