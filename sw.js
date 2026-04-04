const CACHE_VERSION = 'v6';
const STATIC_CACHE = 'feuerwehr-app-static-' + CACHE_VERSION;
const DYNAMIC_CACHE = 'feuerwehr-app-dynamic-' + CACHE_VERSION;
const API_CACHE = 'feuerwehr-app-api-' + CACHE_VERSION;
const OFFLINE_FALLBACK = '/login.php';

// Static assets to cache on install.
// NOTE: / and /index.php are intentionally excluded because they contain
//       auth-checks and redirects that must not be cached. /login.php is
//       included instead. Navigation to / is always handled network-first
//       by the navigate handler below.
const STATIC_ASSETS = [
  '/login.php',
  '/public/css/style.css',
  '/public/js/app.js',
  '/manifest.json',
  '/public/icons/icon-192x192.png',
  '/public/icons/icon-512x512.png'
];

// API endpoints to cache
const API_ROUTES = [
  '/src/php/api/personnel.php',
  '/src/php/api/vehicles.php',
  '/src/php/api/locations.php',
  '/src/php/api/phone-numbers.php',
  '/src/php/api/hazmat.php'
];

// Pages to cache dynamically
const PAGE_ROUTES = [
  '/src/php/pages/home.php',
  '/src/php/pages/attendance.php',
  '/src/php/pages/mission-report.php',
  '/src/php/pages/phone-numbers.php',
  '/src/php/pages/hazard-matrix.php',
  '/src/php/pages/hazmat.php',
  '/src/php/pages/vehicles.php'
];

// Install event - cache static resources
self.addEventListener('install', event => {
  console.log('[SW] Installing service worker...');
  event.waitUntil(
    caches.open(STATIC_CACHE)
      .then(cache => {
        console.log('[SW] Caching static assets');
        return cache.addAll(STATIC_ASSETS);
      })
      .then(() => self.skipWaiting())
  );
});

// Activate event - clean up old caches
self.addEventListener('activate', event => {
  console.log('[SW] Activating service worker...');
  event.waitUntil(
    caches.keys()
      .then(cacheNames => {
        return Promise.all(
          cacheNames.map(cacheName => {
            if (cacheName.startsWith('feuerwehr-app-') && 
                cacheName !== STATIC_CACHE && 
                cacheName !== DYNAMIC_CACHE && 
                cacheName !== API_CACHE) {
              console.log('[SW] Deleting old cache:', cacheName);
              return caches.delete(cacheName);
            }
          })
        );
      })
      .then(() => self.clients.claim())
  );
});

// Helper function to determine cache strategy
function getCacheStrategy(url) {
  // Static assets: cache-first
  if (STATIC_ASSETS.some(asset => url.pathname === asset) || 
      url.pathname.match(/\.(css|js|png|jpg|jpeg|svg|woff|woff2)$/)) {
    return 'cache-first';
  }
  
  // API endpoints: network-first with cache fallback
  if (url.pathname.includes('/src/php/api/') && 
      (url.pathname.includes('personnel') || 
       url.pathname.includes('vehicles') || 
       url.pathname.includes('locations') ||
       url.pathname.includes('phone-numbers') ||
       url.pathname.includes('hazmat'))) {
    return 'network-first';
  }
  
  // Pages: network-first with cache fallback
  if (url.pathname.includes('/src/php/pages/')) {
    return 'network-first';
  }
  
  // Form submissions: network-only
  if (url.pathname.includes('/src/php/forms/') || 
      (url.pathname.includes('/src/php/api/') && url.pathname.match(/\/(attendance|mission-report|users|settings)/))) {
    return 'network-only';
  }
  
  // Default: network-first
  return 'network-first';
}

// Cache-first strategy
async function cacheFirst(request) {
  const cache = await caches.open(STATIC_CACHE);
  const cached = await cache.match(request);
  if (cached) {
    return cached;
  }
  
  try {
    const response = await fetch(request);
    if (response.ok) {
      cache.put(request, response.clone());
    }
    return response;
  } catch (error) {
    console.log('[SW] Fetch failed for:', request.url);
    throw error;
  }
}

// Network-first strategy
async function networkFirst(request) {
  const cacheName = request.url.includes('/src/php/api/') ? API_CACHE : DYNAMIC_CACHE;
  
  try {
    const response = await fetch(request);
    if (response.ok) {
      const cache = await caches.open(cacheName);
      cache.put(request, response.clone());
    }
    return response;
  } catch (error) {
    console.log('[SW] Network failed, trying cache for:', request.url);
    const cache = await caches.open(cacheName);
    const cached = await cache.match(request);
    if (cached) {
      return cached;
    }
    throw error;
  }
}

// Fetch event - intelligent caching
self.addEventListener('fetch', event => {
  const url = new URL(event.request.url);
  
  // Skip cross-origin requests
  if (url.origin !== location.origin) {
    return;
  }
  
  // Skip chrome extensions and other non-http(s) requests
  if (!event.request.url.startsWith('http')) {
    return;
  }

  // Logout: clear dynamic/API caches so no stale authenticated data remains,
  // then let the browser handle the redirect to /login.php natively.
  if (event.request.mode === 'navigate' && url.searchParams.get('action') === 'logout') {
    event.waitUntil(
      Promise.all([
        caches.delete(DYNAMIC_CACHE),
        caches.delete(API_CACHE)
      ])
    );
    return;
  }

  // Other navigation requests: pass the original Request object so its
  // redirect:'manual' mode is preserved.  When the server issues a 302
  // (e.g. session expired → /login.php) the SW receives an opaque-redirect
  // response and returns it to the browser, which then follows the redirect
  // natively.  Using fetch(event.request.url) (a plain string) would create
  // a new Request with redirect:'follow', causing the SW to follow the
  // redirect internally and return a response with response.redirected===true,
  // which triggers "ERR_FAILED – a redirected response was used for a request
  // whose redirect mode is not 'follow'".
  // Fall back to the cached login page when offline.
  if (event.request.mode === 'navigate') {
    event.respondWith(
      fetch(event.request)
        .catch(() => {
          console.log('[SW] Navigate fetch failed, serving offline fallback for:', event.request.url);
          return caches.match(OFFLINE_FALLBACK);
        })
    );
    return;
  }

  const strategy = getCacheStrategy(url);
  
  if (strategy === 'cache-first') {
    event.respondWith(cacheFirst(event.request));
  } else if (strategy === 'network-first') {
    event.respondWith(networkFirst(event.request));
  } else {
    // network-only - just fetch
    event.respondWith(fetch(event.request));
  }
});

// Background Sync for form submissions
self.addEventListener('sync', event => {
  if (event.tag === 'sync-forms') {
    console.log('[SW] Background sync triggered');
    event.waitUntil(syncPendingForms());
  }
});

// Sync pending forms from IndexedDB
async function syncPendingForms() {
  try {
    // Open IndexedDB
    const db = await openDB();
    
    const tx = db.transaction('pending-forms', 'readonly');
    const store = tx.objectStore('pending-forms');
    const forms = await getAllFromStore(store);
    
    console.log('[SW] Found', forms.length, 'pending forms to sync');
    
    // Send each form
    for (const formData of forms) {
      try {
        const fetchOptions = {
          method: 'POST',
          body: formData.data
        };

        // Re-apply the content type stored at save time (default: application/json)
        if (formData.contentType) {
          fetchOptions.headers = { 'Content-Type': formData.contentType };
        }

        const response = await fetch(formData.url, fetchOptions);
        
        if (response.ok) {
          // Remove from IndexedDB on success using a Promise-wrapped transaction
          const deleteDb = await openDB();
          const deleteTx = deleteDb.transaction('pending-forms', 'readwrite');
          const deleteStore = deleteTx.objectStore('pending-forms');
          deleteStore.delete(formData.id);

          // Wait for the transaction to fully commit before continuing
          await new Promise((resolve, reject) => {
            deleteTx.oncomplete = resolve;
            deleteTx.onerror   = () => reject(deleteTx.error);
            deleteTx.onabort   = () => reject(new Error('Transaction aborted'));
          });

          deleteDb.close();
          
          console.log('[SW] Successfully synced form:', formData.id);
          
          // Notify all clients
          const clients = await self.clients.matchAll();
          clients.forEach(client => {
            client.postMessage({
              type: 'FORM_SYNCED',
              formId: formData.id
            });
          });
        }
      } catch (error) {
        console.log('[SW] Failed to sync form:', formData.id, error);
      }
    }
    
    db.close();
  } catch (error) {
    console.error('[SW] Error syncing forms:', error);
  }
}

// Helper to open IndexedDB
function openDB() {
  return new Promise((resolve, reject) => {
    const request = indexedDB.open('FeuerwehrAppDB', 1);
    
    request.onerror = () => reject(request.error);
    
    request.onsuccess = () => {
      const db = request.result;
      // Handle version change for proper cleanup
      db.onversionchange = () => {
        db.close();
      };
      resolve(db);
    };
    
    request.onupgradeneeded = (event) => {
      const db = event.target.result;
      if (!db.objectStoreNames.contains('pending-forms')) {
        db.createObjectStore('pending-forms', { keyPath: 'id', autoIncrement: true });
      }
    };
  });
}

// Helper to get all items from store
function getAllFromStore(store) {
  return new Promise((resolve, reject) => {
    const request = store.getAll();
    request.onerror = () => reject(request.error);
    request.onsuccess = () => resolve(request.result);
  });
}
