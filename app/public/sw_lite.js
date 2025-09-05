// Define the cache name
var CACHE_NAME = 'expenses_cache';

// Specify the resources you want to cache
var urlsToCache = [
  '/',
  '/index.html',
  '/index.js',
  '/sw_lite.js',
  'https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css',
  'https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js'
];

// Install event - caches resources
self.addEventListener('install', function (event) {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(function (cache) {
        //console.log('Opened cache');
        return cache.addAll(urlsToCache);
      })
  );
});

