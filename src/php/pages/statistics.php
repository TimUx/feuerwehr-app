<?php
/**
 * Statistics Page
 */

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../datastore.php';

Auth::requireAuth();

$user = Auth::getUser();
$userLocationId = Auth::getUserLocationId();
$isGlobalAdmin = Auth::isGlobalAdmin();

$currentYear = $_GET['year'] ?? date('Y');
$selectedPersonnel = $_GET['personnel'] ?? null;
$selectedLocation = $_GET['location'] ?? null;

$stats = DataStore::getStatistics($currentYear);

// Filter personnel and locations based on user's location
if (!$isGlobalAdmin && $userLocationId) {
    // Non-global users can only see their own location
    $personnel = DataStore::getPersonnelByLocation($userLocationId);
    $location = DataStore::getLocationById($userLocationId);
    $locations = $location ? [$location] : [];
    
    // Force filter to user's location if not already set or if different
    // Cast both to string for comparison since GET parameters are strings
    if (!$selectedLocation || (string)$selectedLocation !== (string)$userLocationId) {
        $selectedLocation = $userLocationId;
    }
} else {
    // Global admins can see all
    $personnel = DataStore::getPersonnel();
    $locations = DataStore::getLocations();
}

// Get personnel stats if selected
$personnelStats = null;
if ($selectedPersonnel) {
    $personnelStats = DataStore::getPersonnelStatistics($selectedPersonnel, $currentYear);
}

// Get location stats if selected
$locationStats = null;
if ($selectedLocation) {
    $locationStats = DataStore::getLocationStatistics($selectedLocation, $currentYear);
}
?>

<div class="card">
    <div class="card-header">
        <span class="material-icons">bar_chart</span>
        Statistiken
    </div>
    <div class="card-content">
        <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap;">
            <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 150px;">
                <label class="form-label" for="year">Jahr</label>
                <select id="year" name="year" class="form-select">
                    <?php for ($y = date('Y'); $y >= date('Y') - 10; $y--): ?>
                        <option value="<?php echo $y; ?>" <?php echo $currentYear == $y ? 'selected' : ''; ?>>
                            <?php echo $y; ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            
            <?php if ($isGlobalAdmin): ?>
            <div class="form-group" style="margin-bottom: 0; flex: 2; min-width: 200px;">
                <label class="form-label" for="location">Standort (optional)</label>
                <select id="location" name="location" class="form-select">
                    <option value="">-- Alle Standorte --</option>
                    <?php foreach ($locations as $location): ?>
                        <option value="<?php echo $location['id']; ?>" <?php echo $selectedLocation == $location['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($location['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            
            <div class="form-group" style="margin-bottom: 0; flex: 2; min-width: 200px;">
                <label class="form-label" for="personnel">Einsatzkraft (optional)</label>
                <select id="personnel" name="personnel" class="form-select">
                    <option value="">-- Alle Einsatzkräfte --</option>
                    <?php foreach ($personnel as $person): ?>
                        <option value="<?php echo $person['id']; ?>" <?php echo $selectedPersonnel == $person['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($person['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <?php if ($personnelStats): ?>
            <?php 
            $person = DataStore::getPersonnelById($selectedPersonnel);
            ?>
            <h3>Statistik für <?php echo htmlspecialchars($person['name']); ?> - <?php echo $currentYear; ?></h3>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-top: 1rem;">
                <div style="padding: 1.5rem; background-color: var(--bg-secondary); border-radius: 8px; text-align: center;">
                    <div style="color: var(--text-secondary); font-size: 0.875rem; margin-bottom: 0.5rem;">Übungsdienste</div>
                    <div style="font-size: 2.5rem; font-weight: 500; color: var(--success-color);">
                        <?php echo $personnelStats['training_sessions']; ?>
                    </div>
                </div>
                
                <div style="padding: 1.5rem; background-color: var(--bg-secondary); border-radius: 8px; text-align: center;">
                    <div style="color: var(--text-secondary); font-size: 0.875rem; margin-bottom: 0.5rem;">Übungsstunden</div>
                    <div style="font-size: 2.5rem; font-weight: 500; color: var(--success-color);">
                        <?php echo number_format($personnelStats['training_hours'], 1); ?>
                    </div>
                </div>
                
                <div style="padding: 1.5rem; background-color: var(--bg-secondary); border-radius: 8px; text-align: center;">
                    <div style="color: var(--text-secondary); font-size: 0.875rem; margin-bottom: 0.5rem;">Einsätze</div>
                    <div style="font-size: 2.5rem; font-weight: 500; color: var(--error-color);">
                        <?php echo $personnelStats['missions']; ?>
                    </div>
                </div>
                
                <div style="padding: 1.5rem; background-color: var(--bg-secondary); border-radius: 8px; text-align: center;">
                    <div style="color: var(--text-secondary); font-size: 0.875rem; margin-bottom: 0.5rem;">Einsatzstunden</div>
                    <div style="font-size: 2.5rem; font-weight: 500; color: var(--error-color);">
                        <?php echo number_format($personnelStats['mission_hours'], 1); ?>
                    </div>
                </div>
                
                <div style="padding: 1.5rem; background-color: var(--primary-color); color: white; border-radius: 8px; text-align: center; grid-column: span 2;">
                    <div style="font-size: 0.875rem; margin-bottom: 0.5rem; opacity: 0.9;">Gesamtstunden</div>
                    <div style="font-size: 3rem; font-weight: 500;">
                        <?php echo number_format($personnelStats['total_hours'], 1); ?>
                    </div>
                </div>
            </div>
            
        <?php elseif ($locationStats): ?>
            <?php 
            $location = DataStore::getLocationById($selectedLocation);
            ?>
            <h3>Statistik für Standort <?php echo htmlspecialchars($location['name']); ?> - <?php echo $currentYear; ?></h3>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-top: 1rem;">
                <div style="padding: 1.5rem; background-color: var(--bg-secondary); border-radius: 8px; text-align: center;">
                    <div style="color: var(--text-secondary); font-size: 0.875rem; margin-bottom: 0.5rem;">Übungsdienste</div>
                    <div style="font-size: 2.5rem; font-weight: 500; color: var(--success-color);">
                        <?php echo $locationStats['total_training_sessions']; ?>
                    </div>
                </div>
                
                <div style="padding: 1.5rem; background-color: var(--bg-secondary); border-radius: 8px; text-align: center;">
                    <div style="color: var(--text-secondary); font-size: 0.875rem; margin-bottom: 0.5rem;">Übungsstunden</div>
                    <div style="font-size: 2.5rem; font-weight: 500; color: var(--success-color);">
                        <?php echo number_format($locationStats['total_training_hours'], 1); ?>
                    </div>
                </div>
                
                <div style="padding: 1.5rem; background-color: var(--bg-secondary); border-radius: 8px; text-align: center;">
                    <div style="color: var(--text-secondary); font-size: 0.875rem; margin-bottom: 0.5rem;">Einsätze</div>
                    <div style="font-size: 2.5rem; font-weight: 500; color: var(--error-color);">
                        <?php echo $locationStats['total_missions']; ?>
                    </div>
                </div>
                
                <div style="padding: 1.5rem; background-color: var(--bg-secondary); border-radius: 8px; text-align: center;">
                    <div style="color: var(--text-secondary); font-size: 0.875rem; margin-bottom: 0.5rem;">Einsatzstunden</div>
                    <div style="font-size: 2.5rem; font-weight: 500; color: var(--error-color);">
                        <?php echo number_format($locationStats['total_mission_hours'], 1); ?>
                    </div>
                </div>
            </div>
            
            <div style="margin-top: 2rem; padding: 1.5rem; background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%); color: white; border-radius: 8px; text-align: center;">
                <div style="font-size: 0.875rem; margin-bottom: 0.5rem; opacity: 0.9;">Gesamtstunden (Übungen + Einsätze)</div>
                <div style="font-size: 3rem; font-weight: 500;">
                    <?php echo number_format(($locationStats['total_training_hours'] ?? 0) + ($locationStats['total_mission_hours'] ?? 0), 1); ?> h
                </div>
            </div>
            
        <?php else: ?>
            <h3>Gesamtstatistik Einsatzabteilung - <?php echo $currentYear; ?></h3>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-top: 1rem;">
                <div style="padding: 1.5rem; background-color: var(--bg-secondary); border-radius: 8px; text-align: center;">
                    <div style="color: var(--text-secondary); font-size: 0.875rem; margin-bottom: 0.5rem;">Übungsdienste</div>
                    <div style="font-size: 2.5rem; font-weight: 500; color: var(--success-color);">
                        <?php echo $stats['total_training_sessions']; ?>
                    </div>
                </div>
                
                <div style="padding: 1.5rem; background-color: var(--bg-secondary); border-radius: 8px; text-align: center;">
                    <div style="color: var(--text-secondary); font-size: 0.875rem; margin-bottom: 0.5rem;">Übungsstunden</div>
                    <div style="font-size: 2.5rem; font-weight: 500; color: var(--success-color);">
                        <?php echo number_format($stats['total_training_hours'], 1); ?>
                    </div>
                </div>
                
                <div style="padding: 1.5rem; background-color: var(--bg-secondary); border-radius: 8px; text-align: center;">
                    <div style="color: var(--text-secondary); font-size: 0.875rem; margin-bottom: 0.5rem;">Einsätze</div>
                    <div style="font-size: 2.5rem; font-weight: 500; color: var(--error-color);">
                        <?php echo $stats['total_missions']; ?>
                    </div>
                </div>
                
                <div style="padding: 1.5rem; background-color: var(--bg-secondary); border-radius: 8px; text-align: center;">
                    <div style="color: var(--text-secondary); font-size: 0.875rem; margin-bottom: 0.5rem;">Einsatzstunden</div>
                    <div style="font-size: 2.5rem; font-weight: 500; color: var(--error-color);">
                        <?php echo number_format($stats['total_mission_hours'], 1); ?>
                    </div>
                </div>
            </div>
            
            <div style="margin-top: 2rem; padding: 1.5rem; background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%); color: white; border-radius: 8px; text-align: center;">
                <div style="font-size: 0.875rem; margin-bottom: 0.5rem; opacity: 0.9;">Gesamtstunden (Übungen + Einsätze)</div>
                <div style="font-size: 3rem; font-weight: 500;">
                    <?php echo number_format($stats['total_training_hours'] + $stats['total_mission_hours'], 1); ?> h
                </div>
            </div>
        <?php endif; ?>
        
        <div style="margin-top: 2rem; padding: 1rem; background-color: var(--bg-secondary); border-radius: 4px;">
            <p style="margin: 0; color: var(--text-secondary); font-size: 0.875rem;">
                <span class="material-icons" style="font-size: 1rem; vertical-align: middle;">info</span>
                Die Statistiken basieren auf den erfassten Anwesenheitslisten und Einsatzberichten.
            </p>
        </div>
    </div>
</div>

<script>
// Handle filter changes without form submission
function updateStatistics() {
    const year = document.getElementById('year').value;
    const locationEl = document.getElementById('location');
    const location = locationEl ? locationEl.value : '';
    const personnel = document.getElementById('personnel').value;
    
    const params = { year: year };
    if (location) params.location = location;
    if (personnel) params.personnel = personnel;
    
    window.feuerwehrApp.navigateTo('statistics', params);
}

document.getElementById('year').addEventListener('change', updateStatistics);
const locationEl = document.getElementById('location');
if (locationEl) {
    locationEl.addEventListener('change', updateStatistics);
}
document.getElementById('personnel').addEventListener('change', updateStatistics);
</script>
