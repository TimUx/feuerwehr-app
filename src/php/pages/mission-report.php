<?php
/**
 * Mission Report Form (Einsatzbericht)
 * Based on JetForm JSON structure
 */

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../datastore.php';

Auth::requireOperator();

$personnel = DataStore::getPersonnel();
$vehicles = DataStore::getVehicles();

// Function options from JSON
$functions = [
    'Fahrzeugführer', 'Melder', 'Maschinist', 'Agrifftruppführer', 
    'Angriffstruppmann', 'Wassertruppführer', 'Wassertruppmann',
    'Schlauchtruppführer', 'Schlautruppmann'
];

$involvement_types = ['Verursacher', 'Geschädigter', 'Zeuge', 'Sonstiges'];
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
                <label class="form-label" for="einsatzgrund">Einsatzgrund *</label>
                <input type="text" id="einsatzgrund" name="einsatzgrund" class="form-input" maxlength="150" placeholder="z.B. Brand, Technische Hilfeleistung" required>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="einsatzdatum">Einsatzdatum *</label>
                <input type="date" id="einsatzdatum" name="einsatzdatum" class="form-input" required>
            </div>
            
            <div class="form-row">
                <div class="form-group" style="flex: 1;">
                    <label class="form-label" for="beginn">Beginn *</label>
                    <input type="datetime-local" id="beginn" name="beginn" class="form-input" required>
                </div>
                
                <div class="form-group" style="flex: 1;">
                    <label class="form-label" for="ende">Ende *</label>
                    <input type="datetime-local" id="ende" name="ende" class="form-input" required>
                </div>
                
                <div class="form-group" style="flex: 1;">
                    <label class="form-label" for="dauer">Dauer (Minuten)</label>
                    <input type="text" id="dauer" name="dauer" class="form-input" readonly>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="einsatzort">Einsatzort *</label>
                <textarea id="einsatzort" name="einsatzort" class="form-textarea" rows="2" placeholder="Straße, Hausnummer, Ort" required></textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="einsatzleiter">Einsatzleiter *</label>
                <input type="text" id="einsatzleiter" name="einsatzleiter" class="form-input" required>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="einsatzlage">Einsatzlage *</label>
                <textarea id="einsatzlage" name="einsatzlage" class="form-textarea" rows="3" placeholder="Beschreibung der vorgefundenen Situation" required></textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="tatigkeiten_der_feuerwehr">Tätigkeiten der Feuerwehr *</label>
                <textarea id="tatigkeiten_der_feuerwehr" name="tatigkeiten_der_feuerwehr" class="form-textarea" rows="3" placeholder="Beschreibung der durchgeführten Maßnahmen" required></textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="verbrauchte_mittel">Verbrauchte Mittel</label>
                <textarea id="verbrauchte_mittel" name="verbrauchte_mittel" class="form-textarea" rows="2" placeholder="Auflistung der verbrauchten Materialien"></textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="besondere_vorkommnisse">Besondere Vorkommnisse</label>
                <textarea id="besondere_vorkommnisse" name="besondere_vorkommnisse" class="form-textarea" rows="2" placeholder="Unfälle, Verletzungen, besondere Ereignisse"></textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label">Einsatz Kostenpflichtig?</label>
                <div style="display: flex; gap: 20px;">
                    <div class="form-check">
                        <input type="radio" id="kostenpflichtig_ja" name="einsatz_kostenpflichtig" value="ja" class="form-check-input">
                        <label for="kostenpflichtig_ja" class="form-check-label">ja</label>
                    </div>
                    <div class="form-check">
                        <input type="radio" id="kostenpflichtig_nein" name="einsatz_kostenpflichtig" value="nein" class="form-check-input" checked>
                        <label for="kostenpflichtig_nein" class="form-check-label">nein</label>
                    </div>
                </div>
            </div>
            
            <h3>Eingesetzte Fahrzeuge *</h3>
            
            <div class="form-group">
                <?php if (empty($vehicles)): ?>
                    <p style="color: var(--text-secondary);">Keine Fahrzeuge vorhanden. Bitte zuerst Fahrzeuge anlegen.</p>
                <?php else: ?>
                    <?php foreach ($vehicles as $vehicle): ?>
                    <div class="form-check">
                        <input type="checkbox" id="vehicle-<?php echo $vehicle['id']; ?>" name="eingesetzte_fahrzeuge[]" value="<?php echo htmlspecialchars($vehicle['type']); ?>" class="form-check-input vehicle-checkbox">
                        <label for="vehicle-<?php echo $vehicle['id']; ?>" class="form-check-label">
                            <?php echo htmlspecialchars($vehicle['type']); ?>
                            <?php if (!empty($vehicle['radio_call_sign'])): ?>
                                (<?php echo htmlspecialchars($vehicle['radio_call_sign']); ?>)
                            <?php endif; ?>
                        </label>
                    </div>
                    <?php endforeach; ?>
                    <div class="form-check">
                        <input type="checkbox" id="vehicle-custom" class="form-check-input vehicle-checkbox" data-custom="true">
                        <label for="vehicle-custom" class="form-check-label">+ Sonstiges</label>
                    </div>
                    <input type="text" id="vehicle-custom-input" name="eingesetzte_fahrzeuge_custom" class="form-input" placeholder="Anderes Fahrzeug" style="display: none; margin-top: 10px;">
                <?php endif; ?>
            </div>
            
            <h3>Anzahl beteiligter Einsatzkräfte *</h3>
            
            <div class="form-group">
                <label class="form-label" for="anzahl_einsatzkrafte">Anzahl beteiligter Einsatzkräfte</label>
                <div style="display: flex; align-items: center; gap: 15px;">
                    <input type="range" id="anzahl_einsatzkrafte" name="anzahl_einsatzkrafte" min="1" max="20" value="1" class="form-range" style="flex: 1;">
                    <span id="anzahl_einsatzkrafte_value" class="range-value">1</span>
                </div>
            </div>
            
            <h3>Fahrzeugbesatzung *</h3>
            
            <div id="crew-container">
                <!-- Dynamic crew entries will be added here -->
            </div>
            
            <h3>Anzahl beteiligter Personen</h3>
            
            <div class="form-group">
                <label class="form-label" for="anzahl_beteiligter_personen">Anzahl beteiligter Personen?</label>
                <div style="display: flex; align-items: center; gap: 15px;">
                    <input type="range" id="anzahl_beteiligter_personen" name="anzahl_beteiligter_personen" min="0" max="10" value="0" class="form-range" style="flex: 1;">
                    <span id="anzahl_beteiligter_personen_value" class="range-value">0</span>
                </div>
            </div>
            
            <div id="persons-container" style="display: none;">
                <h3>Beteiligte Personen</h3>
                <!-- Dynamic person entries will be added here -->
            </div>
            
            <div class="form-group mt-3">
                <button type="submit" class="btn btn-primary">
                    <span class="material-icons">send</span>
                    Absenden
                </button>
                <button type="reset" class="btn btn-secondary">
                    <span class="material-icons">refresh</span>
                    Zurücksetzen
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.crew-entry, .person-entry {
    background: var(--card-bg);
    border: 2px solid #888;
    border-radius: 10px;
    padding: 15px;
    margin-bottom: 15px;
}

.form-row {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.form-range {
    -webkit-appearance: none;
    appearance: none;
    height: 10px;
    border-radius: 5px;
    background: #888;
    outline: none;
}

.form-range::-webkit-slider-thumb {
    -webkit-appearance: none;
    appearance: none;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: #bb0000;
    border: 4px solid #000;
    cursor: pointer;
}

.form-range::-moz-range-thumb {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: #bb0000;
    border: 4px solid #000;
    cursor: pointer;
}

.range-value {
    font-size: 1.2em;
    font-weight: bold;
    color: #bb0000;
    min-width: 30px;
    text-align: center;
}
</style>

<script>
const personnel = <?php echo json_encode($personnel); ?>;
const vehicles = <?php echo json_encode($vehicles); ?>;
const functions = <?php echo json_encode($functions); ?>;
const involvementTypes = <?php echo json_encode($involvement_types); ?>;

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
        document.getElementById('dauer').value = durationMinutes;
    }
}

document.getElementById('beginn').addEventListener('change', calculateDuration);
document.getElementById('ende').addEventListener('change', calculateDuration);

// Handle custom vehicle input
document.getElementById('vehicle-custom').addEventListener('change', function() {
    const customInput = document.getElementById('vehicle-custom-input');
    customInput.style.display = this.checked ? 'block' : 'none';
    if (!this.checked) {
        customInput.value = '';
    }
});

// Handle anzahl einsatzkrafte range
const crewRange = document.getElementById('anzahl_einsatzkrafte');
const crewValue = document.getElementById('anzahl_einsatzkrafte_value');
const crewContainer = document.getElementById('crew-container');

crewRange.addEventListener('input', function() {
    crewValue.textContent = this.value;
    updateCrewEntries(parseInt(this.value));
});

function updateCrewEntries(count) {
    crewContainer.innerHTML = '';
    
    for (let i = 0; i < count; i++) {
        const entry = document.createElement('div');
        entry.className = 'crew-entry';
        entry.innerHTML = `
            <h4 style="margin-top: 0;">Einsatzkraft ${i + 1}</h4>
            
            <div class="form-group">
                <label class="form-label">Funktion</label>
                <select name="fahrzeugbesatzung[${i}][funktion]" class="form-select">
                    <option value="">Funktion</option>
                    ${functions.map(f => `<option value="${f}">${f}</option>`).join('')}
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">Name</label>
                <select name="fahrzeugbesatzung[${i}][name]" class="form-select">
                    <option value="">Name</option>
                    ${personnel.map(p => `<option value="${p.id}">${p.name}</option>`).join('')}
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">Fahrzeug</label>
                <select name="fahrzeugbesatzung[${i}][fahrzeug]" class="form-select vehicle-select">
                    <option value="">Fahrzeug</option>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">Verdienstausfall?</label>
                <div class="form-check">
                    <input type="checkbox" id="verdienstausfall_${i}" name="fahrzeugbesatzung[${i}][verdienstausfall]" value="ja" class="form-check-input">
                    <label for="verdienstausfall_${i}" class="form-check-label">ja</label>
                </div>
            </div>
        `;
        crewContainer.appendChild(entry);
    }
    
    updateVehicleSelects();
}

// Update vehicle select options based on checked vehicles
function updateVehicleSelects() {
    const checkedVehicles = Array.from(document.querySelectorAll('.vehicle-checkbox:checked'))
        .filter(cb => !cb.dataset.custom)
        .map(cb => cb.value);
    
    const customVehicle = document.getElementById('vehicle-custom-input').value;
    if (customVehicle && document.getElementById('vehicle-custom').checked) {
        checkedVehicles.push(customVehicle);
    }
    
    document.querySelectorAll('.vehicle-select').forEach(select => {
        const currentValue = select.value;
        select.innerHTML = '<option value="">Fahrzeug</option>' +
            checkedVehicles.map(v => `<option value="${v}" ${v === currentValue ? 'selected' : ''}>${v}</option>`).join('');
    });
}

// Listen for changes in vehicle checkboxes
document.querySelectorAll('.vehicle-checkbox').forEach(cb => {
    cb.addEventListener('change', updateVehicleSelects);
});

document.getElementById('vehicle-custom-input').addEventListener('input', updateVehicleSelects);

// Handle anzahl beteiligter personen range
const personsRange = document.getElementById('anzahl_beteiligter_personen');
const personsValue = document.getElementById('anzahl_beteiligter_personen_value');
const personsContainer = document.getElementById('persons-container');

personsRange.addEventListener('input', function() {
    personsValue.textContent = this.value;
    const count = parseInt(this.value);
    
    if (count > 0) {
        personsContainer.style.display = 'block';
        updatePersonEntries(count);
    } else {
        personsContainer.style.display = 'none';
    }
});

function updatePersonEntries(count) {
    const container = personsContainer.querySelector('div') || (() => {
        const div = document.createElement('div');
        personsContainer.appendChild(div);
        return div;
    })();
    
    container.innerHTML = '';
    
    for (let i = 0; i < count; i++) {
        const entry = document.createElement('div');
        entry.className = 'person-entry';
        entry.innerHTML = `
            <h4 style="margin-top: 0;">Person ${i + 1}</h4>
            
            <div class="form-group">
                <label class="form-label">Beteiligungsart</label>
                <select name="beteiligte_personen[${i}][beteiligungsart]" class="form-select">
                    <option value="">Wählen...</option>
                    ${involvementTypes.map(t => `<option value="${t}">${t}</option>`).join('')}
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">Name</label>
                <input type="text" name="beteiligte_personen[${i}][name]" class="form-input">
            </div>
            
            <div class="form-group">
                <label class="form-label">Telefonnummer</label>
                <input type="tel" name="beteiligte_personen[${i}][telefonnummer]" class="form-input">
            </div>
            
            <div class="form-group">
                <label class="form-label">Adresse</label>
                <textarea name="beteiligte_personen[${i}][adresse]" class="form-textarea" rows="2"></textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label">KFZ-Kennzeichen</label>
                <input type="text" name="beteiligte_personen[${i}][kfz_kennzeichen]" class="form-input">
            </div>
        `;
        container.appendChild(entry);
    }
}

// Initialize with 1 crew member
updateCrewEntries(1);

// Form submission
document.getElementById('mission-report-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    // Validate at least one vehicle is selected
    const vehiclesChecked = document.querySelectorAll('.vehicle-checkbox:checked').length;
    if (vehiclesChecked === 0) {
        window.feuerwehrApp.showAlert('error', 'Bitte wählen Sie mindestens ein Fahrzeug aus.');
        return;
    }
    
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
            crewRange.value = 1;
            crewValue.textContent = '1';
            personsRange.value = 0;
            personsValue.textContent = '0';
            updateCrewEntries(1);
            personsContainer.style.display = 'none';
        } else {
            window.feuerwehrApp.showAlert('error', result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        window.feuerwehrApp.showAlert('error', 'Fehler beim Absenden des Berichts');
    }
});
</script>
