/* Bendaharaku Service Worker — Web Push notifications only.
 * Senga cache logic di sini; asset Vite ditangani build biasa. */
self.addEventListener('install', () => {
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(self.clients.claim());
});

self.addEventListener('push', (event) => {
  let data = { title: 'Bendaharaku', body: '', url: '/' };
  try {
    const payload = event.data ? event.data.json() : {};
    data = { title: payload.title || data.title, body: payload.body || '', url: payload.url || '/', tag: payload.tag || null, data: payload.data || {} };
  } catch (e) {
    data.body = event.data ? event.data.text() : '';
  }

  const options = {
    body: data.body,
    data: { url: data.url },
    tag: data.tag || 'bendaharaku',
    renotify: true,
    icon: '/favicon.ico',
    badge: '/favicon.ico',
  };

  event.waitUntil(self.registration.showNotification(data.title, options));
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();

  const url = (event.notification.data && event.notification.data.url) || '/';

  event.waitUntil(
    self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clients) => {
      for (const client of clients) {
        if (new URL(client.url).pathname === new URL(url, self.location.origin).pathname) {
          return client.focus();
        }
      }
      return self.clients.openWindow(url);
    }),
  );
});
