// Service Worker for Push Notifications
const CACHE_NAME = 'project-manager-v1';
const urlsToCache = [
    '/',
    '/dashboard',
    '/favicon.ico'
];

// Install event
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => cache.addAll(urlsToCache))
    );
    self.skipWaiting();
});

// Activate event
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    if (cacheName !== CACHE_NAME) {
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
    self.clients.claim();
});

// Fetch event (optional - for caching)
self.addEventListener('fetch', (event) => {
    event.respondWith(
        caches.match(event.request)
            .then((response) => {
                // Return cached version or fetch from network
                return response || fetch(event.request);
            })
    );
});

// Push event - handle incoming push notifications
self.addEventListener('push', (event) => {
    console.log('Push event received:', event);
    
    if (!event.data) {
        console.log('Push event but no data');
        return;
    }

    let notificationData;
    try {
        notificationData = event.data.json();
    } catch (e) {
        console.log('Error parsing push data:', e);
        return;
    }

    const title = notificationData.title || 'Proje Yönetimi';
    const options = {
        body: notificationData.body || 'Yeni bildirim',
        icon: notificationData.icon || '/favicon.ico',
        badge: notificationData.badge || '/favicon.ico',
        tag: notificationData.tag || 'general',
        data: notificationData.data || {},
        actions: notificationData.actions || [],
        requireInteraction: notificationData.requireInteraction || false,
        vibrate: [200, 100, 200], // Vibration pattern for mobile
        timestamp: Date.now()
    };

    event.waitUntil(
        self.registration.showNotification(title, options)
    );
});

// Notification click event
self.addEventListener('notificationclick', (event) => {
    console.log('Notification clicked:', event);
    
    event.notification.close();

    // Handle action buttons
    if (event.action) {
        switch (event.action) {
            case 'view':
                // Handle view action
                handleNotificationClick(event);
                break;
            case 'dismiss':
                // Just close the notification
                return;
            default:
                handleNotificationClick(event);
        }
    } else {
        // Handle main notification click
        handleNotificationClick(event);
    }
});

// Notification close event
self.addEventListener('notificationclose', (event) => {
    console.log('Notification closed:', event);
    
    // Track notification dismissal if needed
    if (event.notification.data && event.notification.data.type) {
        // Could send analytics about notification dismissal
    }
});

// Helper function to handle notification clicks
function handleNotificationClick(event) {
    const notificationData = event.notification.data || {};
    const url = notificationData.url || '/dashboard';
    
    event.waitUntil(
        clients.matchAll({
            type: 'window',
            includeUncontrolled: true
        }).then((clientList) => {
            // Check if there's already a window/tab open with the target URL
            for (let i = 0; i < clientList.length; i++) {
                const client = clientList[i];
                if (client.url.includes(url) && 'focus' in client) {
                    return client.focus();
                }
            }
            
            // If no existing window is found, open a new one
            if (clients.openWindow) {
                return clients.openWindow(url);
            }
        })
    );
}

// Background sync event (for future use)
self.addEventListener('sync', (event) => {
    console.log('Background sync triggered:', event.tag);
    
    if (event.tag === 'background-sync') {
        event.waitUntil(
            // Handle background sync tasks
            Promise.resolve()
        );
    }
});

// Message event for communication with main thread
self.addEventListener('message', (event) => {
    console.log('Service worker received message:', event.data);
    
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});

// Helper function to send message to all clients
function sendMessageToAllClients(message) {
    return clients.matchAll({
        includeUncontrolled: true,
        type: 'window'
    }).then((clientList) => {
        clientList.forEach((client) => {
            client.postMessage(message);
        });
    });
}

// Periodic sync for future use (when supported)
if ('periodicSync' in self.registration) {
    self.addEventListener('periodicsync', (event) => {
        if (event.tag === 'sync-tasks') {
            event.waitUntil(
                // Periodic sync tasks
                Promise.resolve()
            );
        }
    });
}