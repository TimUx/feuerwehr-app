<?php
/**
 * Attendance List Form (Anwesenheitsliste)
 */

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../datastore.php';

Auth::requireOperator();

$user = Auth::getUser();
$hasGlobalAccess = Auth::hasGlobalAccess();
$userLocationId = Auth::getUserLocationId();

$personnel = DataStore::getPersonnelByLocation($hasGlobalAccess ? null : $userLocationId);
$locations = DataStore::getLocations();

// Check if we're editing an existing record
$editMode = false;
$editRecord = null;
if (isset($_GET['edit'])) {
    $editId = $_GET['edit'];
    $allRecords = DataStore::getAttendanceRecords();
    foreach ($allRecords as $record) {
        if ($record['id'] === $editId) {
            $editRecord = $record;
            $editMode = true;
            break;
        }
    }
}
?>

<div class="card">
    <div class="card-header">
        <span class="material-icons">fact_check</span>
        <?php echo $editMode ? 'Anwesenheitsliste bearbeiten' : 'Anwesenheitsliste'; ?>
    </div>
    <div class="card-content">
        <!-- Offline Support Banner -->
        <div class="offline-form-banner" id="offline-banner" style="display: none;">
            <span class="material-icons">cloud_off</span>
            <div class="offline-form-banner-text">
                <strong>Offline-Modus</strong>
                Formulare können offline ausgefüllt werden und werden automatisch gesendet, sobald Sie wieder online sind.
            </div>
        </div>
        
        <?php if ($editMode): ?>
        <div class="alert alert-info" style="margin-bottom: 1rem; padding: 0.75rem; background-color: #e3f2fd; border-left: 4px solid #2196f3; border-radius: 4px;">
            <strong>Bearbeitungsmodus:</strong> Sie bearbeiten eine vorhandene Anwesenheitsliste.
        </div>
        <?php endif; ?>
        
        <form id="attendance-form" method="POST" action="/src/php/forms/submit_attendance.php" enctype="multipart/form-data">
            <?php if ($editMode): ?>
            <input type="hidden" name="record_id" value="<?php echo htmlspecialchars($editRecord['id']); ?>">
            <?php endif; ?>
            
            <h3 style="margin-top: 0;">Veranstaltungsdaten</h3>
            
            <?php if ($hasGlobalAccess): ?>
            <div class="form-group">
                <label class="form-label" for="standort-filter">Einsatzabteilung / Standort *</label>
                <select id="standort-filter" name="standort" class="form-input" required>
                    <option value="">-- Standort auswählen --</option>
                    <?php foreach ($locations as $location): ?>
                    <option value="<?php echo htmlspecialchars($location['id']); ?>"><?php echo htmlspecialchars($location['name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <small style="color: var(--text-secondary); display: block; margin-top: 0.25rem;">
                    Je nach Auswahl werden die entsprechenden Übungsleiter und Einsatzkräfte angezeigt
                </small>
            </div>
            <?php else: ?>
            <input type="hidden" id="standort-filter" name="standort" value="<?php echo htmlspecialchars($userLocationId); ?>">
            <div class="form-group">
                <label class="form-label">Einsatzabteilung / Standort</label>
                <input type="text" class="form-input" value="<?php echo htmlspecialchars(DataStore::getLocationById($userLocationId)['name'] ?? 'Unbekannt'); ?>" readonly>
                <small style="color: var(--text-secondary); display: block; margin-top: 0.25rem;">
                    Ihr zugewiesener Standort
                </small>
            </div>
            <?php endif; ?>
            
            <div class="form-group">
                <label class="form-label" for="datum">Datum *</label>
                <input type="date" id="datum" name="datum" class="form-input" required>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="von">Von (Uhrzeit) *</label>
                <input type="time" id="von" name="von" class="form-input" required>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="bis">Bis (Uhrzeit) *</label>
                <input type="time" id="bis" name="bis" class="form-input" required>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="thema">Thema *</label>
                <input type="text" id="thema" name="thema" class="form-input" placeholder="z.B. Atemschutzübung, Erste Hilfe, Technische Übung" required>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="anmerkungen">Anmerkungen</label>
                <textarea id="anmerkungen" name="anmerkungen" class="form-textarea" placeholder="Weitere Informationen zur Veranstaltung"></textarea>
            </div>
            
            <h3>Übungsleiter</h3>
            
            <div class="form-group">
                <label class="form-label" for="uebungsleiter-select">Übungsleiter aus Liste auswählen *</label>
                <select id="uebungsleiter-select" name="uebungsleiter_select[]" class="form-select leader-select" multiple size="5">
                    <?php 
                    // Filter personnel with "Ausbilder" checkbox set
                    foreach ($personnel as $person): 
                        $isInstructor = !empty($person['is_instructor']) && $person['is_instructor'];
                        if ($isInstructor):
                    ?>
                        <option value="<?php echo htmlspecialchars($person['name']); ?>" data-location-id="<?php echo htmlspecialchars($person['location_id'] ?? ''); ?>">
                            <?php echo htmlspecialchars($person['name']); ?>
                            <?php if (!empty($person['qualifications'])): ?>
                                (<?php echo implode(', ', array_map('htmlspecialchars', $person['qualifications'])); ?>)
                            <?php endif; ?>
                        </option>
                    <?php 
                        endif;
                    endforeach; 
                    ?>
                </select>
                <small style="color: var(--text-secondary); display: block; margin-top: 0.25rem;">
                    Halten Sie Strg (Windows) oder Cmd (Mac) gedrückt, um mehrere auszuwählen
                </small>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="uebungsleiter-andere">Oder andere Übungsleiter eingeben (Freitext) *</label>
                <input type="text" id="uebungsleiter-andere" name="uebungsleiter_andere" class="form-input leader-input" placeholder="Namen mit Komma trennen, z.B. Max Mustermann, Anna Schmidt">
                <small style="color: var(--text-secondary); display: block; margin-top: 0.25rem;">
                    Für Übungsleiter, die nicht in der Liste sind (mindestens Dropdown ODER Freitext erforderlich)
                </small>
            </div>
            
            <h3>Dauer</h3>
            
            <div class="form-group">
                <label class="form-label" for="dauer">Dauer (in Minuten) *</label>
                <input type="number" id="dauer" name="dauer" class="form-input" readonly required>
                <small style="color: var(--text-secondary); display: block; margin-top: 0.25rem;">
                    Wird automatisch berechnet aus Von/Bis Zeit
                </small>
            </div>
            
            <h3>Dateiupload</h3>
            
            <div class="form-group">
                <label class="form-label" for="datei">Dateianhang (optional)</label>
                <input type="file" id="datei" name="datei" class="form-input" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                <small style="color: var(--text-secondary); display: block; margin-top: 0.25rem;">
                    PDF, Bilder oder Dokumente (max. 10MB)
                </small>
            </div>
            
            <h3>Teilnehmer</h3>
            
            <div class="form-group">
                <label class="form-label">Wählen Sie die anwesenden Teilnehmer aus *</label>
                <div style="margin-bottom: 0.5rem;">
                    <button type="button" class="btn btn-secondary" onclick="selectAllAttendees()">
                        <span class="material-icons">check_box</span>
                        Alle auswählen
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="deselectAllAttendees()">
                        <span class="material-icons">check_box_outline_blank</span>
                        Alle abwählen
                    </button>
                </div>
                
                <?php if (empty($personnel)): ?>
                    <p style="color: var(--text-secondary);">Keine Einsatzkräfte vorhanden. Bitte zuerst Einsatzkräfte anlegen.</p>
                <?php else: ?>
                    <div id="attendees-list">
                        <?php foreach ($personnel as $person): ?>
                        <div class="form-check" data-location-id="<?php echo htmlspecialchars($person['location_id'] ?? ''); ?>">
                            <input type="checkbox" id="attendee-<?php echo $person['id']; ?>" name="teilnehmer[]" value="<?php echo $person['id']; ?>" class="form-check-input attendee-checkbox">
                            <label for="attendee-<?php echo $person['id']; ?>" class="form-check-label">
                                <?php echo htmlspecialchars($person['name']); ?>
                                <?php if (!empty($person['qualifications'])): ?>
                                    <?php foreach ($person['qualifications'] as $qual): ?>
                                        <span class="badge badge-info" style="font-size: 0.7rem;"><?php echo htmlspecialchars($qual); ?></span>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div style="margin-top: 1rem; padding: 0.75rem; background-color: var(--bg-secondary); border-radius: 4px;">
                        <strong>Teilnehmeranzahl (Gesamt):</strong> <span id="total-count">0</span>
                        <small style="display: block; color: var(--text-secondary); margin-top: 0.25rem;">
                            Übungsleiter + Teilnehmer
                        </small>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="form-group mt-3">
                <button type="submit" class="btn btn-primary">
                    <span class="material-icons">send</span>
                    Liste absenden
                </button>
                <button type="reset" class="btn btn-secondary">
                    <span class="material-icons">refresh</span>
                    Zurücksetzen
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Set default date to today
document.getElementById('datum').valueAsDate = new Date();

// Filter instructors and personnel by selected location
function filterByLocation() {
    const selectedLocationId = document.getElementById('standort-filter').value;
    
    // Filter instructors in dropdown
    const instructorOptions = document.querySelectorAll('#uebungsleiter-select option');
    let visibleInstructors = 0;
    instructorOptions.forEach(option => {
        const optionLocationId = option.getAttribute('data-location-id') || '';
        if (!selectedLocationId || optionLocationId === selectedLocationId) {
            option.style.display = '';
            visibleInstructors++;
        } else {
            option.style.display = 'none';
            option.selected = false; // Deselect hidden options
        }
    });
    
    // Filter attendees in checkboxes
    const attendeeItems = document.querySelectorAll('#attendees-list .form-check');
    let visibleAttendees = 0;
    attendeeItems.forEach(item => {
        const itemLocationId = item.getAttribute('data-location-id') || '';
        if (!selectedLocationId || itemLocationId === selectedLocationId) {
            item.style.display = '';
            visibleAttendees++;
        } else {
            item.style.display = 'none';
            // Uncheck hidden attendees
            const checkbox = item.querySelector('.attendee-checkbox');
            if (checkbox) checkbox.checked = false;
        }
    });
    
    // Update count after filtering
    updateTotalCount();
    
    // Show alert if no items are available for the selected location
    if (selectedLocationId && window.feuerwehrApp) {
        if (visibleInstructors === 0) {
            window.feuerwehrApp.showAlert('warning', 'Keine Übungsleiter für den ausgewählten Standort verfügbar. Bitte verwenden Sie das Freitext-Feld.');
        }
        if (visibleAttendees === 0) {
            window.feuerwehrApp.showAlert('warning', 'Keine Einsatzkräfte für den ausgewählten Standort verfügbar. Bitte legen Sie zuerst Einsatzkräfte für diesen Standort an.');
        }
    }
}

// Add event listener for location filter
document.getElementById('standort-filter').addEventListener('change', filterByLocation);

// Initialize filtering on page load (for Global Admin with pre-selected location)
const standortFilter = document.getElementById('standort-filter');
if (standortFilter && standortFilter.value) {
    filterByLocation();
}

// Calculate duration automatically
function calculateDuration() {
    const vonInput = document.getElementById('von');
    const bisInput = document.getElementById('bis');
    const dauerInput = document.getElementById('dauer');
    
    if (vonInput.value && bisInput.value) {
        const von = vonInput.value.split(':');
        const bis = bisInput.value.split(':');
        
        const vonMinutes = parseInt(von[0]) * 60 + parseInt(von[1]);
        const bisMinutes = parseInt(bis[0]) * 60 + parseInt(bis[1]);
        
        let duration = bisMinutes - vonMinutes;
        
        // Handle overnight events
        if (duration < 0) {
            duration += 24 * 60;
        }
        
        dauerInput.value = duration;
    }
}

// Add event listeners for time inputs
document.getElementById('von').addEventListener('change', calculateDuration);
document.getElementById('bis').addEventListener('change', calculateDuration);

// Update total participant count (leaders + attendees)
function updateTotalCount() {
    // Count selected leaders from dropdown
    const leaderSelect = document.getElementById('uebungsleiter-select');
    const selectedLeaders = Array.from(leaderSelect.selectedOptions).length;
    
    // Count leaders from text field (split by comma)
    const leaderText = document.getElementById('uebungsleiter-andere').value.trim();
    const textLeaders = leaderText ? leaderText.split(',').filter(name => name.trim()).length : 0;
    
    // Count selected attendees
    const attendees = document.querySelectorAll('.attendee-checkbox:checked').length;
    
    // Total is all unique people (leaders + attendees)
    const total = selectedLeaders + textLeaders + attendees;
    
    document.getElementById('total-count').textContent = total;
}

// Select all attendees
function selectAllAttendees() {
    document.querySelectorAll('.attendee-checkbox').forEach(cb => {
        cb.checked = true;
    });
    updateTotalCount();
}

// Deselect all attendees
function deselectAllAttendees() {
    document.querySelectorAll('.attendee-checkbox').forEach(cb => {
        cb.checked = false;
    });
    updateTotalCount();
}

// Listen for changes
document.getElementById('uebungsleiter-select').addEventListener('change', updateTotalCount);
document.getElementById('uebungsleiter-andere').addEventListener('input', updateTotalCount);
document.querySelectorAll('.attendee-checkbox').forEach(cb => {
    cb.addEventListener('change', updateTotalCount);
});

// Initialize count
updateTotalCount();

// Form submission
document.getElementById('attendance-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    
    // Validate that at least one leader (from select or text) and one attendee is selected
    const leadersSelect = formData.getAll('uebungsleiter_select[]');
    const leadersOther = formData.get('uebungsleiter_andere');
    const attendees = formData.getAll('teilnehmer[]');
    
    if (leadersSelect.length === 0 && (!leadersOther || leadersOther.trim() === '')) {
        window.feuerwehrApp.showAlert('error', 'Bitte wählen Sie mindestens einen Übungsleiter aus oder geben Sie einen Namen ein');
        return;
    }
    
    if (attendees.length === 0) {
        window.feuerwehrApp.showAlert('error', 'Bitte wählen Sie mindestens einen Teilnehmer aus');
        return;
    }
    
    try {
        const response = await fetch('/src/php/forms/submit_attendance.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            window.feuerwehrApp.showAlert('success', result.message);
            e.target.reset();
            document.getElementById('datum').valueAsDate = new Date();
            updateTotalCount();
        } else {
            window.feuerwehrApp.showAlert('error', result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        window.feuerwehrApp.showAlert('error', 'Fehler beim Absenden der Liste');
    }
});

// Pre-fill form if in edit mode
<?php if ($editMode && $editRecord): ?>
(function() {
    const record = <?php echo json_encode($editRecord); ?>;
    
    // Pre-fill basic fields
    if (record.standort || record.location_id) {
        const locationId = record.standort || record.location_id;
        const standortField = document.getElementById('standort-filter');
        if (standortField) {
            standortField.value = locationId;
            // Trigger location filter
            filterByLocation();
        }
    }
    
    if (record.datum) {
        document.getElementById('datum').value = record.datum;
    }
    
    if (record.von) {
        document.getElementById('von').value = record.von;
    }
    
    if (record.bis) {
        document.getElementById('bis').value = record.bis;
    }
    
    if (record.thema) {
        document.getElementById('thema').value = record.thema;
    }
    
    if (record.anmerkungen) {
        document.getElementById('anmerkungen').value = record.anmerkungen;
    }
    
    // Pre-fill leaders
    if (record.uebungsleiter && Array.isArray(record.uebungsleiter)) {
        const leaderSelect = document.getElementById('uebungsleiter-select');
        const leaderOptions = leaderSelect.options;
        
        for (let i = 0; i < leaderOptions.length; i++) {
            const option = leaderOptions[i];
            if (record.uebungsleiter.includes(option.value)) {
                option.selected = true;
            }
        }
        
        // If there are leaders not in the dropdown, put them in the text field
        const leadersInDropdown = Array.from(leaderOptions).map(opt => opt.value);
        const leadersNotInDropdown = record.uebungsleiter.filter(leader => !leadersInDropdown.includes(leader));
        if (leadersNotInDropdown.length > 0) {
            document.getElementById('uebungsleiter-andere').value = leadersNotInDropdown.join(', ');
        }
    }
    
    // Pre-fill attendees
    if (record.teilnehmer && Array.isArray(record.teilnehmer)) {
        record.teilnehmer.forEach(attendeeId => {
            const checkbox = document.getElementById('attendee-' + attendeeId);
            if (checkbox) {
                checkbox.checked = true;
            }
        });
        updateTotalCount();
    }
})();
<?php endif; ?>

<script>
// Initialize offline banner using shared utility
if (typeof initOfflineBanner === 'function') {
  initOfflineBanner('offline-banner');
}
</script>
