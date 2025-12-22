<?php
/**
 * Personnel Management Page
 */

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../datastore.php';

Auth::requireAuth();

$personnel = DataStore::getPersonnel();
$isAdmin = Auth::isAdmin();
?>

<div class="card">
    <div class="card-header">
        <span>Einsatzkräfte</span>
        <?php if ($isAdmin): ?>
        <button class="btn btn-primary" onclick="openPersonnelModal()">
            <span class="material-icons">add</span>
            Hinzufügen
        </button>
        <?php endif; ?>
    </div>
    <div class="card-content">
        <?php if (empty($personnel)): ?>
            <p class="text-center" style="color: var(--text-secondary);">Keine Einsatzkräfte vorhanden.</p>
        <?php else: ?>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Ausbildungen</th>
                            <th>Führungsrollen</th>
                            <?php if ($isAdmin): ?>
                            <th style="width: 120px;">Aktionen</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($personnel as $person): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($person['name']); ?></td>
                            <td>
                                <?php 
                                $qualifications = $person['qualifications'] ?? [];
                                if (!empty($qualifications)) {
                                    foreach ($qualifications as $qual) {
                                        echo '<span class="badge badge-info">' . htmlspecialchars($qual) . '</span> ';
                                    }
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                            <td>
                                <?php 
                                $roles = $person['leadership_roles'] ?? [];
                                if (!empty($roles)) {
                                    foreach ($roles as $role) {
                                        echo '<span class="badge badge-primary">' . htmlspecialchars($role) . '</span> ';
                                    }
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                            <?php if ($isAdmin): ?>
                            <td>
                                <button class="icon-btn" onclick='editPersonnel(<?php echo json_encode($person); ?>)' title="Bearbeiten">
                                    <span class="material-icons">edit</span>
                                </button>
                                <button class="icon-btn" onclick="deletePersonnel('<?php echo $person['id']; ?>', '<?php echo htmlspecialchars($person['name']); ?>')" title="Löschen">
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

<!-- Personnel Modal -->
<div id="personnel-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title" id="modal-title">Einsatzkraft hinzufügen</h2>
            <button class="modal-close" onclick="closePersonnelModal()">&times;</button>
        </div>
        <form id="personnel-form">
            <input type="hidden" id="personnel-id" name="id">
            
            <div class="form-group">
                <label class="form-label" for="personnel-name">Name *</label>
                <input type="text" id="personnel-name" name="name" class="form-input" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Ausbildungen</label>
                <div class="form-check">
                    <input type="checkbox" id="qual-agt" name="qualifications[]" value="AGT" class="form-check-input">
                    <label for="qual-agt" class="form-check-label">AGT (Atemschutzgeräteträger)</label>
                </div>
                <div class="form-check">
                    <input type="checkbox" id="qual-maschinist" name="qualifications[]" value="Maschinist" class="form-check-input">
                    <label for="qual-maschinist" class="form-check-label">Maschinist</label>
                </div>
                <div class="form-check">
                    <input type="checkbox" id="qual-sanitaeter" name="qualifications[]" value="Sanitäter" class="form-check-input">
                    <label for="qual-sanitaeter" class="form-check-label">Sanitäter</label>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Führungsrollen</label>
                <div class="form-check">
                    <input type="checkbox" id="role-truppfuehrer" name="leadership_roles[]" value="Truppführer" class="form-check-input">
                    <label for="role-truppfuehrer" class="form-check-label">Truppführer</label>
                </div>
                <div class="form-check">
                    <input type="checkbox" id="role-gruppenfuehrer" name="leadership_roles[]" value="Gruppenführer" class="form-check-input">
                    <label for="role-gruppenfuehrer" class="form-check-label">Gruppenführer</label>
                </div>
                <div class="form-check">
                    <input type="checkbox" id="role-zugfuehrer" name="leadership_roles[]" value="Zugführer" class="form-check-input">
                    <label for="role-zugfuehrer" class="form-check-label">Zugführer</label>
                </div>
                <div class="form-check">
                    <input type="checkbox" id="role-verbandsfuehrer" name="leadership_roles[]" value="Verbandsführer" class="form-check-input">
                    <label for="role-verbandsfuehrer" class="form-check-label">Verbandsführer</label>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closePersonnelModal()">Abbrechen</button>
                <button type="submit" class="btn btn-primary">Speichern</button>
            </div>
        </form>
    </div>
</div>

<script>
function openPersonnelModal() {
    document.getElementById('personnel-modal').classList.add('show');
    document.getElementById('modal-title').textContent = 'Einsatzkraft hinzufügen';
    document.getElementById('personnel-form').reset();
    document.getElementById('personnel-id').value = '';
}

function closePersonnelModal() {
    document.getElementById('personnel-modal').classList.remove('show');
}

function editPersonnel(person) {
    document.getElementById('personnel-modal').classList.add('show');
    document.getElementById('modal-title').textContent = 'Einsatzkraft bearbeiten';
    document.getElementById('personnel-id').value = person.id;
    document.getElementById('personnel-name').value = person.name;
    
    // Clear all checkboxes
    document.querySelectorAll('#personnel-form input[type="checkbox"]').forEach(cb => cb.checked = false);
    
    // Set qualifications
    if (person.qualifications) {
        person.qualifications.forEach(qual => {
            const checkbox = document.querySelector(`#personnel-form input[value="${qual}"]`);
            if (checkbox) checkbox.checked = true;
        });
    }
    
    // Set leadership roles
    if (person.leadership_roles) {
        person.leadership_roles.forEach(role => {
            const checkbox = document.querySelector(`#personnel-form input[value="${role}"]`);
            if (checkbox) checkbox.checked = true;
        });
    }
}

async function deletePersonnel(id, name) {
    if (!confirm(`Möchten Sie "${name}" wirklich löschen?`)) {
        return;
    }
    
    try {
        const response = await fetch('/src/php/api/personnel.php', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id })
        });
        
        const result = await response.json();
        
        if (result.success) {
            window.feuerwehrApp.showAlert('success', result.message);
            window.feuerwehrApp.loadPage('personnel');
        } else {
            window.feuerwehrApp.showAlert('error', result.message);
        }
    } catch (error) {
        window.feuerwehrApp.showAlert('error', 'Fehler beim Löschen');
    }
}

// Form submission
document.getElementById('personnel-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const data = {
        name: formData.get('name'),
        qualifications: formData.getAll('qualifications[]'),
        leadership_roles: formData.getAll('leadership_roles[]')
    };
    
    const id = formData.get('id');
    const method = id ? 'PUT' : 'POST';
    
    if (id) {
        data.id = id;
    }
    
    try {
        const response = await fetch('/src/php/api/personnel.php', {
            method: method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            window.feuerwehrApp.showAlert('success', result.message);
            closePersonnelModal();
            window.feuerwehrApp.loadPage('personnel');
        } else {
            window.feuerwehrApp.showAlert('error', result.message);
        }
    } catch (error) {
        window.feuerwehrApp.showAlert('error', 'Fehler beim Speichern');
    }
});
</script>
