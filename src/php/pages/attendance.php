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
        
        <form id="attendance-form" method="POST" action="/src/php/forms/submit_attendance.php" enctype="multipart/form-data" novalidate>
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
                <label class="form-label">Übungsleiter aus Liste auswählen *</label>
                
                <?php 
                // Filter personnel with "Ausbilder" checkbox set
                $instructors = array_filter($personnel, function($person) {
                    return !empty($person['is_instructor']);
                });
                ?>
                
                <?php if (empty($instructors)): ?>
                    <p style="color: var(--text-secondary);">Keine Übungsleiter vorhanden. Bitte verwenden Sie das Freitext-Feld unten.</p>
                <?php else: ?>
                    <div id="instructors-list">
                        <?php foreach ($instructors as $person): ?>
                        <div class="form-check" data-location-id="<?php echo htmlspecialchars($person['location_id'] ?? ''); ?>">
                            <input type="checkbox" id="instructor-<?php echo $person['id']; ?>" name="uebungsleiter_select[]" value="<?php echo htmlspecialchars($person['name']); ?>" class="form-check-input instructor-checkbox">
                            <label for="instructor-<?php echo $person['id']; ?>" class="form-check-label">
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
                <?php endif; ?>
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
                        <?php foreach ($personnel as $person): 
                            $isInstructor = !empty($person['is_instructor']);
                        ?>
                        <div class="form-check" data-location-id="<?php echo htmlspecialchars($person['location_id'] ?? ''); ?>">
                            <input type="checkbox" id="attendee-<?php echo $person['id']; ?>" name="teilnehmer[]" value="<?php echo $person['id']; ?>" class="form-check-input attendee-checkbox">
                            <label for="attendee-<?php echo $person['id']; ?>" class="form-check-label">
                                <?php echo htmlspecialchars($person['name']); ?>
                                <?php if ($isInstructor): ?>
                                    <span class="material-icons" style="color: var(--primary); font-size: 1rem; vertical-align: middle;" title="Ausbilder">school</span>
                                <?php endif; ?>
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
    
    // Filter instructors in checkboxes
    const instructorItems = document.querySelectorAll('#instructors-list .form-check');
    let visibleInstructors = 0;
    instructorItems.forEach(item => {
        const itemLocationId = item.getAttribute('data-location-id') || '';
        if (!selectedLocationId || itemLocationId === selectedLocationId) {
            item.style.display = '';
            visibleInstructors++;
        } else {
            item.style.display = 'none';
            // Uncheck hidden instructors
            const checkbox = item.querySelector('.instructor-checkbox');
            if (checkbox) checkbox.checked = false;
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
(function() {
    const standortFilter = document.getElementById('standort-filter');
    if (standortFilter && standortFilter.value) {
        filterByLocation();
    }
})();

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
    // Count selected leaders from checkboxes
    const selectedLeaders = document.querySelectorAll('.instructor-checkbox:checked').length;
    
    // Count leaders from text field (split by comma)
    const leaderText = document.getElementById('uebungsleiter-andere').value.trim();
    const textLeaders = leaderText ? leaderText.split(',').filter(name => name.trim()).length : 0;
    
    // Count selected attendees
    const attendees = document.querySelectorAll('.attendee-checkbox:checked').length;
    
    // Total is all unique people (leaders + attendees)
    const total = selectedLeaders + textLeaders + attendees;
    
    document.getElementById('total-count').textContent = total;
}

// Select all attendees (only visible ones)
function selectAllAttendees() {
    document.querySelectorAll('.attendee-checkbox').forEach(cb => {
        // Only check visible checkboxes
        const formCheck = cb.closest('.form-check');
        if (formCheck && formCheck.style.display !== 'none') {
            cb.checked = true;
        }
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
document.querySelectorAll('.instructor-checkbox').forEach(cb => {
    cb.addEventListener('change', updateTotalCount);
});
document.getElementById('uebungsleiter-andere').addEventListener('input', updateTotalCount);
document.querySelectorAll('.attendee-checkbox').forEach(cb => {
    cb.addEventListener('change', updateTotalCount);
});

// Initialize count
updateTotalCount();

// Helper function to validate required fields and scroll to first error
function validateRequiredFields() {
    const form = document.getElementById('attendance-form');
    const requiredFields = form.querySelectorAll('[required]');
    
    for (const field of requiredFields) {
        // Skip hidden fields
        if (field.offsetParent === null) continue;
        
        // Check if field is empty
        if (!field.value || (field.type === 'checkbox' && !field.checked)) {
            // Special handling for checkbox groups
            if (field.type === 'checkbox') {
                continue; // Skip individual checkbox validation
            }
            
            // Scroll to the field
            field.scrollIntoView({ behavior: 'smooth', block: 'center' });
            
            // Highlight the field
            field.style.border = '2px solid var(--error-color)';
            setTimeout(() => {
                field.style.border = '';
            }, 3000);
            
            // Get field label
            const label = form.querySelector(`label[for="${field.id}"]`);
            const fieldName = label ? label.textContent : field.name;
            
            // Show modal error
            window.feuerwehrApp.showConfirmationModal(
                'error',
                'Pflichtfeld nicht ausgefüllt',
                `Bitte füllen Sie das Feld "${fieldName}" aus.`
            );
            
            return false;
        }
    }
    
    return true;
}

// Form submission
document.getElementById('attendance-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    // Validate required fields first
    if (!validateRequiredFields()) {
        return;
    }
    
    const formData = new FormData(e.target);
    
    // Validate that at least one leader (from select or text) and one attendee is selected
    const leadersSelect = formData.getAll('uebungsleiter_select[]');
    const leadersOther = formData.get('uebungsleiter_andere');
    const attendees = formData.getAll('teilnehmer[]');
    
    if (leadersSelect.length === 0 && (!leadersOther || leadersOther.trim() === '')) {
        // Scroll to instructor section
        const instructorSection = document.querySelector('h3:nth-of-type(2)'); // "Übungsleiter" heading
        if (instructorSection) {
            instructorSection.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        
        window.feuerwehrApp.showConfirmationModal(
            'error',
            'Übungsleiter fehlt',
            'Bitte wählen Sie mindestens einen Übungsleiter aus oder geben Sie einen Namen ein.'
        );
        return;
    }
    
    if (attendees.length === 0) {
        // Scroll to attendees section
        const attendeesSection = document.querySelector('h3:nth-of-type(4)'); // "Teilnehmer" heading
        if (attendeesSection) {
            attendeesSection.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        
        window.feuerwehrApp.showConfirmationModal(
            'error',
            'Teilnehmer fehlen',
            'Bitte wählen Sie mindestens einen Teilnehmer aus.'
        );
        return;
    }
    
    // Disable submit button during submission
    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalBtnText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="material-icons">hourglass_empty</span> Wird gesendet...';
    
    try {
        const response = await fetch('/src/php/forms/submit_attendance.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Show success modal
            window.feuerwehrApp.showConfirmationModal(
                'success',
                'Erfolgreich gesendet!',
                result.message,
                () => {
                    // Reset form after modal is closed
                    e.target.reset();
                    document.getElementById('datum').valueAsDate = new Date();
                    updateTotalCount();
                }
            );
        } else {
            // Show error modal
            window.feuerwehrApp.showConfirmationModal(
                'error',
                'Fehler beim Senden',
                result.message
            );
        }
    } catch (error) {
        console.error('Error:', error);
        window.feuerwehrApp.showConfirmationModal(
            'error',
            'Fehler beim Senden',
            'Es ist ein Fehler beim Absenden der Liste aufgetreten. Bitte versuchen Sie es erneut.'
        );
    } finally {
        // Re-enable submit button
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
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
        // First, uncheck all instructors (since they start unchecked)
        document.querySelectorAll('.instructor-checkbox').forEach(cb => {
            cb.checked = false;
        });
        
        // Check the instructors that were selected
        const instructorCheckboxes = document.querySelectorAll('.instructor-checkbox');
        const leadersInCheckboxes = [];
        
        instructorCheckboxes.forEach(checkbox => {
            if (record.uebungsleiter.includes(checkbox.value)) {
                checkbox.checked = true;
                leadersInCheckboxes.push(checkbox.value);
            }
        });
        
        // If there are leaders not in the checkboxes, put them in the text field
        const leadersNotInCheckboxes = record.uebungsleiter.filter(leader => !leadersInCheckboxes.includes(leader));
        if (leadersNotInCheckboxes.length > 0) {
            document.getElementById('uebungsleiter-andere').value = leadersNotInCheckboxes.join(', ');
        }
        
        updateTotalCount();
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
</script>

<script>
// Initialize offline banner using shared utility
if (typeof initOfflineBanner === 'function') {
  initOfflineBanner('offline-banner');
}
</script>
