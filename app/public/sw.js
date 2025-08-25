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
  //sendStoredRequests();
  if (event.request.method === "POST") {
    return new Response(JSON.stringify({ error: "Is Offline" }), {
            status: 500,
            headers: { 'Content-Type': 'application/json' }
          });
    // Only intercept POST requests
    if (!navigator.onLine) {
      // If offline, store the request
      event.respondWith(
        storeRequest(event.request.clone()).then(() => {
          // Respond with a generic message or a specific response if needed
          return new Response(JSON.stringify({ message: "Request stored and will be retried when online" }), {
            headers: { 'Content-Type': 'application/json' }
          });
        }).catch(error => {
          // Handle errors, possibly inform the user that offline functionality might not work
          console.error('Failed to store request:', error);
          return new Response(JSON.stringify({ error: "Failed to store request for later" }), {
            status: 500,
            headers: { 'Content-Type': 'application/json' }
          });
        })
      );
    }
  }
  else if (event.request.method === "GET") {
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

async function storeRequest(request) {
  const db = await openDB();

  try {
    // Read the request body in the correct format.
    const clonedRequest = request.clone();
    const contentType = clonedRequest.headers.get('Content-Type');
    let body;

    if (contentType && contentType.includes('application/json')) {
      body = await clonedRequest.json();
    } else {
      // Assume text for simplicity, but you might need to handle FormData or other types
      body = await clonedRequest.text();
    }

    // Create the request object for IndexedDB
    const requestObj = {
      url: clonedRequest.url,
      headers: [...clonedRequest.headers.entries()],
      method: clonedRequest.method,
      body: JSON.stringify(body), // Storing the body as a stringified JSON
      timestamp: new Date().toISOString(),
    };



    const tx = db.transaction('postRequests', 'readwrite');
    //const store = tx.objectStore('postRequests');
    // Add the request to the object store and wire up events
    tx.objectStore('postRequests').add(requestObj);

    // Wait for the add operation to complete within the transaction lifecycle
    //await addPromise;
    // Wait for the transaction to complete
    await tx.oncomplete;

  } catch (error) {
    console.error('Failed to store request in IndexedDB:', error);
    throw error;
  } finally {
    db.close(); // Close the database connection
  }
}

// self.addEventListener('change', function (event) {
//   if (navigator.onLine) {
//     sendStoredRequests();
//   }
// })


async function sendStoredRequests() {
  const db = await openDB(); // Make sure this openDB function correctly opens your IndexedDB
  const tx = db.transaction('postRequests', 'readonly');
  const store = tx.objectStore('postRequests');

  const cursorRequest = store.openCursor();

  cursorRequest.onsuccess = async (event) => {
    const cursor = event.target.result;

    if (cursor) {
      const storedRequest = cursor.value; // Your stored request object
      const key = cursor.primaryKey; // The key of the stored request
      const headers = new Headers();

      // Assuming headers is an array of arrays like [['Content-Type', 'application/json']]
      storedRequest.headers.forEach(([key, value]) => headers.append(key, value));

      try {
        let response = await fetch(storedRequest.url, {
          method: storedRequest.method,
          headers: headers,
          body: storedRequest.body.slice(1, -1) // Be careful with slicing
        });

        if (response.ok) {
          console.log('Stored request sent successfully', storedRequest);
          console.log('Key of the stored request:', key); // Logging the key
          await deleteStoredRequest(key); // Now passing the key for deletion
          cursor.continue();
        }
      } catch (error) {
        console.error('Failed to resend stored request', storedRequest, error);
        cursor.continue();
      }

      //cursor.continue(key + 1); // Move to the next item in the store
    }
  };

  cursorRequest.onerror = function (event) {
    console.error('Cursor request failed', event.target.errorCode);
  };

  await tx.done; // This will wait for the transaction to complete
}



async function deleteStoredRequest(key) {
  const db = await openDB();
  const tx = db.transaction('postRequests', 'readwrite');

  return new Promise((resolve, reject) => {


    const deleteID = tx.objectStore('postRequests').delete(key);

    deleteID.onerror = function (event) {
      // Generic error handler for all errors targeted at this database's requests
      reject('Database error: ' + event.target.errorCode);
    };

    deleteID.onsuccess = function (event) {
      resolve(event.target.result); // The result is the opened database
    };
  })
}


/*async function deleteStoredRequest(timestamp) {
  const db = await openDB();
  const tx = db.transaction('postRequests', 'readwrite');
  const store = tx.objectStore('postRequests');
  const requestIndex = await store.index('timestamp');
  const requestToDelete = await requestIndex.get(timestamp);
  if (requestToDelete) {
    store.delete(requestToDelete.id);
  }
  return tx.complete;
}*/






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

