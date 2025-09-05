// sw_lite.js

const STATIC_CACHE = 'expenses-static-v3';
const RUNTIME_CACHE = 'expenses-runtime-v1'; // ★ separate runtime cache

const URLS_TO_CACHE = [
  '/',
  '/index.html',
  '/index.js',
  '/sw_lite.js',
  'https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css',
  'https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js'
];

self.addEventListener('install', (event) => {
  event.waitUntil((async () => {
    const cache = await caches.open(STATIC_CACHE);
    // Consider moving the CDN URLs out of addAll if install failures bother you. ★
    await cache.addAll(URLS_TO_CACHE);
    self.skipWaiting();
  })());
});

self.addEventListener('activate', (event) => {
  event.waitUntil((async () => {
    const keys = await caches.keys();
    await Promise.all(
      keys
        .filter(k => k !== STATIC_CACHE && k !== RUNTIME_CACHE) // ★ keep runtime too
        .map(k => caches.delete(k))
    );
    await self.clients.claim();
  })());
});

self.addEventListener('fetch', (event) => {
  const { request } = event;

  // ★ 1) Handle navigations (HTML shell strategy)
  if (request.mode === 'navigate') {
    event.respondWith((async () => {
      try {
        const fresh = await fetch(request);
        // Optionally, put a copy of '/' into STATIC_CACHE during install.
        return fresh;
      } catch {
        // Serve cached shell when offline
        const cachedShell = await caches.match('/index.html'); // or '/'
        return cachedShell || new Response('Offline', { status: 503 });
      }
    })());
    return;
  }

  if (request.method !== 'GET') return;

  const url = new URL(request.url);
  const sameOrigin = url.origin === location.origin;
  const allowedCdn = /^(https:\/\/maxcdn\.bootstrapcdn\.com|https:\/\/ajax\.googleapis\.com)$/i.test(url.origin);
  if (!sameOrigin && !allowedCdn) return;

  const isApi = sameOrigin && url.pathname.startsWith('/php/');

  const wantsStatic = (sameOrigin && (
      url.pathname === '/' ||
      url.pathname.startsWith('/assets/') ||
      /\.(html|js|css|png|jpg|jpeg|svg|woff2)$/.test(url.pathname)
    )) || allowedCdn;

  if (wantsStatic) {
    // Cache-first for static assets
    event.respondWith((async () => {
      // ★ 2) Do NOT ignore search; versioned files like app.js?v=2 should be distinct
      const cached = await caches.match(request);
      if (cached) return cached;

      const response = await fetch(request, { credentials: 'same-origin' });
      // For cross-origin, response.type may be "opaque". You can still cache it, but be aware you can't read headers. ★
      if (response.ok || response.type === 'opaque') {
        const cache = await caches.open(STATIC_CACHE);
        cache.put(request, response.clone());
      }
      return response;
    })());
    return;
  }

  if (isApi) {
    // Network-first for API; runtime cache
    event.respondWith((async () => {
      const runtime = await caches.open(RUNTIME_CACHE);
      try {
        const response = await fetch(request, { credentials: 'include' });
        const cc = response.headers.get('Cache-Control') || '';
        const setCookie = response.headers.has('Set-Cookie');
        const hasAuth = request.headers.has('Authorization');

        const cacheable = response.ok &&
          !setCookie &&
          !hasAuth &&
          !/(no-store|private)/i.test(cc);

        if (cacheable) {
          runtime.put(request, response.clone());
        }
        return response;
      } catch {
        const cached = await runtime.match(request);
        if (cached) return cached;
        // As a last resort serve the shell
        const shell = await caches.match('/index.html');
        return shell || new Response('Offline', { status: 503 });
      }
    })());
  }
});
