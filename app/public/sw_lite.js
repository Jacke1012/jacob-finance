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



function openDB() {
  return new Promise((resolve, reject) => {
    // Check for browser support of IndexedDB
    if (!('indexedDB' in self)) {
      reject('This browser doesn\'t support IndexedDB');
      return;
    }

    // Open (or create) the database
    const request = indexedDB.open('postRequestsDB', 1); // '1' is the version of the database

    // Create the schema
    request.onupgradeneeded = function (event) {
      const db = event.target.result;

      // Create an object store for this database if it doesn't already exist
      if (!db.objectStoreNames.contains('postRequests')) {
        db.createObjectStore('postRequests', { autoIncrement: true });
      }
    };

    request.onerror = function (event) {
      // Generic error handler for all errors targeted at this database's requests
      reject('Database error: ' + event.target.errorCode);
    };

    request.onsuccess = function (event) {
      resolve(event.target.result); // The result is the opened database
    };
  });
}

