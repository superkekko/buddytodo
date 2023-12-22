self.addEventListener('activate', event => {
	console.log("Service Worker Avviato");
});

// Service Worker fetch event
self.addEventListener('fetch', (event) => {
	event.respondWith(
		caches.match(event.request).then((response) => {
			// Check if the request is in the cache
			return response || fetch(event.request);
		})
	);
});