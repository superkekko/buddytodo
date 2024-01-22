// Evento activate
self.addEventListener('activate', event => {
	console.log("Service Worker Avviato");
});

self.addEventListener('notificationclick', function(event) {
  let link = event.notification.data.link;
  event.notification.close();
  // This looks to see if the current is already open and focuses if it is
  event.waitUntil(
    clients
      .matchAll({
        type: "window",
      })
      .then((clientList) => {
        for (const client of clientList) {
          if (client.url === "/"+link && "focus" in client) return client.focus();
        }
        if (clients.openWindow) return clients.openWindow(link);
      }),
  );
});