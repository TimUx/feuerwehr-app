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
                        <!-- value = vehicle type (for form submission), data-vehicle-id = vehicle ID (for JS lookup) -->
                        <input type="checkbox" id="vehicle-<?php echo $vehicle['id']; ?>" 
                               name="eingesetzte_fahrzeuge[]" 
                               value="<?php echo htmlspecialchars($vehicle['type']); ?>" 
                               data-vehicle-id="<?php echo $vehicle['id']; ?>"
                               class="form-check-input vehicle-checkbox">
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
            
            <h3>Fahrzeugbesatzung *</h3>
            <p style="color: var(--text-secondary); margin-bottom: 1rem;">
                Für jedes ausgewählte Fahrzeug werden entsprechend der Besatzungsstärke Eingabefelder angezeigt.
                Mindestens eine Zeile pro Fahrzeug muss ausgefüllt werden.
            </p>
            
            <div id="crew-container">
                <!-- Dynamic crew entries per vehicle will be added here -->
            </div>
            
            <h3>Beteiligte Personen</h3>
            <p style="color: var(--text-secondary); margin-bottom: 1rem;">
                Fügen Sie beteiligte Personen hinzu (Verursacher, Geschädigte, Zeugen, etc.)
            </p>
            
            <div class="form-group">
                <button type="button" id="add-person-btn" class="btn btn-secondary">
                    <span class="material-icons">add</span>
                    Person hinzufügen
                </button>
            </div>
            
            <div id="persons-container">
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
    background: var(--bg-card);
    border: 2px solid var(--border-color, #ddd);
    border-radius: 10px;
    padding: 15px;
    margin-bottom: 15px;
    position: relative;
}

.vehicle-crew-section {
    background: var(--bg-secondary);
    border: 3px solid var(--primary-color);
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 25px;
}

.vehicle-crew-section h4 {
    color: var(--primary-color);
    margin-top: 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.vehicle-crew-section h4 .material-icons {
    font-size: 28px;
}

.remove-person-btn {
    position: absolute;
    top: 10px;
    right: 10px;
    background: var(--danger-color, #dc3545);
    color: white;
    border: none;
    border-radius: 50%;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 20px;
    line-height: 1;
}

.remove-person-btn:hover {
    background: #c82333;
}

.form-row {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}
</style>

<script>
const personnel = <?php echo json_encode($personnel); ?>;
const vehicles = <?php echo json_encode($vehicles); ?>;
const functions = <?php echo json_encode($functions); ?>;
const involvementTypes = <?php echo json_encode($involvement_types); ?>;

// Set default date to today
const einsatzdatumField = document.getElementById('einsatzdatum');
if (einsatzdatumField) {
    einsatzdatumField.valueAsDate = new Date();
}

// Calculate duration when start/end times change
function calculateDuration() {
    const startField = document.getElementById('beginn');
    const endField = document.getElementById('ende');
    const durationField = document.getElementById('dauer');
    
    if (!startField || !endField || !durationField) return;
    
    const start = startField.value;
    const end = endField.value;
    
    if (start && end) {
        const startDate = new Date(start);
        const endDate = new Date(end);
        const durationMinutes = Math.round((endDate - startDate) / 1000 / 60);
        durationField.value = durationMinutes;
    } else {
        // Clear duration if either field is empty
        durationField.value = '';
    }
}

const beginnField = document.getElementById('beginn');
const endeField = document.getElementById('ende');

if (beginnField) {
    beginnField.addEventListener('change', calculateDuration);
}

if (endeField) {
    endeField.addEventListener('change', calculateDuration);
}

// Handle custom vehicle input
document.getElementById('vehicle-custom').addEventListener('change', function() {
    const customInput = document.getElementById('vehicle-custom-input');
    customInput.style.display = this.checked ? 'block' : 'none';
    if (!this.checked) {
        customInput.value = '';
    }
    updateCrewSections();
});

document.getElementById('vehicle-custom-input').addEventListener('input', updateCrewSections);

// Update crew sections based on selected vehicles
function updateCrewSections() {
    const crewContainer = document.getElementById('crew-container');
    const selectedVehicles = [];
    
    // Get all checked vehicles with their crew sizes
    document.querySelectorAll('.vehicle-checkbox:checked').forEach(cb => {
        if (cb.dataset.custom) {
            const customValue = document.getElementById('vehicle-custom-input').value;
            if (customValue) {
                selectedVehicles.push({
                    id: 'custom',
                    type: customValue,
                    crew_size: 6 // Default for custom
                });
            }
        } else {
            const vehicleId = cb.dataset.vehicleId;
            // Use loose equality (==) to handle string vs number comparison
            const vehicle = vehicles.find(v => v.id == vehicleId);
            if (vehicle) {
                selectedVehicles.push({
                    id: vehicle.id,
                    type: vehicle.type,
                    crew_size: parseInt(vehicle.crew_size) || 6
                });
            }
        }
    });
    
    // Clear existing
    crewContainer.innerHTML = '';
    
    if (selectedVehicles.length === 0) {
        crewContainer.innerHTML = '<p style="color: var(--text-secondary);">Bitte wählen Sie zuerst Fahrzeuge aus.</p>';
        return;
    }
    
    // Create section for each vehicle
    selectedVehicles.forEach(vehicle => {
        const section = document.createElement('div');
        section.className = 'vehicle-crew-section';
        section.dataset.vehicleId = vehicle.id;
        
        let sectionHTML = `
            <h4>
                <span class="material-icons">local_shipping</span>
                ${vehicle.type} <span style="font-weight: normal; font-size: 0.9em;">(Besatzung: ${vehicle.crew_size})</span>
            </h4>
        `;
        
        // Create crew member entries
        for (let i = 0; i < vehicle.crew_size; i++) {
            sectionHTML += `
                <div class="crew-entry">
                    <h5 style="margin-top: 0;">Einsatzkraft ${i + 1}</h5>
                    <input type="hidden" name="fahrzeugbesatzung[${vehicle.id}_${i}][fahrzeug]" value="${vehicle.type}">
                    
                    <div class="form-row">
                        <div class="form-group" style="flex: 1; min-width: 200px;">
                            <label class="form-label">Funktion</label>
                            <select name="fahrzeugbesatzung[${vehicle.id}_${i}][funktion]" class="form-select">
                                <option value="">Funktion wählen</option>
                                ${functions.map(f => `<option value="${f}">${f}</option>`).join('')}
                            </select>
                        </div>
                        
                        <div class="form-group" style="flex: 1; min-width: 200px;">
                            <label class="form-label">Name</label>
                            <select name="fahrzeugbesatzung[${vehicle.id}_${i}][name]" class="form-select">
                                <option value="">Name wählen</option>
                                ${personnel.map(p => `<option value="${p.id}">${p.name}</option>`).join('')}
                            </select>
                        </div>
                        
                        <div class="form-group" style="flex: 0 0 150px;">
                            <label class="form-label">Verdienstausfall?</label>
                            <div class="form-check">
                                <input type="checkbox" id="verdienstausfall_${vehicle.id}_${i}" name="fahrzeugbesatzung[${vehicle.id}_${i}][verdienstausfall]" value="ja" class="form-check-input">
                                <label for="verdienstausfall_${vehicle.id}_${i}" class="form-check-label">ja</label>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }
        
        section.innerHTML = sectionHTML;
        crewContainer.appendChild(section);
    });
}

// Listen for vehicle checkbox changes
document.querySelectorAll('.vehicle-checkbox').forEach(cb => {
    cb.addEventListener('change', updateCrewSections);
});

// Beteiligte Personen - Add/Remove functionality
let personCounter = 0;

const addPersonBtn = document.getElementById('add-person-btn');
if (addPersonBtn) {
    addPersonBtn.addEventListener('click', function() {
        addPersonEntry();
    });
}

function addPersonEntry() {
    const personsContainer = document.getElementById('persons-container');
    const entry = document.createElement('div');
    entry.className = 'person-entry';
    entry.dataset.personId = personCounter;
    
    entry.innerHTML = `
        <button type="button" class="remove-person-btn" onclick="removePersonEntry(${personCounter})" title="Entfernen">
            <span class="material-icons" style="font-size: 20px;">close</span>
        </button>
        
        <h4 style="margin-top: 0;">Person ${personCounter + 1}</h4>
        
        <div class="form-row">
            <div class="form-group" style="flex: 1; min-width: 200px;">
                <label class="form-label">Beteiligungsart *</label>
                <select name="beteiligte_personen[${personCounter}][beteiligungsart]" class="form-select" required>
                    <option value="">Wählen...</option>
                    ${involvementTypes.map(t => `<option value="${t}">${t}</option>`).join('')}
                </select>
            </div>
            
            <div class="form-group" style="flex: 1; min-width: 200px;">
                <label class="form-label">Name *</label>
                <input type="text" name="beteiligte_personen[${personCounter}][name]" class="form-input" required>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group" style="flex: 1; min-width: 200px;">
                <label class="form-label">Telefonnummer</label>
                <input type="tel" name="beteiligte_personen[${personCounter}][telefonnummer]" class="form-input">
            </div>
            
            <div class="form-group" style="flex: 1; min-width: 200px;">
                <label class="form-label">KFZ-Kennzeichen</label>
                <input type="text" name="beteiligte_personen[${personCounter}][kfz_kennzeichen]" class="form-input">
            </div>
        </div>
        
        <div class="form-group">
            <label class="form-label">Adresse</label>
            <textarea name="beteiligte_personen[${personCounter}][adresse]" class="form-textarea" rows="2"></textarea>
        </div>
    `;
    
    personsContainer.appendChild(entry);
    personCounter++;
}

function removePersonEntry(id) {
    const entry = document.querySelector(`.person-entry[data-person-id="${id}"]`);
    if (entry) {
        entry.remove();
    }
}

// Form submission
document.getElementById('mission-report-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    // Validate at least one vehicle is selected
    const vehiclesChecked = document.querySelectorAll('.vehicle-checkbox:checked').length;
    if (vehiclesChecked === 0) {
        window.feuerwehrApp.showAlert('error', 'Bitte wählen Sie mindestens ein Fahrzeug aus.');
        return;
    }
    
    // Validate at least one crew member per vehicle
    const vehicleSections = document.querySelectorAll('.vehicle-crew-section');
    let hasAllCrewMembers = true;
    
    vehicleSections.forEach(section => {
        const entries = section.querySelectorAll('.crew-entry');
        let hasAtLeastOne = false;
        
        entries.forEach(entry => {
            const nameSelect = entry.querySelector('select[name*="[name]"]');
            if (nameSelect && nameSelect.value) {
                hasAtLeastOne = true;
            }
        });
        
        if (!hasAtLeastOne) {
            hasAllCrewMembers = false;
        }
    });
    
    if (!hasAllCrewMembers) {
        window.feuerwehrApp.showAlert('error', 'Bitte füllen Sie mindestens eine Einsatzkraft pro Fahrzeug aus.');
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
            document.getElementById('crew-container').innerHTML = '<p style="color: var(--text-secondary);">Bitte wählen Sie zuerst Fahrzeuge aus.</p>';
            document.getElementById('persons-container').innerHTML = '';
            personCounter = 0;
        } else {
            window.feuerwehrApp.showAlert('error', result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        window.feuerwehrApp.showAlert('error', 'Fehler beim Absenden des Berichts');
    }
});

// Initialize
updateCrewSections();
</script>
