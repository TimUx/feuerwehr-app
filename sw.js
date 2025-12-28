const CACHE_VERSION = 'v2';
const STATIC_CACHE = 'feuerwehr-app-static-' + CACHE_VERSION;
const DYNAMIC_CACHE = 'feuerwehr-app-dynamic-' + CACHE_VERSION;
const API_CACHE = 'feuerwehr-app-api-' + CACHE_VERSION;

// Static assets to cache on install
const STATIC_ASSETS = [
  '/',
  '/index.php',
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
        const response = await fetch(formData.url, {
          method: 'POST',
          body: formData.data
        });
        
        if (response.ok) {
          // Remove from IndexedDB on success
          const deleteTx = db.transaction('pending-forms', 'readwrite');
          const deleteStore = deleteTx.objectStore('pending-forms');
          await deleteStore.delete(formData.id);
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
  } catch (error) {
    console.error('[SW] Error syncing forms:', error);
  }
}

// Helper to open IndexedDB
function openDB() {
  return new Promise((resolve, reject) => {
    const request = indexedDB.open('FeuerwehrAppDB', 1);
    
    request.onerror = () => reject(request.error);
    request.onsuccess = () => resolve(request.result);
    
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
