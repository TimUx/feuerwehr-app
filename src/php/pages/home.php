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
        
        <!-- Fahrzeuge -->
        <button class="menu-button" onclick="window.feuerwehrApp.navigateTo('vehicles')">
            <span class="material-icons">local_shipping</span>
            <span class="menu-button-text">Fahrzeuge</span>
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

<style>
.menu-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 1.5rem;
}

.menu-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 1.5rem;
}

@media (max-width: 768px) {
    .menu-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
    }
}

.menu-button {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    padding: 2rem 1rem;
    background: var(--bg-card);
    border: 2px solid var(--border-color);
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    min-height: 140px;
}

.menu-button:hover:not(:disabled) {
    transform: translateY(-4px);
    box-shadow: 0 6px 12px rgba(220, 38, 38, 0.2);
    border-color: var(--primary-color);
}

.menu-button:active:not(:disabled) {
    transform: translateY(-2px);
}

.menu-button .material-icons {
    font-size: 48px;
    color: var(--primary-color);
}

.menu-button-text {
    font-size: 1rem;
    font-weight: 500;
    color: var(--text-primary);
    text-align: center;
    line-height: 1.3;
}

.menu-button-admin {
    border-color: var(--info-color);
}

.menu-button-admin .material-icons {
    color: var(--info-color);
}

.menu-button-admin:hover:not(:disabled) {
    border-color: var(--info-color);
    box-shadow: 0 6px 12px rgba(59, 130, 246, 0.2);
}

.menu-button-disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.menu-button-disabled .material-icons,
.menu-button-disabled .menu-button-text {
    color: var(--text-secondary);
}
</style>
