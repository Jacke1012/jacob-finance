// sw_lite.js

// Version your cache so updates are easy to roll out.
const STATIC_CACHE = 'expenses-static-v3';

// Only public, versioned assets here. Prefer self-hosting instead of CDNs.
const URLS_TO_CACHE = [
  '/',
  '/index.html',
  '/index.js',
  '/sw_lite.js',
  // If you *must* cache CDNs, pin exact versions and be aware: an outage blocks install.
  'https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css',
  'https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js'
];

// ---- Install: pre-cache static assets ----
self.addEventListener('install', (event) => {
  event.waitUntil((async () => {
    const cache = await caches.open(STATIC_CACHE);
    // If any request fails, addAll rejects and install fails. That's usually OK for app integrity,
    // but if you want to be resilient to CDN blips, fetch & put each one in a try/catch.
    await cache.addAll(URLS_TO_CACHE);
    self.skipWaiting();
  })());
});

// ---- Activate: clean old caches ----
self.addEventListener('activate', (event) => {
  event.waitUntil((async () => {
    const keys = await caches.keys();
    await Promise.all(
      keys
        .filter((k) => k !== STATIC_CACHE)
        .map((k) => caches.delete(k))
    );
    await self.clients.claim();
  })());
});

// ---- Fetch: cache-first for static, network-first for HTML and API ----
self.addEventListener('fetch', (event) => {
  const { request } = event;
  if (request.method !== 'GET') return;

  const url = new URL(request.url);

  // Only handle same-origin or explicitly allowed CDNs
  const sameOrigin = url.origin === location.origin;
  const allowedCdn = /^(https:\/\/maxcdn\.bootstrapcdn\.com|https:\/\/ajax\.googleapis\.com)$/i.test(url.origin);
  if (!sameOrigin && !allowedCdn) return;

  // Don’t cache authenticated or sensitive responses (defense in depth)
  const isApi = sameOrigin && url.pathname.startsWith('/php/');
  const wantsStatic = sameOrigin && (
    url.pathname === '/' ||
    url.pathname.startsWith('/assets/') ||
    /\.(html|js|css|png|jpg|jpeg|svg|woff2)$/.test(url.pathname)
  ) || allowedCdn;

  if (wantsStatic) {
    // Cache-first for static assets
    event.respondWith((async () => {
      const cached = await caches.match(request, { ignoreSearch: true });
      if (cached) return cached;

      const response = await fetch(request, { credentials: 'same-origin' });
      if (response.ok && response.type !== 'opaque') {
        const cache = await caches.open(STATIC_CACHE);
        cache.put(request, response.clone());
      }
      return response;
    })());
    return;
  }

  if (isApi) {
    // Network-first for APIs; skip caching if clearly sensitive
    event.respondWith((async () => {
      try {
        const response = await fetch(request, { credentials: 'include' });
        // Respect server cache headers and avoid caching private data
        const cc = response.headers.get('Cache-Control') || '';
        const setCookie = response.headers.has('Set-Cookie');
        const hasAuth = request.headers.has('Authorization');

        const cacheable = response.ok &&
          !setCookie &&
          !hasAuth &&
          !/(no-store|private)/i.test(cc);

        if (cacheable) {
          const cache = await caches.open(STATIC_CACHE);
          cache.put(request, response.clone());
        }
        return response;
      } catch {
        // Offline fallback from cache if present
        const cached = await caches.match(request);
        if (cached) return cached;
        // Fallback to a shell (optional): return cached '/' so app can render an offline UI
        const shell = await caches.match('/');
        return shell || new Response('Offline', { status: 503 });
      }
    })());
  }
});
