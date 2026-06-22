// sw_lite.js

// Static cache:
// Stores app files/assets that are known ahead of time.
// Because static requests below use a cache-first strategy, bump this name
// when cached app files change and you want old copies deleted.
const STATIC_CACHE = 'expenses-static-v20';

// Files saved immediately when the service worker installs.
// These are available later even if the network is unavailable.
const URLS_TO_CACHE = [
  '/',
  '/index.php',
  '/index.js',
  '/assets/bootstrap.min.css',
  '/assets/jquery.min.js'
];

self.addEventListener('install', (event) => {
  event.waitUntil((async () => {
    // Open/create the static cache version above.
    const cache = await caches.open(STATIC_CACHE);

    // Download and store every file in URLS_TO_CACHE.
    // If any of these requests fail, the service worker install can fail too.
    await cache.addAll(URLS_TO_CACHE);

    // Activate this new service worker as soon as it finishes installing,
    // instead of waiting for all old tabs to close.
    self.skipWaiting();
  })());
});

self.addEventListener('activate', (event) => {
  event.waitUntil((async () => {
    // Look at all Cache Storage caches for this site.
    const keys = await caches.keys();

    // Delete old cache versions and any previous runtime cache, but keep the
    // current static cache.
    // This is why bumping STATIC_CACHE from v5 to v6 clears old static files.
    await Promise.all(
      keys
        .filter(k => k !== STATIC_CACHE)
        .map(k => caches.delete(k))
    );

    // Take control of open app pages immediately after activation.
    await self.clients.claim();
  })());
});

self.addEventListener('fetch', (event) => {
  const { request } = event;

  // Page navigation requests, like loading or refreshing the app.
  // Strategy: network-first.
  // Online: load fresh HTML/PHP from the server.
  // Offline: fall back to the cached app shell.
  if (request.mode === 'navigate') {
    event.respondWith((async () => {
      try {
        const fresh = await fetch(request);
        return fresh;
      } catch {
        const cachedShell = await caches.match('/index.php');
        return cachedShell || new Response('Offline', { status: 503 });
      }
    })());
    return;
  }

  // Only cache/read GET requests. POST/PUT/DELETE should go straight through.
  if (request.method !== 'GET') return;

  const url = new URL(request.url);

  // Only handle this app's own requests and these two approved CDN origins.
  // Everything else is left to the browser normally.
  const sameOrigin = url.origin === location.origin;
  const allowedCdn = /^(https:\/\/maxcdn\.bootstrapcdn\.com|https:\/\/ajax\.googleapis\.com)$/i.test(url.origin);
  if (!sameOrigin && !allowedCdn) return;

  // Treat same-origin /php/* URLs as API calls.
  const isApi = sameOrigin && url.pathname.startsWith('/php/');

  // Treat local assets and common static file extensions as static files.
  // These use the static cache below.
  const wantsStatic = (sameOrigin && (
      url.pathname === '/' ||
      url.pathname.startsWith('/assets/') ||
      /\.(html|js|css|png|jpg|jpeg|svg|woff2)$/.test(url.pathname)
    )) || allowedCdn;

  if (wantsStatic) {
    // Static file strategy: cache-first.
    // If a cached copy exists, return it without asking nginx/the server.
    // If no cached copy exists, fetch it from the network and store it.
    event.respondWith((async () => {
      // The full request is used as the cache key, including query strings.
      // Example: /index.js?v=1 and /index.js?v=2 are different cached files.
      const cached = await caches.match(request);
      if (cached) return cached;

      const response = await fetch(request, { credentials: 'same-origin' });

      // Store successful same-origin responses.
      // Opaque responses are allowed for cross-origin CDN requests, even though
      // the service worker cannot inspect their status/headers.
      if (response.ok || response.type === 'opaque') {
        const cache = await caches.open(STATIC_CACHE);
        cache.put(request, response.clone());
      }
      return response;
    })());
    return;
  }

  if (isApi) {
    // API strategy: network-first.
    // API responses are intentionally not cached.
    event.respondWith((async () => {
      try {
        return await fetch(request, { credentials: 'include' });
      } catch {
        return new Response('Offline', { status: 503 });
      }
    })());
  }
});
