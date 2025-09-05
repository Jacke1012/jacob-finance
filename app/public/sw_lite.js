// Define the cache name
var CACHE_NAME = 'expenses_cache';

// Specify the resources you want to cache
var urlsToCache = [
  '/',
  '/index.html',
  '/index.js',
  '/sw.js',
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


self.addEventListener('fetch', function (event) {
  if (event.request.method === "GET") {
    event.respondWith(
      fetch(event.request).then(function (response) {
        // Check if we received a valid response
        if (!response || response.status !== 200 || response.type !== 'basic') {
          return response;
        }

        // IMPORTANT: Clone the response. A response is a stream and because we want the browser to consume the response
        // as well as the cache consuming the response, we need to clone it so we have two streams.
        var responseToCache = response.clone();

        caches.open(CACHE_NAME)
          .then(function (cache) {
            cache.put(event.request, responseToCache);
          });

        return response;
      }).catch(function () {
        let url = new URL(event.request.url);
        if (url.pathname === "/php/currentTime.php") {
          //console.log(event.request.url)
          let date_now = new Date(Date.now());
          //console.log(date_now.toLocaleString("sv-SE"))

          const my_date_options = {
            year: "numeric",
            month: "numeric",
            day: "numeric",
            hour: "numeric",
            minute: "numeric",
          };


          //console.log(JSON.stringify(date_now))
          let myjson = { "currentTime": date_now.toLocaleString("sv-SE", my_date_options) };
          let myHeaders = new Headers();
          myHeaders.append("Content-Type", "text/html; charset=UTF-8")
          let myOptions = { status: 200, headers: myHeaders };

          let res = new Response(JSON.stringify(myjson), myOptions)
          return res;
        }
        else {
          // If the network request failed, try to get it from the cache.
          return caches.match(event.request)
            .then(function (response) {
              // If we found a match in the cache, return it. Otherwise, if it was a navigation request, return the offline page.
              if (response) {
                return response;
              } else if (event.request.mode === 'navigate') {
                return caches.match('/');
              }
            });
        }


      })
    );
  }
});


// Activate event - cleans up old caches
self.addEventListener('activate', function (event) {
  var cacheWhitelist = [CACHE_NAME];
  event.waitUntil(
    caches.keys().then(function (cacheNames) {
      return Promise.all(
        cacheNames.map(function (cacheName) {
          if (cacheWhitelist.indexOf(cacheName) === -1) {
            return caches.delete(cacheName);
          }
        })
      );
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

