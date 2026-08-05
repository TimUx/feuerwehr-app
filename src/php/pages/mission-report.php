<?php
/**
 * Mission Report Form (Einsatzbericht)
 * Based on JetForm JSON structure
 */

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../datastore.php';

Auth::requireOperator();

$user = Auth::getUser();
$hasGlobalAccess = Auth::hasGlobalAccess();
$userLocationId = Auth::getUserLocationId();

$personnel = DataStore::getPersonnelByLocation($hasGlobalAccess ? null : $userLocationId);
$vehicles = DataStore::getVehiclesByLocation($hasGlobalAccess ? null : $userLocationId);
$locations = DataStore::getLocations();

// Check if we're editing an existing record
$editMode = false;
$editRecord = null;
if (isset($_GET['edit'])) {
    $editId = $_GET['edit'];
    $allReports = DataStore::getMissionReports();
    foreach ($allReports as $report) {
        if ($report['id'] === $editId) {
            $editRecord = $report;
            $editMode = true;
            break;
        }
    }
}

// Function options from JSON
$functions = [
    'Feuerwehrmann/-frau',
    'Fahrzeugführer', 'Melder', 'Maschinist', 'Angriffstruppführer',
    'Angriffstruppmann', 'Wassertruppführer', 'Wassertruppmann',
    'Schlauchtruppführer', 'Schlauchtruppmann'
];

$involvement_types = ['Verursacher', 'Geschädigter', 'Zeuge', 'Sonstiges'];
?>

<div class="card">
    <div class="card-header">
        <span class="material-icons">description</span>
        <?php echo $editMode ? 'Einsatzbericht bearbeiten' : 'Einsatzbericht'; ?>
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
            <strong>Bearbeitungsmodus:</strong> Sie bearbeiten einen vorhandenen Einsatzbericht.
        </div>
        <?php endif; ?>

        <div id="draft-banner" style="display:none; margin-bottom:1rem; padding:0.75rem; background:rgba(255,152,0,0.12); border-left:4px solid var(--warning-color); border-radius:4px;">
            <strong>Gespeicherter Entwurf gefunden.</strong>
            <div style="display:flex; flex-wrap:wrap; gap:0.5rem; margin-top:0.5rem;">
                <button type="button" class="btn btn-secondary" id="draft-restore-btn">Entwurf laden</button>
                <button type="button" class="btn btn-danger" id="draft-discard-btn">Entwurf verwerfen</button>
            </div>
        </div>
        
        <form id="mission-report-form" method="POST" action="/src/php/forms/submit_mission_report.php" novalidate>
            <?php if ($editMode): ?>
            <input type="hidden" name="record_id" value="<?php echo htmlspecialchars($editRecord['id']); ?>">
            <?php endif; ?>
            
            <h3 style="margin-top: 0;">Einsatzdaten</h3>
            
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
                    Je nach Auswahl werden nur die Fahrzeuge und Einsatzkräfte dieses Standorts angezeigt
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
                    <div class="form-check" data-location-id="<?php echo htmlspecialchars($vehicle['location_id'] ?? ''); ?>">
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
                Mindestens eine Einsatzkraft pro Fahrzeug muss angegeben werden. Die übrigen Sitzplätze müssen nicht belegt werden.
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
                <?php if (!$editMode): ?>
                <button type="button" class="btn btn-secondary" id="save-draft-btn">
                    <span class="material-icons">save</span>
                    Entwurf speichern
                </button>
                <?php endif; ?>
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
(function() {
// Wrap in IIFE to avoid variable conflicts during SPA navigation
const personnel = <?php echo json_encode($personnel); ?>;
const vehicles = <?php echo json_encode($vehicles); ?>;
const functions = <?php echo json_encode($functions); ?>;
const involvementTypes = <?php echo json_encode($involvement_types); ?>;

// Filter vehicles and personnel by selected location
function filterByLocation() {
    const selectedLocationId = document.getElementById('standort-filter').value;
    
    // Filter vehicles
    const vehicleItems = document.querySelectorAll('#mission-report-form .form-check[data-location-id]');
    vehicleItems.forEach(item => {
        const itemLocationId = item.getAttribute('data-location-id') || '';
        if (!selectedLocationId || itemLocationId === selectedLocationId) {
            item.style.display = '';
        } else {
            item.style.display = 'none';
            // Uncheck hidden vehicles
            const checkbox = item.querySelector('.vehicle-checkbox');
            if (checkbox && !checkbox.hasAttribute('data-custom')) {
                checkbox.checked = false;
            }
        }
    });
    
    // Trigger crew update when location changes
    updateCrewSections();
}

// Get filtered personnel based on selected location
function getFilteredPersonnel() {
    const selectedLocationId = document.getElementById('standort-filter').value;
    if (!selectedLocationId) {
        return personnel;
    }
    return personnel.filter(p => p.location_id == selectedLocationId);
}

// Add event listener for location filter
document.getElementById('standort-filter').addEventListener('change', filterByLocation);

// Initialize filtering on page load (for Global Admin with pre-selected location)
(function() {
    const standortFilterMission = document.getElementById('standort-filter');
    if (standortFilterMission && standortFilterMission.value) {
        filterByLocation();
    }
})();

// Set default date to today
(function() {
    const einsatzdatumField = document.getElementById('einsatzdatum');
    if (einsatzdatumField) {
        einsatzdatumField.valueAsDate = new Date();
    }
})();

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
    
    // Save current crew field values before clearing
    const savedCrewData = {};
    crewContainer.querySelectorAll('.vehicle-crew-section').forEach(section => {
        const vehicleId = section.dataset.vehicleId;
        savedCrewData[vehicleId] = [];
        section.querySelectorAll('.crew-entry').forEach((entry, i) => {
            const funktionSelect = entry.querySelector('select[name*="[funktion]"]');
            const nameSelect = entry.querySelector('select[name*="[name]"]');
            const verdienstausfallCheckbox = entry.querySelector('input[type="checkbox"][name*="[verdienstausfall]"]');
            savedCrewData[vehicleId].push({
                funktion: funktionSelect ? funktionSelect.value : '',
                name: nameSelect ? nameSelect.value : '',
                verdienstausfall: verdienstausfallCheckbox ? verdienstausfallCheckbox.checked : false
            });
        });
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
                                ${getFilteredPersonnel().map(p => `<option value="${p.id}">${p.name}</option>`).join('')}
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

        // Restore previously filled crew data for this vehicle
        if (savedCrewData[vehicle.id]) {
            section.querySelectorAll('.crew-entry').forEach((entry, i) => {
                const saved = savedCrewData[vehicle.id][i];
                if (!saved) return;
                const funktionSelect = entry.querySelector('select[name*="[funktion]"]');
                const nameSelect = entry.querySelector('select[name*="[name]"]');
                const verdienstausfallCheckbox = entry.querySelector('input[type="checkbox"][name*="[verdienstausfall]"]');
                if (funktionSelect && saved.funktion) funktionSelect.value = saved.funktion;
                if (nameSelect && saved.name) nameSelect.value = saved.name;
                if (verdienstausfallCheckbox) verdienstausfallCheckbox.checked = saved.verdienstausfall;
            });
        }
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

// Expose removePersonEntry to global scope for onclick handler
window.removePersonEntry = removePersonEntry;

// Helper function to validate required fields and scroll to first error
function validateRequiredFields() {
    const form = document.getElementById('mission-report-form');
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
document.getElementById('mission-report-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    // Validate required fields first
    if (!validateRequiredFields()) {
        return;
    }
    
    // Validate at least one vehicle is selected
    const vehiclesChecked = document.querySelectorAll('.vehicle-checkbox:checked').length;
    if (vehiclesChecked === 0) {
        // Scroll to vehicles section
        const vehiclesSection = document.querySelector('h3:nth-of-type(2)'); // "Eingesetzte Fahrzeuge" heading
        if (vehiclesSection) {
            vehiclesSection.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        
        window.feuerwehrApp.showConfirmationModal(
            'error',
            'Fahrzeug fehlt',
            'Bitte wählen Sie mindestens ein Fahrzeug aus.'
        );
        return;
    }
    
    // Validate at least one crew member per vehicle
    const vehicleSections = document.querySelectorAll('.vehicle-crew-section');
    let hasAllCrewMembers = true;
    let missingCrewVehicle = null;
    
    vehicleSections.forEach(section => {
        const entries = section.querySelectorAll('.crew-entry');
        let hasAtLeastOne = false;
        
        entries.forEach(entry => {
            const nameSelect = entry.querySelector('select[name*="[name]"]');
            const funktionSelect = entry.querySelector('select[name*="[funktion]"]');
            if ((nameSelect && nameSelect.value) || (funktionSelect && funktionSelect.value)) {
                hasAtLeastOne = true;
            }
        });
        
        if (!hasAtLeastOne) {
            hasAllCrewMembers = false;
            if (!missingCrewVehicle) {
                missingCrewVehicle = section;
            }
        }
    });
    
    if (!hasAllCrewMembers) {
        // Scroll to the vehicle section missing crew
        if (missingCrewVehicle) {
            missingCrewVehicle.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        
        window.feuerwehrApp.showConfirmationModal(
            'error',
            'Fahrzeugbesatzung unvollständig',
            'Bitte geben Sie mindestens eine Einsatzkraft pro Fahrzeug an (Funktion oder Name auswählen).'
        );
        return;
    }
    
    const formData = new FormData(e.target);
    
    // Disable submit button during submission
    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalBtnText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="material-icons">hourglass_empty</span> Wird gesendet...';
    
    try {
        const response = await fetch('/src/php/forms/submit_mission_report.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            try { localStorage.removeItem(window.DRAFT_KEY || 'fw_mission_draft_v1'); } catch (_) {}
            // Show success modal
            window.feuerwehrApp.showConfirmationModal(
                'success',
                'Erfolgreich gesendet!',
                result.message,
                () => {
                    // Reset form after modal is closed
                    e.target.reset();
                    document.getElementById('einsatzdatum').valueAsDate = new Date();
                    document.getElementById('crew-container').innerHTML = '<p style="color: var(--text-secondary);">Bitte wählen Sie zuerst Fahrzeuge aus.</p>';
                    document.getElementById('persons-container').innerHTML = '';
                    personCounter = 0;
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
            'Es ist ein Fehler beim Absenden des Berichts aufgetreten. Bitte versuchen Sie es erneut.'
        );
    } finally {
        // Re-enable submit button
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
    }
});

// Initialize
updateCrewSections();

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
    
    if (record.einsatzgrund) {
        document.getElementById('einsatzgrund').value = record.einsatzgrund;
    }
    
    if (record.einsatzdatum) {
        document.getElementById('einsatzdatum').value = record.einsatzdatum;
    }
    
    if (record.beginn) {
        document.getElementById('beginn').value = record.beginn;
    }
    
    if (record.ende) {
        document.getElementById('ende').value = record.ende;
    }
    
    // Trigger duration calculation
    calculateDuration();
    
    if (record.einsatzort) {
        document.getElementById('einsatzort').value = record.einsatzort;
    }
    
    if (record.einsatzleiter) {
        document.getElementById('einsatzleiter').value = record.einsatzleiter;
    }
    
    if (record.einsatzlage) {
        document.getElementById('einsatzlage').value = record.einsatzlage;
    }
    
    if (record.tatigkeiten_der_feuerwehr) {
        document.getElementById('tatigkeiten_der_feuerwehr').value = record.tatigkeiten_der_feuerwehr;
    }
    
    if (record.verbrauchte_mittel) {
        document.getElementById('verbrauchte_mittel').value = record.verbrauchte_mittel;
    }
    
    if (record.besondere_vorkommnisse) {
        document.getElementById('besondere_vorkommnisse').value = record.besondere_vorkommnisse;
    }
    
    if (record.einsatz_kostenpflichtig) {
        const costRadios = document.querySelectorAll('input[name="einsatz_kostenpflichtig"]');
        costRadios.forEach(radio => {
            if (radio.value === record.einsatz_kostenpflichtig) {
                radio.checked = true;
            }
        });
    }
    
        // Pre-fill vehicles
    if (record.eingesetzte_fahrzeuge && Array.isArray(record.eingesetzte_fahrzeuge)) {
        record.eingesetzte_fahrzeuge.forEach(vehicleType => {
            const checkbox = document.querySelector(`input[name="eingesetzte_fahrzeuge[]"][value="${vehicleType}"]`);
            if (checkbox) {
                checkbox.checked = true;
            }
        });
        
        // Update crew sections
        updateCrewSections();
        
        // Pre-fill crew data if available
        // Note: This uses nested loops and multiple DOM queries, but only runs once
        // during edit mode initialization with typically small datasets (< 10 vehicles)
        if (record.fahrzeugbesatzung && Array.isArray(record.fahrzeugbesatzung)) {
            // Wait a bit for the crew sections to be created
            setTimeout(() => {
                record.fahrzeugbesatzung.forEach((crewMember) => {
                    const storedVehicleType = crewMember.fahrzeug;
                    // Find the vehicle section whose data-vehicle-id matches the vehicle type
                    let vehicleSection = null;
                    document.querySelectorAll('.vehicle-crew-section').forEach(section => {
                        const sectionVehicleId = section.dataset.vehicleId;
                        // The section is keyed by vehicle ID; find the vehicle object matching the type
                        const matchingVehicle = vehicles.find(v => (v.id === sectionVehicleId && v.type === storedVehicleType) || sectionVehicleId === 'custom');
                        if (matchingVehicle && !vehicleSection) {
                            vehicleSection = section;
                        }
                    });
                    
                    if (vehicleSection) {
                        // Find an empty name slot in this section
                        const entries = vehicleSection.querySelectorAll('.crew-entry');
                        let targetEntry = null;
                        
                        for (let entry of entries) {
                            const nameSelect = entry.querySelector('select[name*="[name]"]');
                            if (nameSelect && !nameSelect.value) {
                                targetEntry = entry;
                                break;
                            }
                        }
                        
                        if (targetEntry) {
                            const nameSelect = targetEntry.querySelector('select[name*="[name]"]');
                            const funktionSelect = targetEntry.querySelector('select[name*="[funktion]"]');
                            const verdienstausfallCheckbox = targetEntry.querySelector('input[type="checkbox"][name*="[verdienstausfall]"]');
                            
                            if (nameSelect && crewMember.name) {
                                nameSelect.value = crewMember.name;
                            }
                            if (funktionSelect && crewMember.funktion) {
                                funktionSelect.value = crewMember.funktion;
                            }
                            if (verdienstausfallCheckbox && crewMember.verdienstausfall === 'ja') {
                                verdienstausfallCheckbox.checked = true;
                            }
                        }
                    }
                });
            }, 100);
        }
    }
    
    // Pre-fill involved persons if available
    if (record.beteiligte_personen && Array.isArray(record.beteiligte_personen)) {
        record.beteiligte_personen.forEach((person) => {
            addPersonEntry();
        });
        
        // Wait a bit for the entries to be added
        setTimeout(() => {
            const entries = document.querySelectorAll('.person-entry');
            record.beteiligte_personen.forEach((person, index) => {
                if (!entries[index]) return;
                const entry = entries[index];
                
                const beteiligungsartSelect = entry.querySelector('select[name*="[beteiligungsart]"]');
                const nameInput = entry.querySelector('input[name*="[name]"]');
                const telefonnummerInput = entry.querySelector('input[name*="[telefonnummer]"]');
                const adresseTextarea = entry.querySelector('textarea[name*="[adresse]"]');
                const kfzInput = entry.querySelector('input[name*="[kfz_kennzeichen]"]');
                
                if (beteiligungsartSelect && person.beteiligungsart) {
                    beteiligungsartSelect.value = person.beteiligungsart;
                }
                if (nameInput && person.name) {
                    nameInput.value = person.name;
                }
                if (telefonnummerInput && person.telefonnummer) {
                    telefonnummerInput.value = person.telefonnummer;
                }
                if (adresseTextarea && person.adresse) {
                    adresseTextarea.value = person.adresse;
                }
                if (kfzInput && person.kfz_kennzeichen) {
                    kfzInput.value = person.kfz_kennzeichen;
                }
            });
        }, 100);
    }
})();
<?php endif; ?>

})(); // End IIFE
</script>

<script>
// Initialize offline banner using shared utility
if (typeof initOfflineBanner === 'function') {
  initOfflineBanner('offline-banner');
}

// Local draft for new mission reports (not edit mode)
(function () {
  const form = document.getElementById('mission-report-form');
  if (!form || form.querySelector('[name="record_id"]')) return;

  const DRAFT_KEY = 'fw_mission_draft_v1';
  const banner = document.getElementById('draft-banner');
  const saveBtn = document.getElementById('save-draft-btn');
  let saveTimer = null;

  function serializeForm() {
    const data = {};
    const fd = new FormData(form);
    for (const [key, value] of fd.entries()) {
      if (key.endsWith('[]') || key.includes('[')) {
        if (!Object.prototype.hasOwnProperty.call(data, key)) data[key] = [];
        if (Array.isArray(data[key])) data[key].push(value);
        else data[key] = [data[key], value];
      } else if (Object.prototype.hasOwnProperty.call(data, key)) {
        if (!Array.isArray(data[key])) data[key] = [data[key]];
        data[key].push(value);
      } else {
        data[key] = value;
      }
    }
    // Also capture unchecked multi-check state via checked vehicle boxes
    const vehicles = Array.from(form.querySelectorAll('input[name="eingesetzte_fahrzeuge[]"]:checked')).map(el => el.value);
    data['eingesetzte_fahrzeuge[]'] = vehicles;
    return { savedAt: new Date().toISOString(), data };
  }

  function saveDraft(silent) {
    try {
      localStorage.setItem(DRAFT_KEY, JSON.stringify(serializeForm()));
      if (!silent && window.feuerwehrApp) {
        window.feuerwehrApp.showAlert('success', 'Entwurf gespeichert');
      }
    } catch (err) {
      console.error(err);
      if (window.feuerwehrApp) window.feuerwehrApp.showAlert('error', 'Entwurf konnte nicht gespeichert werden');
    }
  }

  function applyDraft(payload) {
    const data = payload.data || {};
    Object.keys(data).forEach((key) => {
      const values = Array.isArray(data[key]) ? data[key] : [data[key]];
      if (key === 'eingesetzte_fahrzeuge[]') {
        form.querySelectorAll('input[name="eingesetzte_fahrzeuge[]"]').forEach(cb => {
          cb.checked = values.includes(cb.value);
        });
        if (typeof updateCrewSections === 'function') updateCrewSections();
        return;
      }
      const fields = form.querySelectorAll(`[name="${CSS.escape(key)}"]`);
      if (!fields.length) return;
      if (fields[0].type === 'checkbox' || fields[0].type === 'radio') {
        fields.forEach(f => { f.checked = values.includes(f.value); });
      } else if (fields.length === 1) {
        fields[0].value = values[0] ?? '';
      }
    });
    // Restore dynamic person entries roughly by count
    const personKeys = Object.keys(data).filter(k => k.includes('beteiligte_personen'));
    if (personKeys.length && typeof addPersonEntry === 'function') {
      const indices = new Set();
      personKeys.forEach(k => {
        const m = k.match(/beteiligte_personen\[(\d+)\]/);
        if (m) indices.add(Number(m[1]));
      });
      [...indices].sort((a,b)=>a-b).forEach(() => addPersonEntry());
      setTimeout(() => {
        Object.keys(data).forEach((key) => {
          if (!key.includes('beteiligte_personen')) return;
          const el = form.querySelector(`[name="${CSS.escape(key)}"]`);
          if (el) el.value = Array.isArray(data[key]) ? data[key][0] : data[key];
        });
      }, 50);
    }
    if (typeof calculateDuration === 'function') calculateDuration();
  }

  function loadDraftMeta() {
    try {
      const raw = localStorage.getItem(DRAFT_KEY);
      if (!raw) return null;
      return JSON.parse(raw);
    } catch (_) {
      return null;
    }
  }

  const existing = loadDraftMeta();
  if (existing && banner) {
    banner.style.display = 'block';
  }

  document.getElementById('draft-restore-btn')?.addEventListener('click', () => {
    const draft = loadDraftMeta();
    if (!draft) return;
    applyDraft(draft);
    banner.style.display = 'none';
    if (window.feuerwehrApp) window.feuerwehrApp.showAlert('info', 'Entwurf geladen');
  });

  document.getElementById('draft-discard-btn')?.addEventListener('click', async () => {
    const ok = window.feuerwehrApp
      ? await window.feuerwehrApp.confirmAction('Entwurf verwerfen', 'Gespeicherten Entwurf wirklich löschen?')
      : true;
    if (!ok) return;
    localStorage.removeItem(DRAFT_KEY);
    banner.style.display = 'none';
    if (window.feuerwehrApp) window.feuerwehrApp.showAlert('success', 'Entwurf verworfen');
  });

  saveBtn?.addEventListener('click', () => saveDraft(false));

  form.addEventListener('input', () => {
    clearTimeout(saveTimer);
    saveTimer = setTimeout(() => saveDraft(true), 2000);
  });
  form.addEventListener('change', () => {
    clearTimeout(saveTimer);
    saveTimer = setTimeout(() => saveDraft(true), 2000);
  });

  // Expose key for submit handler cleanup
  window.DRAFT_KEY = DRAFT_KEY;
})();
</script>
