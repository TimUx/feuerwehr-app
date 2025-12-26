<?php
/**
 * Admin Locations Management Page
 */

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../datastore.php';

Auth::requireAdmin();

$locations = DataStore::getLocations();
?>

<div class="card">
    <div class="card-header">
        <span>Einsatzabteilungen / Standorte</span>
        <button class="btn btn-primary" onclick="openLocationModal()">
            <span class="material-icons">add</span>
            Hinzufügen
        </button>
    </div>
    <div class="card-content">
        <p style="color: var(--text-secondary); margin-bottom: 1rem;">
            Verwalten Sie hier die Standorte Ihrer Feuerwehr. Diese werden als Dropdown bei Fahrzeugen, Einsatzkräften und in Formularen zur Verfügung gestellt.
        </p>
        
        <?php if (empty($locations)): ?>
            <p class="text-center" style="color: var(--text-secondary);">Keine Standorte vorhanden.</p>
        <?php else: ?>
            <div class="table-container">
                <table class="table" id="locationsTable">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Adresse</th>
                            <th style="width: 120px;">Aktionen</th>
                        </tr>
                    </thead>
                    <tbody id="locationsTableBody">
                        <?php
                        // Sort locations by name
                        usort($locations, function($a, $b) {
                            return strcasecmp($a['name'] ?? '', $b['name'] ?? '');
                        });
                        foreach ($locations as $location): 
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($location['name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($location['address'] ?? '-'); ?></td>
                            <td>
                                <button class="icon-btn" onclick='editLocation(<?php echo json_encode($location); ?>)' title="Bearbeiten">
                                    <span class="material-icons">edit</span>
                                </button>
                                <button class="icon-btn" onclick="deleteLocation('<?php echo $location['id']; ?>', '<?php echo htmlspecialchars($location['name']); ?>')" title="Löschen">
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

<!-- Location Modal -->
<div id="location-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title" id="modal-title">Standort hinzufügen</h2>
            <button class="modal-close" onclick="closeLocationModal()">&times;</button>
        </div>
        <form id="location-form">
            <input type="hidden" id="location-id" name="id">
            
            <div class="form-group">
                <label class="form-label" for="location-name">Name *</label>
                <input type="text" id="location-name" name="name" class="form-input" placeholder="z.B. Feuerwehrhaus Willingshausen" required>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="location-address">Adresse</label>
                <textarea id="location-address" name="address" class="form-textarea" rows="3" placeholder="Straße, PLZ, Ort"></textarea>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeLocationModal()">Abbrechen</button>
                <button type="submit" class="btn btn-primary">Speichern</button>
            </div>
        </form>
    </div>
</div>

<script>
function openLocationModal() {
    document.getElementById('location-modal').classList.add('show');
    document.getElementById('modal-title').textContent = 'Standort hinzufügen';
    document.getElementById('location-form').reset();
    document.getElementById('location-id').value = '';
}

function closeLocationModal() {
    document.getElementById('location-modal').classList.remove('show');
}

function editLocation(location) {
    document.getElementById('location-modal').classList.add('show');
    document.getElementById('modal-title').textContent = 'Standort bearbeiten';
    document.getElementById('location-id').value = location.id;
    document.getElementById('location-name').value = location.name || '';
    document.getElementById('location-address').value = location.address || '';
}

async function deleteLocation(id, name) {
    if (!confirm(`Möchten Sie den Standort "${name}" wirklich löschen?\n\nHinweis: Fahrzeuge und Einsatzkräfte, die diesem Standort zugeordnet sind, werden nicht gelöscht.`)) {
        return;
    }
    
    try {
        const response = await fetch('/src/php/api/locations.php', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id })
        });
        
        const result = await response.json();
        
        if (result.success) {
            window.feuerwehrApp.showAlert('success', result.message);
            window.feuerwehrApp.loadPage('admin-locations');
        } else {
            window.feuerwehrApp.showAlert('error', result.message);
        }
    } catch (error) {
        window.feuerwehrApp.showAlert('error', 'Fehler beim Löschen');
    }
}

// Form submission
document.getElementById('location-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const data = {
        name: formData.get('name'),
        address: formData.get('address')
    };
    
    const id = formData.get('id');
    const method = id ? 'PUT' : 'POST';
    
    if (id) {
        data.id = id;
    }
    
    try {
        const response = await fetch('/src/php/api/locations.php', {
            method: method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            window.feuerwehrApp.showAlert('success', result.message);
            closeLocationModal();
            window.feuerwehrApp.loadPage('admin-locations');
        } else {
            window.feuerwehrApp.showAlert('error', result.message);
        }
    } catch (error) {
        window.feuerwehrApp.showAlert('error', 'Fehler beim Speichern');
    }
});
</script>
