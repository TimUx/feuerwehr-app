<?php
/**
 * Admin Vehicles Management Page
 */

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../datastore.php';

Auth::requireAdmin();

$vehicles = DataStore::getVehicles();
?>

<div class="card">
    <div class="card-header">
        <span>Fahrzeugverwaltung</span>
        <button class="btn btn-primary" onclick="openVehicleModal()">
            <span class="material-icons">add</span>
            Hinzufügen
        </button>
    </div>
    <div class="card-content">
        <!-- Search and Filter Controls -->
        <div class="filter-controls" style="margin-bottom: 1rem; display: flex; gap: 1rem; flex-wrap: wrap;">
            <div class="form-group" style="flex: 1; min-width: 200px; margin: 0;">
                <input type="text" id="searchVehicles" class="form-input" placeholder="Suchen nach Typ oder Funkrufname...">
            </div>
            <div class="form-group" style="flex: 0 0 150px; margin: 0;">
                <select id="filterLocation" class="form-input">
                    <option value="">Alle Orte</option>
                    <?php
                    $locations = array_unique(array_filter(array_column($vehicles, 'location')));
                    sort($locations);
                    foreach ($locations as $location):
                    ?>
                    <option value="<?php echo htmlspecialchars($location); ?>"><?php echo htmlspecialchars($location); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="flex: 0 0 150px; margin: 0;">
                <select id="filterType" class="form-input">
                    <option value="">Alle Typen</option>
                    <?php
                    $types = array_unique(array_filter(array_column($vehicles, 'type')));
                    sort($types);
                    foreach ($types as $type):
                    ?>
                    <option value="<?php echo htmlspecialchars($type); ?>"><?php echo htmlspecialchars($type); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <?php if (empty($vehicles)): ?>
            <p class="text-center" style="color: var(--text-secondary);">Keine Fahrzeuge vorhanden.</p>
        <?php else: ?>
            <div class="table-container">
                <table class="table" id="vehiclesTable">
                    <thead>
                        <tr>
                            <th onclick="sortVehicles('type')" style="cursor: pointer;">
                                Typ <span class="material-icons" style="font-size: 16px; vertical-align: middle;">unfold_more</span>
                            </th>
                            <th onclick="sortVehicles('location')" style="cursor: pointer;">
                                Ort/Standort <span class="material-icons" style="font-size: 16px; vertical-align: middle;">unfold_more</span>
                            </th>
                            <th onclick="sortVehicles('radio_call_sign')" style="cursor: pointer;">
                                Funkrufname <span class="material-icons" style="font-size: 16px; vertical-align: middle;">unfold_more</span>
                            </th>
                            <th onclick="sortVehicles('crew_size')" style="cursor: pointer;">
                                Besatzung <span class="material-icons" style="font-size: 16px; vertical-align: middle;">unfold_more</span>
                            </th>
                            <th style="width: 120px;">Aktionen</th>
                        </tr>
                    </thead>
                    <tbody id="vehiclesTableBody">
                        <?php
                        // Sort vehicles by radio_call_sign by default
                        usort($vehicles, function($a, $b) {
                            return strcasecmp($a['radio_call_sign'] ?? '', $b['radio_call_sign'] ?? '');
                        });
                        foreach ($vehicles as $vehicle): 
                        ?>
                        <tr data-type="<?php echo htmlspecialchars($vehicle['type']); ?>" 
                            data-location="<?php echo htmlspecialchars($vehicle['location'] ?? ''); ?>"
                            data-radio="<?php echo htmlspecialchars($vehicle['radio_call_sign'] ?? ''); ?>"
                            data-crew="<?php echo htmlspecialchars($vehicle['crew_size'] ?? ''); ?>">
                            <td><strong><?php echo htmlspecialchars($vehicle['type']); ?></strong></td>
                            <td><?php echo htmlspecialchars($vehicle['location'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($vehicle['radio_call_sign'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($vehicle['crew_size'] ?? '-'); ?></td>
                            <td>
                                <button class="icon-btn" onclick='editVehicle(<?php echo json_encode($vehicle); ?>)' title="Bearbeiten">
                                    <span class="material-icons">edit</span>
                                </button>
                                <button class="icon-btn" onclick="deleteVehicle('<?php echo $vehicle['id']; ?>', '<?php echo htmlspecialchars($vehicle['type']); ?>')" title="Löschen">
                                    <span class="material-icons">delete</span>
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

<!-- Vehicle Modal -->
<div id="vehicle-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title" id="modal-title">Fahrzeug hinzufügen</h2>
            <button class="modal-close" onclick="closeVehicleModal()">&times;</button>
        </div>
        <form id="vehicle-form">
            <input type="hidden" id="vehicle-id" name="id">
            
            <div class="form-group">
                <label class="form-label" for="vehicle-type">Typ *</label>
                <select id="vehicle-type" name="type" class="form-input" required onchange="updateCrewSize()">
                    <option value="">-- Fahrzeugtyp auswählen --</option>
                    <option value="LF 8" data-crew="6">LF 8 - Löschgruppenfahrzeug 8 (Besatzung: 1/5)</option>
                    <option value="LF 10" data-crew="9">LF 10 - Löschgruppenfahrzeug 10 (Besatzung: 1/8)</option>
                    <option value="HLF 10" data-crew="9">HLF 10 - Hilfeleistungslöschgruppenfahrzeug 10 (Besatzung: 1/8)</option>
                    <option value="TSF" data-crew="6">TSF - Tragkraftspritzenfahrzeug (Besatzung: 1/5)</option>
                    <option value="TSF-W" data-crew="6">TSF-W - Tragkraftspritzenfahrzeug mit Löschwasserbehälter (Besatzung: 1/5)</option>
                    <option value="MTF" data-crew="9">MTF - Mannschaftstransportfahrzeug (Besatzung: 1/8)</option>
                    <option value="MLF" data-crew="6">MLF - Mittleres Löschfahrzeug (Besatzung: 1/5)</option>
                    <option value="ELW 1" data-crew="3">ELW 1 - Einsatzleitwagen 1 (Besatzung: 1/2)</option>
                    <option value="GW-L" data-crew="3">GW-L - Gerätewagen Logistik (Besatzung: 1/2)</option>
                    <option value="RW" data-crew="3">RW - Rüstwagen (Besatzung: 1/2)</option>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="vehicle-crew-size">Besatzungsstärke</label>
                <input type="text" id="vehicle-crew-size" name="crew_size" class="form-input" readonly style="background-color: var(--bg-secondary);">
                <small style="color: var(--text-secondary);">Wird automatisch basierend auf dem Fahrzeugtyp gesetzt</small>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="vehicle-location">Ort/Standort</label>
                <input type="text" id="vehicle-location" name="location" class="form-input" placeholder="z.B. Feuerwehrhaus Willingshausen">
            </div>
            
            <div class="form-group">
                <label class="form-label" for="vehicle-radio">Funkrufname</label>
                <input type="text" id="vehicle-radio" name="radio_call_sign" class="form-input" placeholder="z.B. Florian Willingshausen 1/44/1">
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeVehicleModal()">Abbrechen</button>
                <button type="submit" class="btn btn-primary">Speichern</button>
            </div>
        </form>
    </div>
</div>

<script>
function updateCrewSize() {
    const typeSelect = document.getElementById('vehicle-type');
    const crewSizeInput = document.getElementById('vehicle-crew-size');
    const selectedOption = typeSelect.options[typeSelect.selectedIndex];
    const crewSize = selectedOption.getAttribute('data-crew');
    
    if (crewSize && crewSize !== '0') {
        crewSizeInput.value = crewSize;
    } else if (crewSize === '0') {
        crewSizeInput.value = '-';
    } else {
        crewSizeInput.value = '';
    }
}

function openVehicleModal() {
    document.getElementById('vehicle-modal').classList.add('show');
    document.getElementById('modal-title').textContent = 'Fahrzeug hinzufügen';
    document.getElementById('vehicle-form').reset();
    document.getElementById('vehicle-id').value = '';
    document.getElementById('vehicle-crew-size').value = '';
}

function closeVehicleModal() {
    document.getElementById('vehicle-modal').classList.remove('show');
}

function editVehicle(vehicle) {
    document.getElementById('vehicle-modal').classList.add('show');
    document.getElementById('modal-title').textContent = 'Fahrzeug bearbeiten';
    document.getElementById('vehicle-id').value = vehicle.id;
    document.getElementById('vehicle-type').value = vehicle.type;
    document.getElementById('vehicle-location').value = vehicle.location || '';
    document.getElementById('vehicle-radio').value = vehicle.radio_call_sign || '';
    
    // Set crew size from existing data or trigger update
    if (vehicle.crew_size) {
        document.getElementById('vehicle-crew-size').value = vehicle.crew_size;
    } else {
        updateCrewSize();
    }
}

async function deleteVehicle(id, type) {
    if (!confirm(`Möchten Sie das Fahrzeug "${type}" wirklich löschen?`)) {
        return;
    }
    
    try {
        const response = await fetch('/src/php/api/vehicles.php', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id })
        });
        
        const result = await response.json();
        
        if (result.success) {
            window.feuerwehrApp.showAlert('success', result.message);
            window.feuerwehrApp.loadPage('admin-vehicles');
        } else {
            window.feuerwehrApp.showAlert('error', result.message);
        }
    } catch (error) {
        window.feuerwehrApp.showAlert('error', 'Fehler beim Löschen');
    }
}

// Form submission
document.getElementById('vehicle-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const data = {
        type: formData.get('type'),
        location: formData.get('location'),
        radio_call_sign: formData.get('radio_call_sign'),
        crew_size: formData.get('crew_size')
    };
    
    const id = formData.get('id');
    const method = id ? 'PUT' : 'POST';
    
    if (id) {
        data.id = id;
    }
    
    try {
        const response = await fetch('/src/php/api/vehicles.php', {
            method: method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            window.feuerwehrApp.showAlert('success', result.message);
            closeVehicleModal();
            window.feuerwehrApp.loadPage('admin-vehicles');
        } else {
            window.feuerwehrApp.showAlert('error', result.message);
        }
    } catch (error) {
        window.feuerwehrApp.showAlert('error', 'Fehler beim Speichern');
    }
});

// Filter and search functionality
let currentSortColumn = 'radio_call_sign';
let currentSortDirection = 'asc';

document.getElementById('searchVehicles')?.addEventListener('input', filterVehicles);
document.getElementById('filterLocation')?.addEventListener('change', filterVehicles);
document.getElementById('filterType')?.addEventListener('change', filterVehicles);

function filterVehicles() {
    const searchTerm = document.getElementById('searchVehicles')?.value.toLowerCase() || '';
    const locationFilter = document.getElementById('filterLocation')?.value.toLowerCase() || '';
    const typeFilter = document.getElementById('filterType')?.value.toLowerCase() || '';
    
    const rows = document.querySelectorAll('#vehiclesTableBody tr');
    
    rows.forEach(row => {
        const type = row.dataset.type?.toLowerCase() || '';
        const location = row.dataset.location?.toLowerCase() || '';
        const radio = row.dataset.radio?.toLowerCase() || '';
        
        const matchesSearch = !searchTerm || type.includes(searchTerm) || radio.includes(searchTerm);
        const matchesLocation = !locationFilter || location === locationFilter;
        const matchesType = !typeFilter || type === typeFilter;
        
        if (matchesSearch && matchesLocation && matchesType) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

function sortVehicles(column) {
    const tbody = document.getElementById('vehiclesTableBody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    // Toggle sort direction
    if (currentSortColumn === column) {
        currentSortDirection = currentSortDirection === 'asc' ? 'desc' : 'asc';
    } else {
        currentSortColumn = column;
        currentSortDirection = 'asc';
    }
    
    rows.sort((a, b) => {
        let aVal = '';
        let bVal = '';
        
        if (column === 'type') {
            aVal = a.dataset.type || '';
            bVal = b.dataset.type || '';
        } else if (column === 'location') {
            aVal = a.dataset.location || '';
            bVal = b.dataset.location || '';
        } else if (column === 'radio_call_sign') {
            aVal = a.dataset.radio || '';
            bVal = b.dataset.radio || '';
        } else if (column === 'crew_size') {
            aVal = parseInt(a.dataset.crew) || 0;
            bVal = parseInt(b.dataset.crew) || 0;
            const numComparison = aVal - bVal;
            return currentSortDirection === 'asc' ? numComparison : -numComparison;
        }
        
        const comparison = aVal.localeCompare(bVal, 'de', { sensitivity: 'base' });
        return currentSortDirection === 'asc' ? comparison : -comparison;
    });
    
    // Re-append rows in sorted order
    rows.forEach(row => tbody.appendChild(row));
}
</script>
