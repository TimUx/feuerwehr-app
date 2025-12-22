<?php
/**
 * Attendance List Form (Anwesenheitsliste)
 */

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../datastore.php';

Auth::requireOperator();

$personnel = DataStore::getPersonnel();
?>

<div class="card">
    <div class="card-header">
        <span class="material-icons">fact_check</span>
        Anwesenheitsliste
    </div>
    <div class="card-content">
        <form id="attendance-form" method="POST" action="/src/php/forms/submit_attendance.php" enctype="multipart/form-data">
            
            <h3 style="margin-top: 0;">Veranstaltungsdaten</h3>
            
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
                        <option value="<?php echo htmlspecialchars($person['name']); ?>">
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
                        <div class="form-check">
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
</script>
