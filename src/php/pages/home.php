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
    <h2 style="margin-bottom: 2rem; text-align: center; color: var(--text-primary);">Hauptmenü</h2>
    
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
        
        <!-- Statistiken -->
        <button class="menu-button" onclick="window.feuerwehrApp.navigateTo('statistics')">
            <span class="material-icons">bar_chart</span>
            <span class="menu-button-text">Statistiken</span>
        </button>
        
        <!-- Future: Telefonnummern -->
        <button class="menu-button menu-button-disabled" disabled title="Demnächst verfügbar">
            <span class="material-icons">phone</span>
            <span class="menu-button-text">Telefonnummern</span>
        </button>
        
        <!-- Future: Online Karte -->
        <button class="menu-button menu-button-disabled" disabled title="Demnächst verfügbar">
            <span class="material-icons">map</span>
            <span class="menu-button-text">Online Karte</span>
        </button>
        
        <!-- Future: Gefahrenmatrix -->
        <button class="menu-button menu-button-disabled" disabled title="Demnächst verfügbar">
            <span class="material-icons">warning</span>
            <span class="menu-button-text">Gefahrenmatrix</span>
        </button>
        
        <!-- Future: Gefahrstoffkennzeichen -->
        <button class="menu-button menu-button-disabled" disabled title="Demnächst verfügbar">
            <span class="material-icons">science</span>
            <span class="menu-button-text">Gefahrstoffe</span>
        </button>
    </div>
    
    <?php if ($isAdmin): ?>
    <div style="margin-top: 3rem;">
        <h3 style="margin-bottom: 1.5rem; text-align: center; color: var(--text-primary);">Administration</h3>
        
        <div class="menu-grid">
            <!-- Einsatzkräfte verwalten -->
            <button class="menu-button menu-button-admin" onclick="window.feuerwehrApp.navigateTo('personnel')">
                <span class="material-icons">people</span>
                <span class="menu-button-text">Einsatzkräfte</span>
            </button>
            
            <!-- Fahrzeuge verwalten -->
            <button class="menu-button menu-button-admin" onclick="window.feuerwehrApp.navigateTo('vehicles')">
                <span class="material-icons">local_shipping</span>
                <span class="menu-button-text">Fahrzeuge</span>
            </button>
            
            <!-- Formulardaten -->
            <button class="menu-button menu-button-admin menu-button-disabled" disabled title="Demnächst verfügbar">
                <span class="material-icons">folder</span>
                <span class="menu-button-text">Formulardaten</span>
            </button>
            
            <!-- Benutzerverwaltung -->
            <button class="menu-button menu-button-admin" onclick="window.feuerwehrApp.navigateTo('users')">
                <span class="material-icons">admin_panel_settings</span>
                <span class="menu-button-text">Benutzer</span>
            </button>
        </div>
    </div>
    <?php endif; ?>
</div>
