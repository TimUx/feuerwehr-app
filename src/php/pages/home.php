<?php
/**
 * Home Page - Main Menu
 */

require_once __DIR__ . '/../auth.php';

Auth::requireAuth();

$user = Auth::getUser();
$isAdmin = Auth::isAdmin();
?>

<div class="menu-container">
    <div class="menu-grid">
        <!-- Anwesenheitsliste -->
        <button class="menu-button" onclick="window.feuerwehrApp.navigateTo('attendance')">
            <span class="material-icons">fact_check</span>
            <span class="menu-button-text">Anwesenheitsliste</span>
        </button>
        
        <!-- Einsatzbericht -->
        <button class="menu-button" onclick="window.feuerwehrApp.navigateTo('mission-report')">
            <span class="material-icons">description</span>
            <span class="menu-button-text">Einsatzbericht</span>
        </button>
        
        <!-- Fahrzeuge -->
        <button class="menu-button" onclick="window.feuerwehrApp.navigateTo('vehicles')">
            <span class="material-icons">local_shipping</span>
            <span class="menu-button-text">Fahrzeuge</span>
        </button>
        
        <!-- Telefonnummern -->
        <button class="menu-button" onclick="window.feuerwehrApp.navigateTo('phone-numbers')">
            <span class="material-icons">phone</span>
            <span class="menu-button-text">Telefonnummern</span>
        </button>
        
        <!-- Online Karte -->
        <button class="menu-button" onclick="window.feuerwehrApp.navigateTo('map')">
            <span class="material-icons">map</span>
            <span class="menu-button-text">Online Karte</span>
        </button>
        
        <!-- Gefahrenmatrix -->
        <button class="menu-button" onclick="window.feuerwehrApp.navigateTo('hazard-matrix')">
            <span class="material-icons">warning</span>
            <span class="menu-button-text">Gefahrenmatrix</span>
        </button>
        
        <!-- Gefahrstoffkennzeichen -->
        <button class="menu-button" onclick="window.feuerwehrApp.navigateTo('hazmat')">
            <span class="material-icons">science</span>
            <span class="menu-button-text">Gefahrstoffe</span>
        </button>
        
        <!-- Statistiken (moved to end) -->
        <button class="menu-button" onclick="window.feuerwehrApp.navigateTo('statistics')">
            <span class="material-icons">bar_chart</span>
            <span class="menu-button-text">Statistiken</span>
        </button>
        
        <!-- Allgemeine Einstellungen (for all users) -->
        <button class="menu-button" onclick="window.feuerwehrApp.navigateTo('admin-settings')">
            <span class="material-icons">settings</span>
            <span class="menu-button-text">Allgemeine Einstellungen</span>
        </button>
        
        <!-- PWA Install Button (only show if app can be installed) -->
        <button class="menu-button" id="home-install-btn" style="display: none;" onclick="window.feuerwehrApp.installPWA()">
            <span class="material-icons">get_app</span>
            <span class="menu-button-text">App installieren</span>
        </button>
    </div>
    
    <?php if ($isAdmin): ?>
    <div class="menu-section-admin">
        <h3 class="menu-title">Administration</h3>
        
        <div class="menu-grid">
            <!-- Einsatzkräfte -->
            <button class="menu-button menu-button-admin" onclick="window.feuerwehrApp.navigateTo('personnel')">
                <span class="material-icons">people</span>
                <span class="menu-button-text">Einsatzkräfte</span>
            </button>
            
            <!-- Fahrzeuge -->
            <button class="menu-button menu-button-admin" onclick="window.feuerwehrApp.navigateTo('admin-vehicles')">
                <span class="material-icons">local_shipping</span>
                <span class="menu-button-text">Fahrzeuge</span>
            </button>
            
            <!-- Telefonnummern -->
            <button class="menu-button menu-button-admin" onclick="window.feuerwehrApp.navigateTo('admin-phone-numbers')">
                <span class="material-icons">phone</span>
                <span class="menu-button-text">Telefonnummern</span>
            </button>
            
            <!-- Formulardaten -->
            <button class="menu-button menu-button-admin menu-button-disabled" disabled title="Demnächst verfügbar">
                <span class="material-icons">folder</span>
                <span class="menu-button-text">Formulardaten</span>
            </button>
            
            <!-- Benutzer -->
            <button class="menu-button menu-button-admin" onclick="window.feuerwehrApp.navigateTo('users')">
                <span class="material-icons">admin_panel_settings</span>
                <span class="menu-button-text">Benutzer</span>
            </button>
            
            <!-- Allgemeine Einstellungen -->
            <button class="menu-button menu-button-admin" onclick="window.feuerwehrApp.navigateTo('admin-settings')">
                <span class="material-icons">settings</span>
                <span class="menu-button-text">Allgemeine Einstellungen</span>
            </button>
            
            <!-- Email Einstellungen -->
            <button class="menu-button menu-button-admin" onclick="window.feuerwehrApp.navigateTo('email-settings')">
                <span class="material-icons">email</span>
                <span class="menu-button-text">Email Einstellungen</span>
            </button>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
// Listen for deferred prompt to show PWA install button
window.addEventListener('beforeinstallprompt', (e) => {
    const homeInstallBtn = document.getElementById('home-install-btn');
    if (homeInstallBtn) {
        homeInstallBtn.style.display = 'flex';
    }
});

// Hide button after installation
window.addEventListener('appinstalled', () => {
    const homeInstallBtn = document.getElementById('home-install-btn');
    if (homeInstallBtn) {
        homeInstallBtn.style.display = 'none';
    }
});
</script>
