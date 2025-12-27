<?php
/**
 * Users Management Page (Admin only)
 */

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../datastore.php';

Auth::requireAdmin();

// Filter users by location for location-restricted admins
$users = Auth::listUsers();
if (Auth::hasLocationRestriction()) {
    $userLocationId = Auth::getUserLocationId();
    $users = array_filter($users, function($user) use ($userLocationId) {
        return ($user['location_id'] ?? null) === $userLocationId;
    });
    $users = array_values($users); // Re-index array
}

$locations = DataStore::getLocations();
$hasLocationRestriction = Auth::hasLocationRestriction();
$userLocationId = Auth::getUserLocationId();
?>

<div class="card">
    <div class="card-header">
        <span>Benutzerverwaltung</span>
        <button class="btn btn-primary" onclick="openUserModal()">
            <span class="material-icons">add</span>
            Benutzer hinzufügen
        </button>
    </div>
    <div class="card-content">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Benutzername</th>
                        <th>E-Mail</th>
                        <th>Rolle</th>
                        <th>Standort</th>
                        <th>Erstellt am</th>
                        <th style="width: 120px;">Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <?php 
                    $userLocation = null;
                    if (!empty($user['location_id'])) {
                        $userLocation = DataStore::getLocationById($user['location_id']);
                    }
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td>
                            <?php if (!empty($user['email'])): ?>
                                <?php echo htmlspecialchars($user['email']); ?>
                            <?php else: ?>
                                <span style="color: var(--text-secondary);">Keine E-Mail</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge badge-<?php echo $user['role'] === 'admin' ? 'primary' : 'info'; ?>">
                                <?php echo htmlspecialchars($user['role']); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($userLocation): ?>
                                <?php echo htmlspecialchars($userLocation['name']); ?>
                            <?php else: ?>
                                <span class="badge badge-success">Global</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($user['created_at']); ?></td>
                        <td>
                            <button class="icon-btn" onclick='editUser(<?php echo json_encode($user); ?>)' title="Bearbeiten">
                                <span class="material-icons">edit</span>
                            </button>
                            <button class="icon-btn" onclick="deleteUser('<?php echo $user['id']; ?>', '<?php echo htmlspecialchars($user['username']); ?>')" title="Löschen">
                                <span class="material-icons">delete</span>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div style="margin-top: 1.5rem; padding: 1rem; background-color: rgba(255, 152, 0, 0.1); border-left: 4px solid var(--warning-color); border-radius: 4px;">
            <strong>Sicherheitshinweis:</strong> Stellen Sie sicher, dass Sie das Standard-Admin-Passwort geändert haben!
        </div>
    </div>
</div>

<!-- User Modal -->
<div id="user-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title" id="modal-title">Benutzer hinzufügen</h2>
            <button class="modal-close" onclick="closeUserModal()">&times;</button>
        </div>
        <form id="user-form">
            <input type="hidden" id="user-id" name="id">
            
            <div class="form-group">
                <label class="form-label" for="username">Benutzername *</label>
                <input type="text" id="username" name="username" class="form-input" required>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="email">E-Mail-Adresse</label>
                <input type="email" id="email" name="email" class="form-input" placeholder="benutzer@example.com">
                <small style="color: var(--text-secondary); display: block; margin-top: 0.25rem;">
                    Optional. Wird für die Passwort-Wiederherstellung benötigt.
                </small>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="password">Passwort *</label>
                <input type="password" id="password" name="password" class="form-input" required>
                <small class="form-error" id="password-hint">Bei Bearbeitung leer lassen, um Passwort nicht zu ändern</small>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="role">Rolle *</label>
                <select id="role" name="role" class="form-select" required>
                    <option value="operator">Operator (kann nur Formulare ausfüllen)</option>
                    <option value="admin">Administrator (voller Zugriff)</option>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="location_id">Standort</label>
                <select id="location_id" name="location_id" class="form-select" <?php if ($hasLocationRestriction): ?>disabled<?php endif; ?>>
                    <option value="">Global (alle Standorte)</option>
                    <?php foreach ($locations as $location): ?>
                        <option value="<?php echo htmlspecialchars($location['id']); ?>" <?php if ($hasLocationRestriction && $location['id'] === $userLocationId): ?>selected<?php endif; ?>>
                            <?php echo htmlspecialchars($location['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($hasLocationRestriction): ?>
                <input type="hidden" name="location_id" value="<?php echo htmlspecialchars($userLocationId); ?>">
                <small style="color: var(--text-secondary); display: block; margin-top: 0.25rem;">
                    Der Standort ist auf Ihren zugewiesenen Standort festgelegt.
                </small>
                <?php else: ?>
                <small style="color: var(--text-secondary); display: block; margin-top: 0.25rem;">
                    Global: Benutzer kann alle Standorte sehen und bearbeiten<br>
                    Standort-spezifisch: Benutzer sieht nur Fahrzeuge und Einsatzkräfte des zugewiesenen Standorts
                </small>
                <?php endif; ?>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeUserModal()">Abbrechen</button>
                <button type="submit" class="btn btn-primary">Speichern</button>
            </div>
        </form>
    </div>
</div>

<script>
function openUserModal() {
    document.getElementById('user-modal').classList.add('show');
    document.getElementById('modal-title').textContent = 'Benutzer hinzufügen';
    document.getElementById('user-form').reset();
    document.getElementById('user-id').value = '';
    document.getElementById('password').required = true;
    document.getElementById('password-hint').style.display = 'none';
}

function closeUserModal() {
    document.getElementById('user-modal').classList.remove('show');
}

function editUser(user) {
    document.getElementById('user-modal').classList.add('show');
    document.getElementById('modal-title').textContent = 'Benutzer bearbeiten';
    document.getElementById('user-id').value = user.id;
    document.getElementById('username').value = user.username;
    document.getElementById('email').value = user.email || '';
    document.getElementById('password').value = '';
    document.getElementById('password').required = false;
    document.getElementById('password-hint').style.display = 'block';
    document.getElementById('role').value = user.role;
    document.getElementById('location_id').value = user.location_id || '';
}

async function deleteUser(id, username) {
    if (!confirm(`Möchten Sie den Benutzer "${username}" wirklich löschen?`)) {
        return;
    }
    
    try {
        const response = await fetch('/src/php/api/users.php', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id })
        });
        
        const result = await response.json();
        
        if (result.success) {
            window.feuerwehrApp.showAlert('success', result.message);
            window.feuerwehrApp.loadPage('users');
        } else {
            window.feuerwehrApp.showAlert('error', result.message);
        }
    } catch (error) {
        window.feuerwehrApp.showAlert('error', 'Fehler beim Löschen');
    }
}

// Form submission
document.getElementById('user-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const data = {
        username: formData.get('username'),
        role: formData.get('role')
    };
    
    const email = formData.get('email');
    if (email) {
        data.email = email;
    }
    
    const password = formData.get('password');
    if (password) {
        data.password = password;
    }
    
    const locationId = formData.get('location_id');
    if (locationId) {
        data.location_id = locationId;
    } else {
        data.location_id = null; // Explicitly set null for global access
    }
    
    const id = formData.get('id');
    const method = id ? 'PUT' : 'POST';
    
    if (id) {
        data.id = id;
    }
    
    try {
        const response = await fetch('/src/php/api/users.php', {
            method: method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            window.feuerwehrApp.showAlert('success', result.message);
            closeUserModal();
            window.feuerwehrApp.loadPage('users');
        } else {
            window.feuerwehrApp.showAlert('error', result.message);
        }
    } catch (error) {
        window.feuerwehrApp.showAlert('error', 'Fehler beim Speichern');
    }
});
</script>
