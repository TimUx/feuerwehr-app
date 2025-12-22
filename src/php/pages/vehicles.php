<?php
/**
 * Vehicles Management Page
 */

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../datastore.php';

Auth::requireAuth();

$vehicles = DataStore::getVehicles();
$isAdmin = Auth::isAdmin();
?>

<div class="card">
    <div class="card-header">
        <span>Fahrzeuge</span>
        <?php if ($isAdmin): ?>
        <button class="btn btn-primary" onclick="openVehicleModal()">
            <span class="material-icons">add</span>
            Hinzufügen
        </button>
        <?php endif; ?>
    </div>
    <div class="card-content">
        <?php if (empty($vehicles)): ?>
            <p class="text-center" style="color: var(--text-secondary);">Keine Fahrzeuge vorhanden.</p>
        <?php else: ?>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Typ</th>
                            <th>Ort/Standort</th>
                            <th>Funkrufname</th>
                            <?php if ($isAdmin): ?>
                            <th style="width: 120px;">Aktionen</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($vehicles as $vehicle): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($vehicle['type']); ?></strong></td>
                            <td><?php echo htmlspecialchars($vehicle['location'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($vehicle['radio_call_sign'] ?? '-'); ?></td>
                            <?php if ($isAdmin): ?>
                            <td>
                                <button class="icon-btn" onclick='editVehicle(<?php echo json_encode($vehicle); ?>)' title="Bearbeiten">
                                    <span class="material-icons">edit</span>
                                </button>
                                <button class="icon-btn" onclick="deleteVehicle('<?php echo $vehicle['id']; ?>', '<?php echo htmlspecialchars($vehicle['type']); ?>')" title="Löschen">
                                    <span class="material-icons">delete</span>
                                </button>
                            </td>
                            <?php endif; ?>
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
                <input type="text" id="vehicle-type" name="type" class="form-input" placeholder="z.B. LF 10, TLF 16/25, DLK 23/12" required>
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
function openVehicleModal() {
    document.getElementById('vehicle-modal').classList.add('show');
    document.getElementById('modal-title').textContent = 'Fahrzeug hinzufügen';
    document.getElementById('vehicle-form').reset();
    document.getElementById('vehicle-id').value = '';
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
            window.feuerwehrApp.loadPage('vehicles');
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
        radio_call_sign: formData.get('radio_call_sign')
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
            window.feuerwehrApp.loadPage('vehicles');
        } else {
            window.feuerwehrApp.showAlert('error', result.message);
        }
    } catch (error) {
        window.feuerwehrApp.showAlert('error', 'Fehler beim Speichern');
    }
});
</script>
