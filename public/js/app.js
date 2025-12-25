// Feuerwehr App - Main JavaScript

class FeuerwehrApp {
  constructor() {
    this.currentPage = 'home';
    this.theme = localStorage.getItem('theme') || 'light';
    this.deferredPrompt = null;
    this.init();
  }

  init() {
    this.setupServiceWorker();
    this.setupTheme();
    this.setupNavigation();
    this.setupEventListeners();
    this.setupPWAInstall();
    
    // Load the home page on initialization
    this.loadPage(this.currentPage);
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

    // Listen for the beforeinstallprompt event
    window.addEventListener('beforeinstallprompt', (e) => {
      // Prevent the mini-infobar from appearing on mobile
      e.preventDefault();
      // Stash the event so it can be triggered later
      this.deferredPrompt = e;
      // Show the install button
      installBtn.style.display = 'block';
    });

    // Handle install button click
    installBtn.addEventListener('click', async () => {
      if (!this.deferredPrompt) {
        return;
      }

      // Show the install prompt
      this.deferredPrompt.prompt();
      
      // Wait for the user to respond to the prompt
      const { outcome } = await this.deferredPrompt.userChoice;
      
      console.log(`User response to the install prompt: ${outcome}`);
      
      // Clear the deferredPrompt
      this.deferredPrompt = null;
      
      // Hide the install button
      installBtn.style.display = 'none';
    });

    // Listen for the appinstalled event
    window.addEventListener('appinstalled', () => {
      console.log('PWA was installed');
      // Hide the install button
      installBtn.style.display = 'none';
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

  navigateTo(page) {
    this.currentPage = page;
    
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

    try {
      const response = await fetch(`/src/php/pages/${page}.php`);
      if (response.ok) {
        const html = await response.text();
        mainContent.innerHTML = html;
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
      this.showAlert('error', 'Fehler beim Senden des Formulars');
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
