<?php
/**
 * Dashboard Page
 */

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../datastore.php';

Auth::requireAuth();

$user = Auth::getUser();
$isAdmin = Auth::isAdmin();
$hasGlobalAccess = Auth::hasGlobalAccess();
$userLocationId = Auth::getUserLocationId();

// Get counts - filtered by location for location-restricted users
if ($hasGlobalAccess) {
    $personnelCount = count(DataStore::getPersonnel());
    $vehiclesCount = count(DataStore::getVehicles());
    $attendanceCount = count(DataStore::getAttendanceRecords());
    $missionsCount = count(DataStore::getMissionReports());
} else {
    $personnelCount = count(DataStore::getPersonnelByLocation($userLocationId));
    $vehiclesCount = count(DataStore::getVehiclesByLocation($userLocationId));
    // Show all attendance and mission records since they may span multiple locations
    $attendanceCount = count(DataStore::getAttendanceRecords());
    $missionsCount = count(DataStore::getMissionReports());
}

// Get current year statistics
$currentYear = date('Y');
$stats = DataStore::getStatistics($currentYear);
?>

<div class="card">
    <div class="card-header">
        <span class="material-icons">dashboard</span>
        Dashboard
    </div>
    <div class="card-content">
        <h3 style="margin-top: 0;">Willkommen, <?php echo htmlspecialchars($user['username']); ?>!</h3>
        <p>Rolle: <span class="badge badge-<?php echo $isAdmin ? 'primary' : 'info'; ?>"><?php echo htmlspecialchars($user['role']); ?></span></p>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem;">
    <div class="card">
        <div class="card-content">
            <div style="display: flex; align-items: center; gap: 1rem;">
                <span class="material-icons" style="font-size: 48px; color: var(--primary-color);">people</span>
                <div>
                    <div style="font-size: 2rem; font-weight: 500; color: var(--text-primary);"><?php echo $personnelCount; ?></div>
                    <div style="color: var(--text-secondary);">Einsatzkräfte</div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-content">
            <div style="display: flex; align-items: center; gap: 1rem;">
                <span class="material-icons" style="font-size: 48px; color: var(--info-color);">local_shipping</span>
                <div>
                    <div style="font-size: 2rem; font-weight: 500; color: var(--text-primary);"><?php echo $vehiclesCount; ?></div>
                    <div style="color: var(--text-secondary);">Fahrzeuge</div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-content">
            <div style="display: flex; align-items: center; gap: 1rem;">
                <span class="material-icons" style="font-size: 48px; color: var(--success-color);">fact_check</span>
                <div>
                    <div style="font-size: 2rem; font-weight: 500; color: var(--text-primary);"><?php echo $attendanceCount; ?></div>
                    <div style="color: var(--text-secondary);">Übungsdienste</div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-content">
            <div style="display: flex; align-items: center; gap: 1rem;">
                <span class="material-icons" style="font-size: 48px; color: var(--error-color);">local_fire_department</span>
                <div>
                    <div style="font-size: 2rem; font-weight: 500; color: var(--text-primary);"><?php echo $missionsCount; ?></div>
                    <div style="color: var(--text-secondary);">Einsätze</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        Statistik <?php echo $currentYear; ?>
    </div>
    <div class="card-content">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
            <div style="padding: 1rem; background-color: var(--bg-secondary); border-radius: 4px;">
                <div style="color: var(--text-secondary); font-size: 0.875rem;">Übungsdienste</div>
                <div style="font-size: 1.5rem; font-weight: 500; color: var(--text-primary);">
                    <?php echo $stats['total_training_sessions']; ?>
                </div>
            </div>
            
            <div style="padding: 1rem; background-color: var(--bg-secondary); border-radius: 4px;">
                <div style="color: var(--text-secondary); font-size: 0.875rem;">Übungsstunden</div>
                <div style="font-size: 1.5rem; font-weight: 500; color: var(--text-primary);">
                    <?php echo number_format($stats['total_training_hours'], 1); ?> h
                </div>
            </div>
            
            <div style="padding: 1rem; background-color: var(--bg-secondary); border-radius: 4px;">
                <div style="color: var(--text-secondary); font-size: 0.875rem;">Einsätze</div>
                <div style="font-size: 1.5rem; font-weight: 500; color: var(--text-primary);">
                    <?php echo $stats['total_missions']; ?>
                </div>
            </div>
            
            <div style="padding: 1rem; background-color: var(--bg-secondary); border-radius: 4px;">
                <div style="color: var(--text-secondary); font-size: 0.875rem;">Einsatzstunden</div>
                <div style="font-size: 1.5rem; font-weight: 500; color: var(--text-primary);">
                    <?php echo number_format($stats['total_mission_hours'], 1); ?> h
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        Schnellzugriff
    </div>
    <div class="card-content">
        <div style="display: flex; flex-wrap: wrap; gap: 1rem;">
            <a href="#" onclick="window.feuerwehrApp.navigateTo('attendance'); return false;" class="btn btn-primary">
                <span class="material-icons">fact_check</span>
                Neue Anwesenheitsliste
            </a>
            
            <a href="#" onclick="window.feuerwehrApp.navigateTo('mission-report'); return false;" class="btn btn-primary">
                <span class="material-icons">description</span>
                Neuer Einsatzbericht
            </a>
            
            <a href="#" onclick="window.feuerwehrApp.navigateTo('statistics'); return false;" class="btn btn-secondary">
                <span class="material-icons">bar_chart</span>
                Statistiken ansehen
            </a>
            
            <?php if ($isAdmin): ?>
            <a href="#" onclick="window.feuerwehrApp.navigateTo('personnel'); return false;" class="btn btn-secondary">
                <span class="material-icons">people</span>
                Einsatzkräfte verwalten
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>
