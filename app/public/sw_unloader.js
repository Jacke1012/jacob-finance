if ('serviceWorker' in navigator) {
    navigator.serviceWorker.getRegistrations().then(function (registrations) {
        for (let registration of registrations) {
            registration.unregister().then(function (boolean) {
                if (boolean) {
                    console.log('Service worker unregistered successfully');
                }
            });
        }
    }).catch(function (error) {
        console.log('Error during service worker deregistration:', error);
    });
}
