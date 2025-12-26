<?php
/**
 * Form Data Page - View saved form submissions
 * Accessible to operators and admins
 */

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../datastore.php';

Auth::requireOperator();

$attendanceRecords = DataStore::getAttendanceRecords();
$missionReports = DataStore::getMissionReports();
$personnel = DataStore::getPersonnel();
$vehicles = DataStore::getVehicles();

// Sort by date, most recent first
usort($attendanceRecords, function($a, $b) {
    return strcmp($b['date'] ?? '', $a['date'] ?? '');
});

usort($missionReports, function($a, $b) {
    return strcmp($b['date'] ?? '', $a['date'] ?? '');
});

// Helper function to get personnel names
function getPersonnelNames($personnelIds, $allPersonnel) {
    $names = [];
    foreach ($personnelIds as $id) {
        foreach ($allPersonnel as $person) {
            if ($person['id'] === $id) {
                $names[] = $person['name'];
                break;
            }
        }
    }
    return $names;
}

// Helper function to get vehicle names
function getVehicleNames($vehicleIds, $allVehicles) {
    $names = [];
    foreach ($vehicleIds as $id) {
        foreach ($allVehicles as $vehicle) {
            if ($vehicle['id'] === $id) {
                $names[] = $vehicle['callsign'] . ' (' . $vehicle['type'] . ')';
                break;
            }
        }
    }
    return $names;
}
?>

<div class="card">
    <div class="card-header">
        <span class="material-icons">folder</span>
        Formulardaten
    </div>
    <div class="card-content">
        <p style="color: var(--text-secondary); margin-bottom: 1.5rem;">
            Hier finden Sie alle gespeicherten Formulardaten (Anwesenheitslisten und Einsatzberichte).
        </p>
        
        <!-- Tabs -->
        <div style="display: flex; gap: 0.5rem; margin-bottom: 1.5rem; border-bottom: 2px solid var(--border-color);">
            <button class="tab-button active" data-tab="attendance" onclick="switchTab('attendance')" style="padding: 0.75rem 1.5rem; border: none; background: none; cursor: pointer; font-size: 1rem; border-bottom: 3px solid var(--primary); margin-bottom: -2px;">
                Anwesenheitslisten
            </button>
            <button class="tab-button" data-tab="missions" onclick="switchTab('missions')" style="padding: 0.75rem 1.5rem; border: none; background: none; cursor: pointer; font-size: 1rem; color: var(--text-secondary);">
                Einsatzberichte
            </button>
        </div>
        
        <!-- Attendance Records Tab -->
        <div id="attendance-tab" class="tab-content">
            <h3 style="margin-top: 0; margin-bottom: 1rem;">Anwesenheitslisten</h3>
            <?php if (empty($attendanceRecords)): ?>
                <p class="text-center" style="color: var(--text-secondary);">Keine Anwesenheitslisten vorhanden.</p>
            <?php else: ?>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Datum</th>
                                <th>Art</th>
                                <th>Beschreibung</th>
                                <th>Dauer</th>
                                <th>Teilnehmer</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($attendanceRecords as $record): 
                                $attendeeNames = getPersonnelNames($record['attendees'] ?? [], $personnel);
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($record['date'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($record['type'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($record['description'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($record['duration_hours'] ?? 0); ?> h</td>
                                <td>
                                    <?php if (!empty($attendeeNames)): ?>
                                        <details>
                                            <summary><?php echo count($attendeeNames); ?> Teilnehmer</summary>
                                            <ul style="margin: 0.5rem 0; padding-left: 1.5rem;">
                                                <?php foreach ($attendeeNames as $name): ?>
                                                    <li><?php echo htmlspecialchars($name); ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </details>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Mission Reports Tab -->
        <div id="missions-tab" class="tab-content" style="display: none;">
            <h3 style="margin-top: 0; margin-bottom: 1rem;">Einsatzberichte</h3>
            <?php if (empty($missionReports)): ?>
                <p class="text-center" style="color: var(--text-secondary);">Keine Einsatzberichte vorhanden.</p>
            <?php else: ?>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Datum</th>
                                <th>Einsatzart</th>
                                <th>Ort</th>
                                <th>Beschreibung</th>
                                <th>Dauer</th>
                                <th>Fahrzeuge</th>
                                <th>Teilnehmer</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($missionReports as $report): 
                                $participantNames = getPersonnelNames($report['participants'] ?? [], $personnel);
                                $vehicleNames = getVehicleNames($report['vehicles'] ?? [], $vehicles);
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($report['date'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($report['mission_type'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($report['location'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($report['description'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($report['duration_hours'] ?? 0); ?> h</td>
                                <td>
                                    <?php if (!empty($vehicleNames)): ?>
                                        <details>
                                            <summary><?php echo count($vehicleNames); ?> Fahrzeuge</summary>
                                            <ul style="margin: 0.5rem 0; padding-left: 1.5rem;">
                                                <?php foreach ($vehicleNames as $name): ?>
                                                    <li><?php echo htmlspecialchars($name); ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </details>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($participantNames)): ?>
                                        <details>
                                            <summary><?php echo count($participantNames); ?> Teilnehmer</summary>
                                            <ul style="margin: 0.5rem 0; padding-left: 1.5rem;">
                                                <?php foreach ($participantNames as $name): ?>
                                                    <li><?php echo htmlspecialchars($name); ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </details>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function switchTab(tabName) {
    // Hide all tab contents
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.style.display = 'none';
    });
    
    // Remove active class from all buttons
    document.querySelectorAll('.tab-button').forEach(btn => {
        btn.classList.remove('active');
        btn.style.borderBottom = 'none';
        btn.style.color = 'var(--text-secondary)';
    });
    
    // Show selected tab
    document.getElementById(tabName + '-tab').style.display = 'block';
    
    // Add active class to clicked button
    const activeBtn = document.querySelector(`[data-tab="${tabName}"]`);
    activeBtn.classList.add('active');
    activeBtn.style.borderBottom = '3px solid var(--primary)';
    activeBtn.style.color = 'var(--text-primary)';
}
</script>
