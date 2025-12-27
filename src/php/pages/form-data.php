<?php
/**
 * Form Data Page - View saved form submissions
 * Accessible to operators and admins
 */

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../datastore.php';

Auth::requireOperator();

// Determine user's location access
$user = Auth::getUser();
$userLocationId = $user['location_id'] ?? null;
// Admins and operators have global access, regular users are restricted to their location
$isGlobalUser = Auth::isAdmin() || Auth::isOperator();

// Get data filtered by location
if ($isGlobalUser) {
    $attendanceRecords = DataStore::getAttendanceRecords();
    $missionReports = DataStore::getMissionReports();
} else {
    // Regular users see only their location's data (or nothing if no location assigned)
    $attendanceRecords = $userLocationId ? DataStore::getAttendanceRecordsByLocation($userLocationId) : [];
    $missionReports = $userLocationId ? DataStore::getMissionReportsByLocation($userLocationId) : [];
}

$personnel = DataStore::getPersonnel();
$vehicles = DataStore::getVehicles();
$locations = DataStore::getLocations();

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

// Helper function to get location name by ID
function getLocationName($locationId, $allLocations) {
    if (empty($locationId)) {
        return '-';
    }
    $location = DataStore::getLocationById($locationId);
    return $location ? $location['name'] : '-';
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
                                <th>Von</th>
                                <th>Bis</th>
                                <th>Dauer</th>
                                <th>Standort</th>
                                <th>Thema</th>
                                <th>Aktionen</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($attendanceRecords as $record): 
                                $attendeeNames = getPersonnelNames($record['attendees'] ?? [], $personnel);
                                $locationName = getLocationName($record['location_id'] ?? '', $locations);
                                $instructors = $record['uebungsleiter'] ?? [];
                                
                                // Prepare record data as JSON for details modal
                                $recordJson = htmlspecialchars(json_encode([
                                    'id' => $record['id'],
                                    'datum' => $record['datum'] ?? $record['date'] ?? '-',
                                    'von' => $record['von'] ?? '-',
                                    'bis' => $record['bis'] ?? '-',
                                    'dauer' => (!empty($record['dauer']) ? intval($record['dauer']) : (!empty($record['duration_hours']) ? intval($record['duration_hours'] * 60) : 0)),
                                    'standort' => $locationName,
                                    'thema' => $record['thema'] ?? $record['description'] ?? '-',
                                    'instructors' => $instructors,
                                    'attendees' => $attendeeNames,
                                    'anmerkungen' => $record['anmerkungen'] ?? ''
                                ]), ENT_QUOTES, 'UTF-8');
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($record['datum'] ?? $record['date'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($record['von'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($record['bis'] ?? '-'); ?></td>
                                <td>
                                    <?php 
                                    // Display duration - prefer 'dauer' field (in minutes), otherwise calculate from duration_hours
                                    $durationMin = 0;
                                    if (!empty($record['dauer'])) {
                                        $durationMin = intval($record['dauer']);
                                    } elseif (!empty($record['duration_hours'])) {
                                        $durationMin = intval($record['duration_hours'] * 60);
                                    }
                                    echo htmlspecialchars($durationMin) . ' min';
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($locationName); ?></td>
                                <td><?php echo htmlspecialchars($record['thema'] ?? $record['description'] ?? '-'); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-info" onclick='showAttendanceDetails(<?php echo $recordJson; ?>)' title="Details anzeigen">
                                        <span class="material-icons" style="font-size: 1rem;">info</span>
                                    </button>
                                    <button class="btn btn-sm btn-primary" onclick="resendAttendanceEmail('<?php echo htmlspecialchars($record['id']); ?>')" title="E-Mail erneut versenden">
                                        <span class="material-icons" style="font-size: 1rem;">email</span>
                                    </button>
                                    <button class="btn btn-sm btn-secondary" onclick="downloadAttendancePDF('<?php echo htmlspecialchars($record['id']); ?>')" title="PDF herunterladen">
                                        <span class="material-icons" style="font-size: 1rem;">download</span>
                                    </button>
                                    <button class="btn btn-sm btn-warning" onclick="editAttendanceRecord('<?php echo htmlspecialchars($record['id']); ?>')" title="Bearbeiten">
                                        <span class="material-icons" style="font-size: 1rem;">edit</span>
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="deleteAttendanceRecord('<?php echo htmlspecialchars($record['id']); ?>')" title="Löschen">
                                        <span class="material-icons" style="font-size: 1rem;">delete</span>
                                    </button>
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
                                <th>Aktionen</th>
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
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="resendMissionEmail('<?php echo htmlspecialchars($report['id']); ?>')" title="E-Mail erneut versenden">
                                        <span class="material-icons" style="font-size: 1rem;">email</span>
                                    </button>
                                    <button class="btn btn-sm btn-secondary" onclick="downloadMissionPDF('<?php echo htmlspecialchars($report['id']); ?>')" title="PDF herunterladen">
                                        <span class="material-icons" style="font-size: 1rem;">download</span>
                                    </button>
                                    <button class="btn btn-sm btn-warning" onclick="editMissionReport('<?php echo htmlspecialchars($report['id']); ?>')" title="Bearbeiten">
                                        <span class="material-icons" style="font-size: 1rem;">edit</span>
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="deleteMissionReport('<?php echo htmlspecialchars($report['id']); ?>')" title="Löschen">
                                        <span class="material-icons" style="font-size: 1rem;">delete</span>
                                    </button>
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

<!-- Details Modal -->
<div id="detailsModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 800px;">
        <div class="modal-header">
            <h2 id="modalTitle">Details</h2>
            <button class="modal-close" onclick="closeDetailsModal()">&times;</button>
        </div>
        <div class="modal-body" id="modalBody">
            <!-- Content will be inserted here -->
        </div>
    </div>
</div>

<style>
.btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
    margin: 0.125rem;
}

.btn-info {
    background-color: #2196F3;
    color: white;
}

.btn-info:hover {
    background-color: #1976D2;
}

.btn-warning {
    background-color: #ff9800;
    color: white;
}

.btn-warning:hover {
    background-color: #f57c00;
}

.btn-danger {
    background-color: #f44336;
    color: white;
}

.btn-danger:hover {
    background-color: #d32f2f;
}

.table-container {
    overflow-x: auto;
}

.table th, .table td {
    white-space: nowrap;
}

.table td[colspan] {
    white-space: normal;
}

/* Modal styles */
.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.modal-content {
    background-color: white;
    border-radius: 8px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    width: 90%;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem;
    border-bottom: 1px solid var(--border-color);
}

.modal-header h2 {
    margin: 0;
    font-size: 1.5rem;
}

.modal-close {
    background: none;
    border: none;
    font-size: 2rem;
    cursor: pointer;
    color: var(--text-secondary);
    padding: 0;
    width: 2rem;
    height: 2rem;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-close:hover {
    color: var(--text-primary);
}

.modal-body {
    padding: 1.5rem;
}

.detail-section {
    margin-bottom: 1.5rem;
}

.detail-section h3 {
    margin-top: 0;
    margin-bottom: 0.75rem;
    font-size: 1.25rem;
    color: var(--primary);
}

.detail-row {
    display: flex;
    padding: 0.5rem 0;
    border-bottom: 1px solid var(--border-color);
}

.detail-label {
    font-weight: bold;
    width: 200px;
    color: var(--text-secondary);
}

.detail-value {
    flex: 1;
    color: var(--text-primary);
}

.detail-list {
    list-style: none;
    padding: 0;
    margin: 0.5rem 0;
}

.detail-list li {
    padding: 0.25rem 0;
}

</style>

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

function showAttendanceDetails(record) {
    const modal = document.getElementById('detailsModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalBody = document.getElementById('modalBody');
    
    modalTitle.textContent = 'Anwesenheitsliste Details';
    
    let instructorsHtml = '';
    if (record.instructors && record.instructors.length > 0) {
        instructorsHtml = '<ul class="detail-list">';
        record.instructors.forEach(instructor => {
            instructorsHtml += `<li>${escapeHtml(instructor)}</li>`;
        });
        instructorsHtml += '</ul>';
    } else {
        instructorsHtml = '-';
    }
    
    let attendeesHtml = '';
    if (record.attendees && record.attendees.length > 0) {
        attendeesHtml = '<ul class="detail-list">';
        record.attendees.forEach(attendee => {
            attendeesHtml += `<li>${escapeHtml(attendee)}</li>`;
        });
        attendeesHtml += '</ul>';
    } else {
        attendeesHtml = '-';
    }
    
    modalBody.innerHTML = `
        <div class="detail-section">
            <h3>Grunddaten</h3>
            <div class="detail-row">
                <div class="detail-label">Datum:</div>
                <div class="detail-value">${escapeHtml(record.datum)}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Zeitraum:</div>
                <div class="detail-value">${escapeHtml(record.von)} - ${escapeHtml(record.bis)}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Dauer:</div>
                <div class="detail-value">${record.dauer} Minuten</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Standort:</div>
                <div class="detail-value">${escapeHtml(record.standort)}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Thema:</div>
                <div class="detail-value">${escapeHtml(record.thema)}</div>
            </div>
        </div>
        
        <div class="detail-section">
            <h3>Übungsleiter</h3>
            ${instructorsHtml}
        </div>
        
        <div class="detail-section">
            <h3>Teilnehmer (${record.attendees ? record.attendees.length : 0})</h3>
            ${attendeesHtml}
        </div>
        
        <div class="detail-section">
            <h3>Anmerkungen</h3>
            <div style="white-space: pre-wrap;">${escapeHtml(record.anmerkungen || '-')}</div>
        </div>
    `;
    
    modal.style.display = 'flex';
}

function closeDetailsModal() {
    document.getElementById('detailsModal').style.display = 'none';
}

function escapeHtml(text) {
    if (!text && text !== 0) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Close modal when clicking outside
document.addEventListener('click', function(event) {
    const modal = document.getElementById('detailsModal');
    if (event.target === modal) {
        closeDetailsModal();
    }
});

async function resendAttendanceEmail(recordId) {
    if (!confirm('Möchten Sie die E-Mail für diese Anwesenheitsliste erneut versenden?')) {
        return;
    }
    
    try {
        const response = await fetch('/src/php/api/resend_form_email.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                type: 'attendance',
                id: recordId
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            window.feuerwehrApp.showAlert('success', result.message || 'E-Mail wurde erfolgreich versendet');
        } else {
            window.feuerwehrApp.showAlert('error', result.message || 'Fehler beim Versenden der E-Mail');
        }
    } catch (error) {
        console.error('Error:', error);
        window.feuerwehrApp.showAlert('error', 'Fehler beim Versenden der E-Mail');
    }
}

async function downloadAttendancePDF(recordId) {
    try {
        const response = await fetch('/src/php/api/generate_form_pdf.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                type: 'attendance',
                id: recordId
            })
        });
        
        if (!response.ok) {
            throw new Error('Server error');
        }
        
        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `Anwesenheitsliste_${recordId}.pdf`;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
        
        window.feuerwehrApp.showAlert('success', 'PDF wurde heruntergeladen');
    } catch (error) {
        console.error('Error:', error);
        window.feuerwehrApp.showAlert('error', 'Fehler beim Herunterladen des PDFs');
    }
}

async function resendMissionEmail(reportId) {
    if (!confirm('Möchten Sie die E-Mail für diesen Einsatzbericht erneut versenden?')) {
        return;
    }
    
    try {
        const response = await fetch('/src/php/api/resend_form_email.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                type: 'mission',
                id: reportId
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            window.feuerwehrApp.showAlert('success', result.message || 'E-Mail wurde erfolgreich versendet');
        } else {
            window.feuerwehrApp.showAlert('error', result.message || 'Fehler beim Versenden der E-Mail');
        }
    } catch (error) {
        console.error('Error:', error);
        window.feuerwehrApp.showAlert('error', 'Fehler beim Versenden der E-Mail');
    }
}

async function downloadMissionPDF(reportId) {
    try {
        const response = await fetch('/src/php/api/generate_form_pdf.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                type: 'mission',
                id: reportId
            })
        });
        
        if (!response.ok) {
            throw new Error('Server error');
        }
        
        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `Einsatzbericht_${reportId}.pdf`;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
        
        window.feuerwehrApp.showAlert('success', 'PDF wurde heruntergeladen');
    } catch (error) {
        console.error('Error:', error);
        window.feuerwehrApp.showAlert('error', 'Fehler beim Herunterladen des PDFs');
    }
}

async function editAttendanceRecord(recordId) {
    // Redirect to attendance page with edit parameter
    window.location.href = '/index.php?page=attendance&edit=' + recordId;
}

async function deleteAttendanceRecord(recordId) {
    if (!confirm('Möchten Sie diese Anwesenheitsliste wirklich löschen? Diese Aktion kann nicht rückgängig gemacht werden.')) {
        return;
    }
    
    try {
        const response = await fetch('/src/php/api/attendance.php', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                id: recordId
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            window.feuerwehrApp.showAlert('success', result.message || 'Anwesenheitsliste wurde gelöscht');
            // Reload page to refresh list
            setTimeout(() => window.location.reload(), 1000);
        } else {
            window.feuerwehrApp.showAlert('error', result.message || 'Fehler beim Löschen der Anwesenheitsliste');
        }
    } catch (error) {
        console.error('Error:', error);
        window.feuerwehrApp.showAlert('error', 'Fehler beim Löschen der Anwesenheitsliste');
    }
}

async function editMissionReport(reportId) {
    // Redirect to mission report page with edit parameter
    window.location.href = '/index.php?page=mission-report&edit=' + reportId;
}

async function deleteMissionReport(reportId) {
    if (!confirm('Möchten Sie diesen Einsatzbericht wirklich löschen? Diese Aktion kann nicht rückgängig gemacht werden.')) {
        return;
    }
    
    try {
        const response = await fetch('/src/php/api/mission-report.php', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                id: reportId
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            window.feuerwehrApp.showAlert('success', result.message || 'Einsatzbericht wurde gelöscht');
            // Reload page to refresh list
            setTimeout(() => window.location.reload(), 1000);
        } else {
            window.feuerwehrApp.showAlert('error', result.message || 'Fehler beim Löschen des Einsatzberichts');
        }
    } catch (error) {
        console.error('Error:', error);
        window.feuerwehrApp.showAlert('error', 'Fehler beim Löschen des Einsatzberichts');
    }
}
</script>
