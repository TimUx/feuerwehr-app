/**
 * Offline Storage Manager
 * Handles IndexedDB operations for offline form submissions
 */

class OfflineStorage {
  constructor() {
    this.dbName = 'FeuerwehrAppDB';
    this.dbVersion = 1;
    this.db = null;
    this.syncInProgress = false;
  }

  /**
   * Initialize IndexedDB
   */
  async init() {
    return new Promise((resolve, reject) => {
      const request = indexedDB.open(this.dbName, this.dbVersion);

      request.onerror = () => {
        console.error('[OfflineStorage] Database error:', request.error);
        reject(request.error);
      };

      request.onsuccess = () => {
        this.db = request.result;
        console.log('[OfflineStorage] Database opened successfully');
        resolve(this.db);
      };

      request.onupgradeneeded = (event) => {
        const db = event.target.result;
        
        // Create object stores
        if (!db.objectStoreNames.contains('pending-forms')) {
          const store = db.createObjectStore('pending-forms', { 
            keyPath: 'id', 
            autoIncrement: true 
          });
          store.createIndex('timestamp', 'timestamp', { unique: false });
          store.createIndex('type', 'type', { unique: false });
          console.log('[OfflineStorage] Created pending-forms store');
        }
      };
    });
  }

  /**
   * Save form data for offline submission
   */
  async saveForm(formType, url, formData, additionalInfo = {}) {
    if (!this.db) {
      await this.init();
    }

    return new Promise((resolve, reject) => {
      const transaction = this.db.transaction(['pending-forms'], 'readwrite');
      const store = transaction.objectStore('pending-forms');

      const formEntry = {
        type: formType,
        url: url,
        data: formData,
        timestamp: new Date().toISOString(),
        status: 'pending',
        ...additionalInfo
      };

      const request = store.add(formEntry);

      request.onsuccess = () => {
        console.log('[OfflineStorage] Form saved offline:', formType, request.result);
        resolve(request.result);
      };

      request.onerror = () => {
        console.error('[OfflineStorage] Error saving form:', request.error);
        reject(request.error);
      };
    });
  }

  /**
   * Get all pending forms
   */
  async getPendingForms() {
    if (!this.db) {
      await this.init();
    }

    return new Promise((resolve, reject) => {
      const transaction = this.db.transaction(['pending-forms'], 'readonly');
      const store = transaction.objectStore('pending-forms');
      const request = store.getAll();

      request.onsuccess = () => {
        resolve(request.result);
      };

      request.onerror = () => {
        reject(request.error);
      };
    });
  }

  /**
   * Get count of pending forms
   */
  async getPendingCount() {
    if (!this.db) {
      await this.init();
    }

    return new Promise((resolve, reject) => {
      const transaction = this.db.transaction(['pending-forms'], 'readonly');
      const store = transaction.objectStore('pending-forms');
      const request = store.count();

      request.onsuccess = () => {
        resolve(request.result);
      };

      request.onerror = () => {
        reject(request.error);
      };
    });
  }

  /**
   * Delete a form entry
   */
  async deleteForm(id) {
    if (!this.db) {
      await this.init();
    }

    return new Promise((resolve, reject) => {
      const transaction = this.db.transaction(['pending-forms'], 'readwrite');
      const store = transaction.objectStore('pending-forms');
      const request = store.delete(id);

      request.onsuccess = () => {
        console.log('[OfflineStorage] Form deleted:', id);
        resolve();
      };

      request.onerror = () => {
        reject(request.error);
      };
    });
  }

  /**
   * Sync all pending forms
   */
  async syncPendingForms() {
    if (this.syncInProgress) {
      console.log('[OfflineStorage] Sync already in progress');
      return { success: false, message: 'Sync already in progress' };
    }

    if (!navigator.onLine) {
      console.log('[OfflineStorage] Cannot sync - offline');
      return { success: false, message: 'No internet connection' };
    }

    this.syncInProgress = true;
    const results = {
      total: 0,
      success: 0,
      failed: 0,
      errors: []
    };

    try {
      const pendingForms = await this.getPendingForms();
      results.total = pendingForms.length;

      if (pendingForms.length === 0) {
        console.log('[OfflineStorage] No pending forms to sync');
        this.syncInProgress = false;
        return { success: true, results };
      }

      console.log('[OfflineStorage] Syncing', pendingForms.length, 'pending forms');

      for (const form of pendingForms) {
        try {
          console.log('[OfflineStorage] Submitting form:', form.id, form.type);
          
          const response = await fetch(form.url, {
            method: 'POST',
            body: form.data
          });

          if (response.ok) {
            const result = await response.json();
            console.log('[OfflineStorage] Form submitted successfully:', form.id);
            await this.deleteForm(form.id);
            results.success++;
          } else {
            const errorText = await response.text();
            console.error('[OfflineStorage] Form submission failed:', form.id, response.status, errorText);
            results.failed++;
            results.errors.push({
              formId: form.id,
              type: form.type,
              error: `HTTP ${response.status}: ${errorText.substring(0, 100)}`
            });
          }
        } catch (error) {
          console.error('[OfflineStorage] Error submitting form:', form.id, error);
          results.failed++;
          results.errors.push({
            formId: form.id,
            type: form.type,
            error: error.message
          });
        }
      }
    } catch (error) {
      console.error('[OfflineStorage] Sync error:', error);
      results.errors.push({
        error: error.message
      });
    } finally {
      this.syncInProgress = false;
    }

    return { success: results.success > 0, results };
  }

  /**
   * Clear all pending forms (use with caution)
   */
  async clearAll() {
    if (!this.db) {
      await this.init();
    }

    return new Promise((resolve, reject) => {
      const transaction = this.db.transaction(['pending-forms'], 'readwrite');
      const store = transaction.objectStore('pending-forms');
      const request = store.clear();

      request.onsuccess = () => {
        console.log('[OfflineStorage] All pending forms cleared');
        resolve();
      };

      request.onerror = () => {
        reject(request.error);
      };
    });
  }

  /**
   * Register background sync (if supported)
   */
  async registerBackgroundSync() {
    if ('serviceWorker' in navigator && 'sync' in ServiceWorkerRegistration.prototype) {
      try {
        const registration = await navigator.serviceWorker.ready;
        await registration.sync.register('sync-forms');
        console.log('[OfflineStorage] Background sync registered');
        return true;
      } catch (error) {
        console.log('[OfflineStorage] Background sync registration failed:', error);
        return false;
      }
    } else {
      console.log('[OfflineStorage] Background sync not supported');
      return false;
    }
  }
}

// Create singleton instance
const offlineStorage = new OfflineStorage();

// Export for use in other scripts
if (typeof window !== 'undefined') {
  window.OfflineStorage = offlineStorage;
}
