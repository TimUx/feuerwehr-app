<?php
/**
 * Mission Report Form (Einsatzbericht)
 */

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../datastore.php';

Auth::requireOperator();

$personnel = DataStore::getPersonnel();
$vehicles = DataStore::getVehicles();
?>

<div class="card">
    <div class="card-header">
        <span class="material-icons">description</span>
        Einsatzbericht
    </div>
    <div class="card-content">
        <form id="mission-report-form" method="POST" action="/src/php/forms/submit_mission_report.php">
            
            <h3 style="margin-top: 0;">Einsatzdaten</h3>
            
            <div class="form-group">
                <label class="form-label" for="einsatzdatum">Einsatzdatum *</label>
                <input type="date" id="einsatzdatum" name="einsatzdatum" class="form-input" required>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="einsatzgrund">Einsatzgrund *</label>
                <input type="text" id="einsatzgrund" name="einsatzgrund" class="form-input" placeholder="z.B. Brand, Technische Hilfeleistung" required>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="einsatzort">Einsatzort *</label>
                <input type="text" id="einsatzort" name="einsatzort" class="form-input" placeholder="Straße, Hausnummer, Ort" required>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="einsatzleiter">Einsatzleiter *</label>
                <input type="text" id="einsatzleiter" name="einsatzleiter" class="form-input" required>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="beginn">Einsatzbeginn *</label>
                <input type="datetime-local" id="beginn" name="beginn" class="form-input" required>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="ende">Einsatzende *</label>
                <input type="datetime-local" id="ende" name="ende" class="form-input" required>
            </div>
            
            <h3>Einsatzbeschreibung</h3>
            
            <div class="form-group">
                <label class="form-label" for="einsatzlage">Einsatzlage</label>
                <textarea id="einsatzlage" name="einsatzlage" class="form-textarea" placeholder="Beschreibung der vorgefundenen Situation"></textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="tatigkeiten_der_feuerwehr">Tätigkeiten der Feuerwehr</label>
                <textarea id="tatigkeiten_der_feuerwehr" name="tatigkeiten_der_feuerwehr" class="form-textarea" placeholder="Beschreibung der durchgeführten Maßnahmen"></textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="verbrauchte_mittel">Verbrauchte Materialien</label>
                <textarea id="verbrauchte_mittel" name="verbrauchte_mittel" class="form-textarea" placeholder="Auflistung der verbrauchten Materialien und Mittel"></textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="besondere_vorkommnisse">Besondere Vorkommnisse</label>
                <textarea id="besondere_vorkommnisse" name="besondere_vorkommnisse" class="form-textarea" placeholder="Unfälle, Verletzungen, besondere Ereignisse"></textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="einsatz_kostenpflichtig">Einsatz kostenpflichtig? *</label>
                <select id="einsatz_kostenpflichtig" name="einsatz_kostenpflichtig" class="form-select" required>
                    <option value="Nein">Nein</option>
                    <option value="Ja">Ja</option>
                </select>
            </div>
            
            <h3>Eingesetzte Fahrzeuge</h3>
            
            <div class="form-group">
                <?php if (empty($vehicles)): ?>
                    <p style="color: var(--text-secondary);">Keine Fahrzeuge vorhanden. Bitte zuerst Fahrzeuge anlegen.</p>
                <?php else: ?>
                    <?php foreach ($vehicles as $vehicle): ?>
                    <div class="form-check">
                        <input type="checkbox" id="vehicle-<?php echo $vehicle['id']; ?>" name="eingesetzte_fahrzeuge[]" value="<?php echo htmlspecialchars($vehicle['type']); ?>" class="form-check-input">
                        <label for="vehicle-<?php echo $vehicle['id']; ?>" class="form-check-label">
                            <?php echo htmlspecialchars($vehicle['type']); ?>
                            <?php if (!empty($vehicle['radio_call_sign'])): ?>
                                (<?php echo htmlspecialchars($vehicle['radio_call_sign']); ?>)
                            <?php endif; ?>
                        </label>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <h3>Fahrzeugbesatzung</h3>
            
            <div class="form-group">
                <label class="form-label">Wählen Sie die Besatzungsmitglieder aus</label>
                <?php if (empty($personnel)): ?>
                    <p style="color: var(--text-secondary);">Keine Einsatzkräfte vorhanden. Bitte zuerst Einsatzkräfte anlegen.</p>
                <?php else: ?>
                    <?php foreach ($personnel as $person): ?>
                    <div class="form-check">
                        <input type="checkbox" id="crew-<?php echo $person['id']; ?>" name="fahrzeugbesatzung[]" value="<?php echo $person['id']; ?>" class="form-check-input">
                        <label for="crew-<?php echo $person['id']; ?>" class="form-check-label">
                            <?php echo htmlspecialchars($person['name']); ?>
                            <?php if (!empty($person['qualifications'])): ?>
                                <?php foreach ($person['qualifications'] as $qual): ?>
                                    <span class="badge badge-info" style="font-size: 0.7rem;"><?php echo htmlspecialchars($qual); ?></span>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </label>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <h3>Weitere beteiligte Personen</h3>
            
            <div class="form-group">
                <label class="form-label">Weitere beteiligte Einsatzkräfte</label>
                <?php if (empty($personnel)): ?>
                    <p style="color: var(--text-secondary);">Keine Einsatzkräfte vorhanden.</p>
                <?php else: ?>
                    <?php foreach ($personnel as $person): ?>
                    <div class="form-check">
                        <input type="checkbox" id="other-<?php echo $person['id']; ?>" name="beteiligte_personen[]" value="<?php echo $person['id']; ?>" class="form-check-input">
                        <label for="other-<?php echo $person['id']; ?>" class="form-check-label">
                            <?php echo htmlspecialchars($person['name']); ?>
                        </label>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div class="form-group mt-3">
                <button type="submit" class="btn btn-primary">
                    <span class="material-icons">send</span>
                    Bericht absenden
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
document.getElementById('einsatzdatum').valueAsDate = new Date();

// Calculate duration when start/end times change
function calculateDuration() {
    const start = document.getElementById('beginn').value;
    const end = document.getElementById('ende').value;
    
    if (start && end) {
        const startDate = new Date(start);
        const endDate = new Date(end);
        const durationMinutes = Math.round((endDate - startDate) / 1000 / 60);
        
        // We'll pass this to the server
        console.log('Duration:', durationMinutes, 'minutes');
    }
}

document.getElementById('beginn').addEventListener('change', calculateDuration);
document.getElementById('ende').addEventListener('change', calculateDuration);

// Form submission
document.getElementById('mission-report-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    
    try {
        const response = await fetch('/src/php/forms/submit_mission_report.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            window.feuerwehrApp.showAlert('success', result.message);
            e.target.reset();
            document.getElementById('einsatzdatum').valueAsDate = new Date();
        } else {
            window.feuerwehrApp.showAlert('error', result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        window.feuerwehrApp.showAlert('error', 'Fehler beim Absenden des Berichts');
    }
});
</script>
