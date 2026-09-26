// public/sw.js
//
// Omniscient Service Worker — combines offline support + push notifications.

// ================================================================
// ✅ Offline support — Tier 5 #30
// ================================================================

const OFFLINE_CACHE = 'omniscient-offline-v1';
const OFFLINE_URL = '/offline.html';

// ================================================================
// Lifecycle
// ================================================================

self.addEventListener('install', (event) => {
    console.log('[SW] Installing...');

    event.waitUntil(
        caches.open(OFFLINE_CACHE).then((cache) => {
            // Pre-cache the offline fallback page
            return cache.add(OFFLINE_URL);
        })
    );

    // Take over as soon as the old SW is released
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    console.log('[SW] Activating...');

    // Clean up old caches (only keep the current offline cache)
    event.waitUntil(
        caches.keys().then((names) => {
            return Promise.all(
                names
                    .filter((name) => name.startsWith('omniscient-') && name !== OFFLINE_CACHE)
                    .map((name) => caches.delete(name))
            );
        })
    );

    // Take control of all open tabs
    self.clients.claim();
});

// ================================================================
// Offline fallback (fetch)
// ================================================================

self.addEventListener('fetch', (event) => {
    const { request } = event;

    // Only intercept top-level HTML navigations.
    // Assets, APIs, images all go through the network normally.
    if (request.mode !== 'navigate') return;

    // Same-origin only
    const url = new URL(request.url);
    if (url.origin !== self.location.origin) return;

    event.respondWith(
        fetch(request).catch(() =>
            caches.match(OFFLINE_URL).then(
                (cached) =>
                    cached ||
                    new Response('Offline', {
                        status: 503,
                        headers: { 'Content-Type': 'text/plain' },
                    })
            )
        )
    );
});

// ================================================================
// Message channel
// ================================================================

self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});

// ================================================================
// Push notifications
// ================================================================

self.addEventListener('push', (event) => {
    console.log('[SW] Push received');

    if (!(self.Notification && self.Notification.permission === 'granted')) {
        return;
    }

    let data = {};
    if (event.data) {
        try {
            data = event.data.json();
        } catch (e) {
            data = {
                title: 'New Notification',
                body: event.data.text(),
                icon: '/images/icon-192.png',
                badge: '/images/icon-72.png',
                url: '/',
            };
        }
    }

    const options = {
        body: data.body || 'You have a new notification',
        icon: data.icon || '/images/icon-192.png',
        badge: data.badge || '/images/icon-72.png',
        vibrate: [100, 50, 100],
        data: {
            url: data.url || '/',
            dateOfArrival: Date.now(),
        },
        actions: data.actions || [
            { action: 'open', title: 'Open' },
            { action: 'dismiss', title: 'Dismiss' },
        ],
    };

    event.waitUntil(
        self.registration.showNotification(data.title || 'Omniscient Directory', options)
    );
});

// ================================================================
// Notification click
// ================================================================

self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const urlToOpen = event.notification.data?.url || '/';

    event.waitUntil(
        clients
            .matchAll({
                type: 'window',
                includeUncontrolled: true,
            })
            .then((clientList) => {
                // Focus an existing tab at that URL
                for (let i = 0; i < clientList.length; i++) {
                    const client = clientList[i];
                    if (client.url === urlToOpen && 'focus' in client) {
                        return client.focus();
                    }
                }
                // Otherwise open a new one
                if (clients.openWindow) {
                    return clients.openWindow(urlToOpen);
                }
            })
    );
});