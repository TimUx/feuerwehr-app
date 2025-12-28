/**
 * Shared utility functions for offline functionality
 */

/**
 * Update offline banner visibility based on connection status
 * @param {string} bannerId - ID of the banner element
 */
function updateOfflineBanner(bannerId = 'offline-banner') {
  const banner = document.getElementById(bannerId);
  if (banner) {
    banner.style.display = navigator.onLine ? 'none' : 'flex';
  }
}

/**
 * Initialize offline banner for a page
 * @param {string} bannerId - ID of the banner element
 */
function initOfflineBanner(bannerId = 'offline-banner') {
  // Update banner on page load
  updateOfflineBanner(bannerId);
  
  // Update banner when online/offline status changes
  window.addEventListener('online', () => updateOfflineBanner(bannerId));
  window.addEventListener('offline', () => updateOfflineBanner(bannerId));
}

// Export functions
if (typeof window !== 'undefined') {
  window.updateOfflineBanner = updateOfflineBanner;
  window.initOfflineBanner = initOfflineBanner;
}
