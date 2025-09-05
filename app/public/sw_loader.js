if ('serviceWorker' in navigator) {
    //console.log("if yes")
    window.addEventListener('load', function () {
        navigator.serviceWorker.register('/sw_lite.js').then(function (registration) {
            // Registration was successful
            //console.log('ServiceWorker registration successful with scope: ', registration.scope);
        }, function (err) {
            // registration failed :(
            console.log('ServiceWorker registration failed: ', err);
        });
    });
}
else {
    console.log("no service worker supported")
}