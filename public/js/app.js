// Feuerwehr App - Main JavaScript

class FeuerwehrApp {
  constructor() {
    this.currentPage = 'home';
    this.pageParams = new URLSearchParams();
    this.theme = localStorage.getItem('theme') || 'light';
    this.deferredPrompt = null;
    this.pageScripts = []; // Track scripts added by pages for cleanup
    this.offlineUI = null;
    this.init();
  }

  init() {
    this.setupServiceWorker();
    this.setupOfflineSupport();
    this.setupTheme();
    this.setupNavigation();
    this.setupEventListeners();
    this.setupPWAInstall();
    
    // Check for URL parameters on initialization
    const urlParams = new URLSearchParams(window.location.search);
    const page = urlParams.get('page');
    if (page) {
      this.currentPage = page;
      // Store any additional parameters
      urlParams.delete('page');
      this.pageParams = urlParams;
    }
    
    // Load the initial page
    this.loadPage(this.currentPage);
  }

  // Setup offline support
  setupOfflineSupport() {
    // Initialize offline storage and UI only when authenticated
    if (document.body.classList.contains('authenticated') || 
        !document.querySelector('.login-container')) {
      if (typeof OfflineStorage !== 'undefined' && typeof OfflineUI !== 'undefined') {
        this.offlineUI = new OfflineUI(window.OfflineStorage);
        console.log('[App] Offline support initialized');
      }
    }
  }

  // Register Service Worker for PWA
  setupServiceWorker() {
    if ('serviceWorker' in navigator) {
      navigator.serviceWorker.register('/sw.js')
        .then(registration => {
          console.log('Service Worker registered:', registration);
        })
        .catch(error => {
          console.error('Service Worker registration failed:', error);
        });
    }
  }

  // PWA Install Prompt
  setupPWAInstall() {
    const installBtn = document.getElementById('install-btn');
    
    if (!installBtn) return;
    
    // Check if already installed
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches || 
                        window.navigator.standalone || 
                        document.referrer.includes('android-app://');
    
    // Detect iOS
    const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
    const isIOSSafari = isIOS && !navigator.standalone && !window.MSStream;

    // Listen for the beforeinstallprompt event (works on Android/Chrome)
    window.addEventListener('beforeinstallprompt', (e) => {
      // Prevent the mini-infobar from appearing on mobile
      e.preventDefault();
      // Stash the event so it can be triggered later
      this.deferredPrompt = e;
      // Show the install button in header
      installBtn.style.display = 'block';
      // Show the install button on home page if it exists
      const homeInstallBtn = document.getElementById('home-install-btn');
      if (homeInstallBtn) {
        homeInstallBtn.style.display = 'flex';
      }
    });

    // Handle install button click in header
    installBtn.addEventListener('click', async () => {
      await this.installPWA();
    });
    
    // For iOS Safari - show instructions instead of install prompt
    if (isIOSSafari && !isStandalone) {
      // Show install button for iOS users
      installBtn.style.display = 'block';
      // Show home install button if available
      const homeInstallBtn = document.getElementById('home-install-btn');
      if (homeInstallBtn) {
        homeInstallBtn.style.display = 'flex';
      }
    }

    // Listen for the appinstalled event
    window.addEventListener('appinstalled', () => {
      console.log('PWA was installed');
      // Hide the install buttons
      installBtn.style.display = 'none';
      const homeInstallBtn = document.getElementById('home-install-btn');
      if (homeInstallBtn) homeInstallBtn.style.display = 'none';
      // Clear the deferredPrompt
      this.deferredPrompt = null;
    });
  }

  // Theme Management
  setupTheme() {
    document.documentElement.setAttribute('data-theme', this.theme);
    const themeToggle = document.getElementById('theme-toggle');
    if (themeToggle) {
      themeToggle.addEventListener('click', () => this.toggleTheme());
    }
  }

  toggleTheme() {
    this.theme = this.theme === 'light' ? 'dark' : 'light';
    document.documentElement.setAttribute('data-theme', this.theme);
    localStorage.setItem('theme', this.theme);
  }

  // Install PWA - can be called from buttons
  async installPWA() {
    // Detect iOS
    const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
    const isIOSSafari = isIOS && !navigator.standalone;
    
    // For iOS Safari - show manual installation instructions
    if (isIOSSafari) {
      const message = `
        <div style="text-align: left; padding: 10px;">
          <h3 style="margin-top: 0;">📱 Installation auf iOS</h3>
          <ol style="line-height: 1.8;">
            <li>Tippen Sie auf das <strong>Teilen-Symbol</strong> 
                <svg style="width: 20px; height: 20px; vertical-align: middle;" viewBox="0 0 50 50">
                  <path fill="currentColor" d="M30.3 13.7L25 8.4l-5.3 5.3-1.4-1.4L25 5.6l6.7 6.7z"/>
                  <path fill="currentColor" d="M24 7h2v21h-2z"/>
                  <path fill="currentColor" d="M35 40H15c-1.7 0-3-1.3-3-3V19c0-1.7 1.3-3 3-3h7v2h-7c-.6 0-1 .4-1 1v18c0 .6.4 1 1 1h20c.6 0 1-.4 1-1V19c0-.6-.4-1-1-1h-7v-2h7c1.7 0 3 1.3 3 3v18c0 1.7-1.3 3-3 3z"/>
                </svg> 
                (unten in der Mitte der Safari-Leiste)
            </li>
            <li>Scrollen Sie nach unten und wählen Sie<br>
                <strong>"Zum Home-Bildschirm"</strong></li>
            <li>Tippen Sie auf <strong>"Hinzufügen"</strong></li>
          </ol>
          <p style="margin-top: 15px; color: #666;">
            Die App erscheint dann als Icon auf Ihrem Home-Bildschirm.
          </p>
        </div>
      `;
      
      // Create a modal for iOS instructions
      const modal = document.createElement('div');
      modal.className = 'modal show';
      modal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 9999;';
      modal.innerHTML = `
        <div style="background: white; border-radius: 12px; max-width: 400px; margin: 20px; max-height: 80vh; overflow-y: auto;">
          ${message}
          <div style="padding: 10px; border-top: 1px solid #eee;">
            <button onclick="this.closest('.modal').remove()" 
                    style="width: 100%; padding: 12px; background: #d32f2f; color: white; border: none; border-radius: 6px; font-size: 16px; cursor: pointer;">
              Verstanden
            </button>
          </div>
        </div>
      `;
      document.body.appendChild(modal);
      return;
    }
    
    // For Android/Chrome - use standard install prompt
    if (!this.deferredPrompt) {
      // Check if already installed (with iOS fallback)
      const isStandalone = window.matchMedia('(display-mode: standalone)').matches || 
                          window.navigator.standalone;
      
      if (isStandalone) {
        this.showAlert('info', 'Die App ist bereits installiert.');
      } else {
        this.showAlert('info', 'Die App kann auf diesem Gerät oder Browser leider nicht installiert werden.');
      }
      return;
    }

    // Show the install prompt
    this.deferredPrompt.prompt();
    
    // Wait for the user to respond to the prompt
    const { outcome } = await this.deferredPrompt.userChoice;
    
    console.log(`User response to the install prompt: ${outcome}`);
    
    if (outcome === 'accepted') {
      this.showAlert('success', 'App wird installiert...');
    }
    
    // Clear the deferredPrompt
    this.deferredPrompt = null;
    
    // Hide all install buttons
    const installBtn = document.getElementById('install-btn');
    if (installBtn) installBtn.style.display = 'none';
    const homeInstallBtn = document.getElementById('home-install-btn');
    if (homeInstallBtn) homeInstallBtn.style.display = 'none';
  }

  // Navigation
  setupNavigation() {
    const menuToggle = document.getElementById('menu-toggle');
    const navDrawer = document.getElementById('nav-drawer');
    const navOverlay = document.getElementById('nav-drawer-overlay');

    if (menuToggle) {
      menuToggle.addEventListener('click', () => {
        navDrawer.classList.toggle('open');
        navOverlay.classList.toggle('visible');
      });
    }

    if (navOverlay) {
      navOverlay.addEventListener('click', () => {
        navDrawer.classList.remove('open');
        navOverlay.classList.remove('visible');
      });
    }

    // Handle navigation item clicks
    document.querySelectorAll('.nav-item').forEach(item => {
      item.addEventListener('click', (e) => {
        e.preventDefault();
        const page = item.getAttribute('data-page');
        this.navigateTo(page);
        navDrawer.classList.remove('open');
        navOverlay.classList.remove('visible');
      });
    });
  }

  navigateTo(page, params = null) {
    this.currentPage = page;
    
    // Clear or set page parameters
    if (params) {
      this.pageParams = new URLSearchParams(params);
    } else {
      this.pageParams = new URLSearchParams();
    }
    
    // Update active nav item
    document.querySelectorAll('.nav-item').forEach(item => {
      item.classList.remove('active');
      if (item.getAttribute('data-page') === page) {
        item.classList.add('active');
      }
    });

    // Load page content
    this.loadPage(page);
  }

  async loadPage(page) {
    const mainContent = document.getElementById('main-content');
    if (!mainContent) return;

    // Show loading spinner
    mainContent.innerHTML = '<div class="spinner"></div>';
    
    // Clean up scripts from previous page
    this.pageScripts.forEach(script => {
      if (script.parentNode) {
        script.parentNode.removeChild(script);
      }
    });
    this.pageScripts = [];

    try {
      // Build URL with parameters
      let url = `/src/php/pages/${page}.php`;
      if (this.pageParams.toString()) {
        url += '?' + this.pageParams.toString();
      }
      
      const response = await fetch(url);
      if (response.ok) {
        const html = await response.text();
        
        // Create a temporary container
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = html;
        
        // Extract stylesheets (link tags)
        const links = tempDiv.querySelectorAll('link[rel="stylesheet"]');
        const linkPromises = [];
        links.forEach(link => {
          // Check if this stylesheet is already loaded
          const href = link.getAttribute('href');
          const existingLink = Array.from(document.querySelectorAll('link[rel="stylesheet"]')).find(l => l.getAttribute('href') === href);
          if (!existingLink) {
            const newLink = document.createElement('link');
            newLink.rel = 'stylesheet';
            if (link.integrity) newLink.integrity = link.integrity;
            if (link.hasAttribute('crossorigin')) {
              newLink.setAttribute('crossorigin', link.getAttribute('crossorigin'));
            }
            
            const linkPromise = new Promise((resolve, reject) => {
              newLink.onload = resolve;
              newLink.onerror = reject;
            });
            linkPromises.push(linkPromise);
            
            // Set href last to trigger loading after event handlers are attached
            newLink.href = href;
            document.head.appendChild(newLink);
          }
          link.remove();
        });
        
        // Extract scripts (both external and inline)
        const scripts = tempDiv.querySelectorAll('script');
        const scriptPromises = [];
        const inlineScripts = [];
        
        scripts.forEach(script => {
          const src = script.getAttribute('src');
          if (src) {
            // External script
            // Check if this script is already loaded
            const existingScript = Array.from(document.querySelectorAll('script[src]')).find(s => s.getAttribute('src') === src);
            if (!existingScript) {
              const newScript = document.createElement('script');
              if (script.integrity) newScript.integrity = script.integrity;
              if (script.hasAttribute('crossorigin')) {
                newScript.setAttribute('crossorigin', script.getAttribute('crossorigin'));
              }
              
              const scriptPromise = new Promise((resolve, reject) => {
                newScript.onload = resolve;
                newScript.onerror = reject;
              });
              scriptPromises.push(scriptPromise);
              
              // Set src last to trigger loading after event handlers are attached
              newScript.src = src;
              document.head.appendChild(newScript);
            }
          } else if (script.textContent.trim()) {
            // Inline script - save for later execution
            inlineScripts.push(script.textContent);
          }
          script.remove();
        });
        
        // Set the HTML without scripts and stylesheets
        mainContent.innerHTML = tempDiv.innerHTML;
        
        // Wait for all external resources to load
        await Promise.all([...linkPromises, ...scriptPromises]);
        
        // Execute inline scripts in order after external resources are loaded
        inlineScripts.forEach(scriptContent => {
          try {
            // Create script element and append to head to execute in global scope
            const scriptEl = document.createElement('script');
            scriptEl.textContent = scriptContent;
            document.head.appendChild(scriptEl);
            // Track for cleanup when loading next page
            this.pageScripts.push(scriptEl);
          } catch (error) {
            console.error('Error executing page script:', error);
          }
        });
        
        this.setupEventListeners();
      } else {
        mainContent.innerHTML = '<div class="alert alert-error">Seite konnte nicht geladen werden.</div>';
      }
    } catch (error) {
      console.error('Error loading page:', error);
      mainContent.innerHTML = '<div class="alert alert-error">Fehler beim Laden der Seite.</div>';
    }
  }

  // Event Listeners
  setupEventListeners() {
    // Modal handling
    this.setupModalHandlers();
    
    // Form submissions
    this.setupFormHandlers();
  }

  setupModalHandlers() {
    document.querySelectorAll('[data-modal]').forEach(trigger => {
      trigger.addEventListener('click', (e) => {
        e.preventDefault();
        const modalId = trigger.getAttribute('data-modal');
        this.openModal(modalId);
      });
    });

    document.querySelectorAll('.modal-close, .modal [data-dismiss="modal"]').forEach(closeBtn => {
      closeBtn.addEventListener('click', (e) => {
        e.preventDefault();
        const modal = closeBtn.closest('.modal');
        if (modal) this.closeModal(modal.id);
      });
    });
  }

  openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
      modal.classList.add('show');
    }
  }

  closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
      modal.classList.remove('show');
    }
  }

  setupFormHandlers() {
    document.querySelectorAll('form[data-ajax]').forEach(form => {
      form.addEventListener('submit', async (e) => {
        e.preventDefault();
        await this.handleFormSubmit(form);
      });
    });
  }

  async handleFormSubmit(form) {
    const formData = new FormData(form);
    const action = form.getAttribute('action');
    
    // Check if this is a form that should support offline (attendance or mission report)
    const isOfflineSupportedForm = action.includes('/forms/submit_attendance.php') || 
                                     action.includes('/forms/submit_mission_report.php');

    try {
      const response = await fetch(action, {
        method: 'POST',
        body: formData
      });

      const result = await response.json();

      if (result.success) {
        this.showAlert('success', result.message || 'Erfolgreich gespeichert');
        
        // Close modal if form is in modal
        const modal = form.closest('.modal');
        if (modal) {
          this.closeModal(modal.id);
        }

        // Reload current page
        this.loadPage(this.currentPage);
      } else {
        this.showAlert('error', result.message || 'Ein Fehler ist aufgetreten');
      }
    } catch (error) {
      console.error('Form submission error:', error);
      
      // Handle offline submission for supported forms
      if (isOfflineSupportedForm && !navigator.onLine && window.OfflineStorage) {
        try {
          const formType = action.includes('attendance') ? 'Anwesenheitsliste' : 'Einsatzbericht';
          await window.OfflineStorage.saveForm(formType, action, formData);
          
          this.showAlert('warning', `Keine Internetverbindung. ${formType} wurde offline gespeichert und wird automatisch gesendet, sobald Sie wieder online sind.`);
          
          // Update pending count
          if (this.offlineUI) {
            await this.offlineUI.updatePendingCount();
          }
          
          // Register background sync if available
          await window.OfflineStorage.registerBackgroundSync();
          
          // Reset form
          form.reset();
          
          // Close modal if form is in modal
          const modal = form.closest('.modal');
          if (modal) {
            this.closeModal(modal.id);
          }
        } catch (offlineError) {
          console.error('Offline storage error:', offlineError);
          this.showAlert('error', 'Fehler beim Speichern für Offline-Nutzung');
        }
      } else {
        this.showAlert('error', 'Fehler beim Senden des Formulars. Bitte überprüfen Sie Ihre Internetverbindung.');
      }
    }
  }

  // Alert/Toast Messages
  showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.textContent = message;

    const mainContent = document.getElementById('main-content');
    if (mainContent) {
      mainContent.insertBefore(alertDiv, mainContent.firstChild);

      // Auto-remove after 5 seconds
      setTimeout(() => {
        alertDiv.remove();
      }, 5000);
    }
  }

  // Utility: API calls
  async api(endpoint, method = 'GET', data = null) {
    const options = {
      method,
      headers: {
        'Content-Type': 'application/json'
      }
    };

    if (data && method !== 'GET') {
      options.body = JSON.stringify(data);
    }

    try {
      const response = await fetch(`/src/php/api/${endpoint}`, options);
      return await response.json();
    } catch (error) {
      console.error('API error:', error);
      throw error;
    }
  }
}

// Initialize app when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
  window.feuerwehrApp = new FeuerwehrApp();
});

// Make app globally accessible
window.FeuerwehrApp = FeuerwehrApp;
