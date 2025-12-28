/**
 * Offline UI Manager
 * Manages UI components for offline status and pending forms
 */

class OfflineUI {
  constructor(offlineStorage) {
    this.offlineStorage = offlineStorage;
    this.statusIndicator = null;
    this.syncButton = null;
    this.pendingBadge = null;
    this.init();
  }

  /**
   * Initialize UI components
   */
  init() {
    this.createStatusIndicator();
    this.createSyncButton();
    this.setupEventListeners();
    this.updateStatus();
    this.updatePendingCount();
  }

  /**
   * Create online/offline status indicator
   */
  createStatusIndicator() {
    // Check if indicator already exists
    if (document.getElementById('offline-status-indicator')) {
      this.statusIndicator = document.getElementById('offline-status-indicator');
      return;
    }

    const indicator = document.createElement('div');
    indicator.id = 'offline-status-indicator';
    indicator.className = 'offline-status-indicator';
    indicator.innerHTML = `
      <span class="material-icons status-icon">cloud_off</span>
      <span class="status-text">Offline</span>
    `;

    // Add to body
    document.body.appendChild(indicator);
    this.statusIndicator = indicator;
  }

  /**
   * Create sync button
   */
  createSyncButton() {
    // Check if button already exists in header
    const header = document.querySelector('.app-header .app-actions');
    if (!header) return;

    if (document.getElementById('sync-button')) {
      this.syncButton = document.getElementById('sync-button');
      this.pendingBadge = document.getElementById('sync-pending-badge');
      return;
    }

    const syncBtn = document.createElement('button');
    syncBtn.id = 'sync-button';
    syncBtn.className = 'icon-btn';
    syncBtn.title = 'Offline-Formulare synchronisieren';
    syncBtn.style.display = 'none';
    syncBtn.style.position = 'relative';
    syncBtn.innerHTML = `
      <span class="material-icons">sync</span>
      <span id="sync-pending-badge" class="sync-badge" style="display: none;">0</span>
    `;

    // Insert before theme toggle button
    const themeToggle = document.getElementById('theme-toggle');
    if (themeToggle) {
      header.insertBefore(syncBtn, themeToggle);
    } else {
      header.appendChild(syncBtn);
    }

    this.syncButton = syncBtn;
    this.pendingBadge = document.getElementById('sync-pending-badge');
  }

  /**
   * Setup event listeners
   */
  setupEventListeners() {
    // Online/offline events
    window.addEventListener('online', () => {
      console.log('[OfflineUI] Browser is online');
      this.updateStatus();
      this.autoSync();
    });

    window.addEventListener('offline', () => {
      console.log('[OfflineUI] Browser is offline');
      this.updateStatus();
    });

    // Sync button click
    if (this.syncButton) {
      this.syncButton.addEventListener('click', () => {
        this.manualSync();
      });
    }

    // Service worker messages
    if ('serviceWorker' in navigator) {
      navigator.serviceWorker.addEventListener('message', (event) => {
        if (event.data && event.data.type === 'FORM_SYNCED') {
          console.log('[OfflineUI] Form synced by service worker:', event.data.formId);
          this.updatePendingCount();
          this.showNotification('Formular erfolgreich synchronisiert', 'success');
        }
      });
    }
  }

  /**
   * Update online/offline status indicator
   */
  updateStatus() {
    if (!this.statusIndicator) return;

    const isOnline = navigator.onLine;
    
    if (isOnline) {
      this.statusIndicator.classList.add('online');
      this.statusIndicator.classList.remove('offline');
      this.statusIndicator.querySelector('.status-icon').textContent = 'cloud_done';
      this.statusIndicator.querySelector('.status-text').textContent = 'Online';
    } else {
      this.statusIndicator.classList.add('offline');
      this.statusIndicator.classList.remove('online');
      this.statusIndicator.querySelector('.status-icon').textContent = 'cloud_off';
      this.statusIndicator.querySelector('.status-text').textContent = 'Offline';
    }
  }

  /**
   * Update pending forms count
   */
  async updatePendingCount() {
    try {
      const count = await this.offlineStorage.getPendingCount();
      
      if (this.pendingBadge) {
        if (count > 0) {
          this.pendingBadge.textContent = count;
          this.pendingBadge.style.display = 'flex';
        } else {
          this.pendingBadge.style.display = 'none';
        }
      }
      
      this.updateSyncButtonVisibility(count);
    } catch (error) {
      console.error('[OfflineUI] Error updating pending count:', error);
    }
  }

  /**
   * Update sync button visibility based on pending count and online status
   */
  updateSyncButtonVisibility(count = 0) {
    if (!this.syncButton) return;
    
    // Show button if there are pending forms or if offline
    if (count > 0 || !navigator.onLine) {
      this.syncButton.style.display = 'block';
    } else {
      this.syncButton.style.display = 'none';
    }
  }

  /**
   * Auto-sync when coming online
   */
  async autoSync() {
    try {
      const count = await this.offlineStorage.getPendingCount();
      if (count > 0) {
        console.log('[OfflineUI] Auto-syncing pending forms');
        await this.performSync();
      }
    } catch (error) {
      console.error('[OfflineUI] Auto-sync failed:', error);
      this.showNotification('Automatische Synchronisierung fehlgeschlagen. Bitte versuchen Sie es manuell.', 'warning');
    }
  }

  /**
   * Manual sync triggered by user
   */
  async manualSync() {
    if (!navigator.onLine) {
      this.showNotification('Keine Internetverbindung verfügbar', 'error');
      return;
    }

    const count = await this.offlineStorage.getPendingCount();
    if (count === 0) {
      this.showNotification('Keine ausstehenden Formulare zum Synchronisieren', 'info');
      return;
    }

    await this.performSync();
  }

  /**
   * Perform sync operation
   */
  async performSync() {
    if (!this.syncButton) return;

    // Show loading state
    const originalHTML = this.syncButton.innerHTML;
    this.syncButton.disabled = true;
    this.syncButton.innerHTML = '<span class="material-icons rotating">sync</span>';

    try {
      const result = await this.offlineStorage.syncPendingForms();
      
      if (result.success) {
        const message = `${result.results.success} Formular(e) erfolgreich synchronisiert`;
        this.showNotification(message, 'success');
        
        if (result.results.failed > 0) {
          const failMessage = `${result.results.failed} Formular(e) konnten nicht synchronisiert werden`;
          this.showNotification(failMessage, 'warning');
        }
      } else {
        this.showNotification('Synchronisierung fehlgeschlagen', 'error');
      }
      
      await this.updatePendingCount();
    } catch (error) {
      console.error('[OfflineUI] Sync error:', error);
      this.showNotification('Fehler bei der Synchronisierung', 'error');
    } finally {
      // Restore button state
      this.syncButton.disabled = false;
      this.syncButton.innerHTML = originalHTML;
    }
  }

  /**
   * Show notification to user
   */
  showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `offline-notification ${type}`;
    
    const icon = {
      'success': 'check_circle',
      'error': 'error',
      'warning': 'warning',
      'info': 'info'
    }[type] || 'info';
    
    notification.innerHTML = `
      <span class="material-icons">${icon}</span>
      <span>${message}</span>
    `;

    document.body.appendChild(notification);

    // Trigger animation
    setTimeout(() => {
      notification.classList.add('show');
    }, 10);

    // Remove after delay
    setTimeout(() => {
      notification.classList.remove('show');
      setTimeout(() => {
        notification.remove();
      }, 300);
    }, 4000);
  }

  /**
   * Show pending forms list (for debugging or user review)
   */
  async showPendingFormsList() {
    const forms = await this.offlineStorage.getPendingForms();
    
    if (forms.length === 0) {
      this.showNotification('Keine ausstehenden Formulare', 'info');
      return;
    }

    // Create modal or display list
    const list = forms.map(form => {
      const date = new Date(form.timestamp).toLocaleString('de-DE');
      return `- ${form.type} (${date})`;
    }).join('\n');

    console.log('[OfflineUI] Pending forms:\n', list);
    this.showNotification(`${forms.length} ausstehende(s) Formular(e)`, 'info');
  }
}

// Export for use in main app
if (typeof window !== 'undefined') {
  window.OfflineUI = OfflineUI;
}
