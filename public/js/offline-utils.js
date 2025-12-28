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
  // Function to setup banner
  const setup = () => {
    const banner = document.getElementById(bannerId);
    if (!banner) {
      console.warn('[OfflineUtils] Banner element not found:', bannerId);
      return;
    }
    
    // Update banner on page load
    updateOfflineBanner(bannerId);
    
    // Update banner when online/offline status changes
    window.addEventListener('online', () => updateOfflineBanner(bannerId));
    window.addEventListener('offline', () => updateOfflineBanner(bannerId));
  };
  
  // Check if DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', setup);
  } else {
    setup();
  }
}

// Export functions
if (typeof window !== 'undefined') {
  window.updateOfflineBanner = updateOfflineBanner;
  window.initOfflineBanner = initOfflineBanner;
}
