<?php
/**
 * Phone Numbers Page - Important Phone Numbers
 */

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../datastore.php';

Auth::requireOperator();

$user = Auth::getUser();
$isAdmin = Auth::isAdmin();

$dataStore = new DataStore();
$phoneNumbers = $dataStore->getPhoneNumbers();
?>

<div class="page-header">
    <h2>Wichtige Telefonnummern</h2>
    <?php if ($isAdmin): ?>
    <button onclick="showAddPhoneNumberModal()" class="btn btn-primary">
        <span class="material-icons">add</span>
        HINZUFÜGEN
    </button>
    <?php endif; ?>
</div>

<div class="phone-numbers-list">
    <?php if (empty($phoneNumbers)): ?>
        <div class="empty-state">
            <span class="material-icons">phone</span>
            <p>Keine Telefonnummern vorhanden</p>
            <?php if ($isAdmin): ?>
            <button onclick="showAddPhoneNumberModal()" class="btn btn-primary">
                Erste Telefonnummer hinzufügen
            </button>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <?php foreach ($phoneNumbers as $phone): ?>
        <div class="phone-number-card">
            <div class="phone-number-info">
                <h3 class="phone-number-name"><?php echo htmlspecialchars($phone['name']); ?></h3>
                <p class="phone-number-org"><?php echo htmlspecialchars($phone['organization']); ?></p>
                <p class="phone-number-role"><?php echo htmlspecialchars($phone['role']); ?></p>
            </div>
            <div class="phone-number-actions">
                <a href="tel:<?php echo htmlspecialchars($phone['phone']); ?>" class="btn btn-primary btn-call">
                    <span class="material-icons">phone</span>
                    <?php echo htmlspecialchars($phone['phone']); ?>
                </a>
                <?php if ($isAdmin): ?>
                <button onclick="editPhoneNumber(<?php echo htmlspecialchars(json_encode($phone)); ?>)" class="btn btn-secondary btn-sm">
                    <span class="material-icons">edit</span>
                </button>
                <button onclick="deletePhoneNumber('<?php echo htmlspecialchars($phone['id']); ?>')" class="btn btn-danger btn-sm">
                    <span class="material-icons">delete</span>
                </button>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php if ($isAdmin): ?>
<!-- Add/Edit Phone Number Modal -->
<div id="phoneNumberModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="phoneNumberModalTitle">Telefonnummer hinzufügen</h3>
            <button class="close-modal" onclick="closePhoneNumberModal()">&times;</button>
        </div>
        <form id="phoneNumberForm" onsubmit="savePhoneNumber(event)">
            <input type="hidden" id="phoneNumberId" name="id">
            
            <div class="form-group">
                <label for="phoneNumberName">Name *</label>
                <input type="text" id="phoneNumberName" name="name" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="phoneNumberOrg">Firma / Organisation *</label>
                <input type="text" id="phoneNumberOrg" name="organization" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="phoneNumberRole">Funktion *</label>
                <input type="text" id="phoneNumberRole" name="role" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="phoneNumberPhone">Telefonnummer *</label>
                <input type="tel" id="phoneNumberPhone" name="phone" class="form-control" required 
                       placeholder="+49 123 456789">
            </div>
            
            <div class="modal-footer">
                <button type="button" onclick="closePhoneNumberModal()" class="btn btn-secondary">Abbrechen</button>
                <button type="submit" class="btn btn-primary">Speichern</button>
            </div>
        </form>
    </div>
</div>

<script>
function showAddPhoneNumberModal() {
    document.getElementById('phoneNumberModalTitle').textContent = 'Telefonnummer hinzufügen';
    document.getElementById('phoneNumberForm').reset();
    document.getElementById('phoneNumberId').value = '';
    document.getElementById('phoneNumberModal').style.display = 'flex';
}

function editPhoneNumber(phone) {
    document.getElementById('phoneNumberModalTitle').textContent = 'Telefonnummer bearbeiten';
    document.getElementById('phoneNumberId').value = phone.id;
    document.getElementById('phoneNumberName').value = phone.name;
    document.getElementById('phoneNumberOrg').value = phone.organization;
    document.getElementById('phoneNumberRole').value = phone.role;
    document.getElementById('phoneNumberPhone').value = phone.phone;
    document.getElementById('phoneNumberModal').style.display = 'flex';
}

function closePhoneNumberModal() {
    document.getElementById('phoneNumberModal').style.display = 'none';
}

async function savePhoneNumber(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const data = Object.fromEntries(formData);
    
    try {
        const response = await fetch('src/php/api/phone-numbers.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            closePhoneNumberModal();
            window.feuerwehrApp.navigateTo('phone-numbers');
        } else {
            alert('Fehler: ' + result.message);
        }
    } catch (error) {
        console.error('Error saving phone number:', error);
        alert('Fehler beim Speichern der Telefonnummer');
    }
}

async function deletePhoneNumber(id) {
    if (!confirm('Möchten Sie diese Telefonnummer wirklich löschen?')) {
        return;
    }
    
    try {
        const response = await fetch('src/php/api/phone-numbers.php', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id })
        });
        
        const result = await response.json();
        
        if (result.success) {
            window.feuerwehrApp.navigateTo('phone-numbers');
        } else {
            alert('Fehler: ' + result.message);
        }
    } catch (error) {
        console.error('Error deleting phone number:', error);
        alert('Fehler beim Löschen der Telefonnummer');
    }
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('phoneNumberModal');
    if (event.target === modal) {
        closePhoneNumberModal();
    }
}
</script>
<?php endif; ?>
