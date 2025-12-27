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
$isGlobalUser = Auth::isAdmin() || Auth::isOperator() || empty($userLocationId);

// Get data filtered by location
if ($isGlobalUser) {
    $attendanceRecords = DataStore::getAttendanceRecords();
    $missionReports = DataStore::getMissionReports();
} else {
    $attendanceRecords = DataStore::getAttendanceRecordsByLocation($userLocationId);
    $missionReports = DataStore::getMissionReportsByLocation($userLocationId);
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
                                <th>Übungsleiter</th>
                                <th>Teilnehmer</th>
                                <th>Anmerkungen</th>
                                <th>Aktionen</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($attendanceRecords as $record): 
                                $attendeeNames = getPersonnelNames($record['attendees'] ?? [], $personnel);
                                $locationName = getLocationName($record['location_id'] ?? '', $locations);
                                $instructors = $record['uebungsleiter'] ?? [];
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
                                    <?php if (!empty($instructors) && is_array($instructors)): ?>
                                        <?php echo implode(', ', array_map('htmlspecialchars', $instructors)); ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
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
                                <td>
                                    <?php 
                                    $anmerkungen = $record['anmerkungen'] ?? '';
                                    if (!empty($anmerkungen)): 
                                        $preview = mb_substr($anmerkungen, 0, 50);
                                        if (mb_strlen($anmerkungen) > 50) {
                                            $preview .= '...';
                                        }
                                    ?>
                                        <details>
                                            <summary><?php echo htmlspecialchars($preview); ?></summary>
                                            <div style="margin-top: 0.5rem; white-space: pre-wrap;"><?php echo htmlspecialchars($anmerkungen); ?></div>
                                        </details>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
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

<style>
.btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
    margin: 0.125rem;
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
