/**
 * Cyborg Push - Client Side JavaScript
 * Handles Service Worker registration and push subscription
 */
(function () {
    'use strict';

    const CyborgPush = {
        config: window.CYBORG_PUSH_CONFIG || {},

        init: function () {
            if (!this.isSupported()) {
                console.log('Cyborg Push: Push notifications not supported');
                return;
            }

            this.registerServiceWorker();
        },

        isSupported: function () {
            return 'serviceWorker' in navigator && 'PushManager' in window;
        },

        registerServiceWorker: async function () {
            try {
                const registration = await navigator.serviceWorker.register(this.config.swPath, {
                    scope: '/'
                });

                console.log('Cyborg Push: Service Worker registered');

                // Check for existing subscription
                const subscription = await registration.pushManager.getSubscription();

                if (subscription) {
                    console.log('Cyborg Push: Already subscribed');
                    this.sendSubscriptionToServer(subscription);
                } else {
                    // Request permission and subscribe
                    this.requestPermission(registration);
                }
            } catch (error) {
                console.error('Cyborg Push: Service Worker registration failed', error);
            }
        },

        requestPermission: async function (registration) {
            const permission = await Notification.requestPermission();

            if (permission === 'granted') {
                this.subscribe(registration);
            } else {
                console.log('Cyborg Push: Notification permission denied');
            }
        },

        subscribe: async function (registration) {
            try {
                const vapidPublicKey = this.urlBase64ToUint8Array(this.config.vapidPublicKey);

                const subscription = await registration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: vapidPublicKey
                });

                console.log('Cyborg Push: Push subscription created');
                this.sendSubscriptionToServer(subscription);
            } catch (error) {
                console.error('Cyborg Push: Failed to subscribe', error);
            }
        },

        sendSubscriptionToServer: function (subscription) {
            const data = subscription.toJSON();

            // Get CSRF token from Perfex CRM
            const csrfName = typeof csrfData !== 'undefined' ? csrfData.token_name : 'csrf_token_name';
            const csrfHash = typeof csrfData !== 'undefined' ? csrfData.hash : '';

            // Build form data with CSRF token
            let formData = this.serialize({
                endpoint: data.endpoint,
                keys: {
                    p256dh: data.keys.p256dh,
                    auth: data.keys.auth
                }
            });

            // Add CSRF token
            if (csrfHash) {
                formData += '&' + csrfName + '=' + encodeURIComponent(csrfHash);
            }

            fetch(this.config.subscribeUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        console.log('Cyborg Push: Subscription saved to server');
                    } else {
                        console.error('Cyborg Push: Failed to save subscription', result.message);
                    }
                })
                .catch(error => {
                    console.error('Cyborg Push: Error sending subscription', error);
                });
        },

        unsubscribe: async function () {
            const registration = await navigator.serviceWorker.ready;
            const subscription = await registration.pushManager.getSubscription();

            if (subscription) {
                await subscription.unsubscribe();

                fetch(this.config.unsubscribeUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'endpoint=' + encodeURIComponent(subscription.endpoint)
                });

                console.log('Cyborg Push: Unsubscribed');
            }
        },

        urlBase64ToUint8Array: function (base64String) {
            const padding = '='.repeat((4 - base64String.length % 4) % 4);
            const base64 = (base64String + padding)
                .replace(/-/g, '+')
                .replace(/_/g, '/');

            const rawData = window.atob(base64);
            const outputArray = new Uint8Array(rawData.length);

            for (let i = 0; i < rawData.length; ++i) {
                outputArray[i] = rawData.charCodeAt(i);
            }

            return outputArray;
        },

        serialize: function (obj, prefix) {
            const str = [];
            for (const p in obj) {
                if (obj.hasOwnProperty(p)) {
                    const k = prefix ? prefix + '[' + p + ']' : p;
                    const v = obj[p];
                    str.push((v !== null && typeof v === 'object') ?
                        this.serialize(v, k) :
                        encodeURIComponent(k) + '=' + encodeURIComponent(v));
                }
            }
            return str.join('&');
        }
    };

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => CyborgPush.init());
    } else {
        CyborgPush.init();
    }

    // Expose globally
    window.CyborgPush = CyborgPush;
})();
