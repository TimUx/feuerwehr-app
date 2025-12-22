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
        <form id="attendance-form" method="POST" action="/src/php/forms/submit_attendance.php">
            
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
                <label class="form-label">Wählen Sie die Übungsleiter aus *</label>
                <?php if (empty($personnel)): ?>
                    <p style="color: var(--text-secondary);">Keine Einsatzkräfte vorhanden. Bitte zuerst Einsatzkräfte anlegen.</p>
                <?php else: ?>
                    <?php foreach ($personnel as $person): ?>
                        <?php 
                        // Show only personnel with leadership roles
                        $hasLeadershipRole = !empty($person['leadership_roles']);
                        if ($hasLeadershipRole):
                        ?>
                        <div class="form-check">
                            <input type="checkbox" id="leader-<?php echo $person['id']; ?>" name="uebungsleiter[]" value="<?php echo $person['id']; ?>" class="form-check-input">
                            <label for="leader-<?php echo $person['id']; ?>" class="form-check-label">
                                <?php echo htmlspecialchars($person['name']); ?>
                                <?php foreach ($person['leadership_roles'] as $role): ?>
                                    <span class="badge badge-primary" style="font-size: 0.7rem;"><?php echo htmlspecialchars($role); ?></span>
                                <?php endforeach; ?>
                            </label>
                        </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    
                    <!-- Also show all personnel in case no one has leadership roles -->
                    <?php 
                    $hasAnyLeaders = false;
                    foreach ($personnel as $person) {
                        if (!empty($person['leadership_roles'])) {
                            $hasAnyLeaders = true;
                            break;
                        }
                    }
                    
                    if (!$hasAnyLeaders): ?>
                        <p style="color: var(--text-secondary); margin-bottom: 1rem;">
                            <em>Hinweis: Keine Personen mit Führungsrollen gefunden. Alle Einsatzkräfte werden angezeigt.</em>
                        </p>
                        <?php foreach ($personnel as $person): ?>
                        <div class="form-check">
                            <input type="checkbox" id="leader-<?php echo $person['id']; ?>" name="uebungsleiter[]" value="<?php echo $person['id']; ?>" class="form-check-input">
                            <label for="leader-<?php echo $person['id']; ?>" class="form-check-label">
                                <?php echo htmlspecialchars($person['name']); ?>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                <?php endif; ?>
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
                        <strong>Teilnehmeranzahl:</strong> <span id="attendee-count">0</span>
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

// Update attendee count
function updateAttendeeCount() {
    const checkboxes = document.querySelectorAll('.attendee-checkbox:checked');
    document.getElementById('attendee-count').textContent = checkboxes.length;
}

// Select all attendees
function selectAllAttendees() {
    document.querySelectorAll('.attendee-checkbox').forEach(cb => {
        cb.checked = true;
    });
    updateAttendeeCount();
}

// Deselect all attendees
function deselectAllAttendees() {
    document.querySelectorAll('.attendee-checkbox').forEach(cb => {
        cb.checked = false;
    });
    updateAttendeeCount();
}

// Listen for checkbox changes
document.querySelectorAll('.attendee-checkbox').forEach(cb => {
    cb.addEventListener('change', updateAttendeeCount);
});

// Initialize count
updateAttendeeCount();

// Form submission
document.getElementById('attendance-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    
    // Validate that at least one leader and one attendee is selected
    const leaders = formData.getAll('uebungsleiter[]');
    const attendees = formData.getAll('teilnehmer[]');
    
    if (leaders.length === 0) {
        window.feuerwehrApp.showAlert('error', 'Bitte wählen Sie mindestens einen Übungsleiter aus');
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
            updateAttendeeCount();
        } else {
            window.feuerwehrApp.showAlert('error', result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        window.feuerwehrApp.showAlert('error', 'Fehler beim Absenden der Liste');
    }
});
</script>
