<?php
/**
 * Phone Numbers Page (Read-Only) - Important Phone Numbers
 */

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../datastore.php';

Auth::requireOperator();

$dataStore = new DataStore();
$phoneNumbers = $dataStore->getPhoneNumbers();
?>

<div class="page-header">
    <h2>Wichtige Telefonnummern</h2>
</div>

<!-- Search Box -->
<div class="search-box" style="margin-bottom: 1.5rem;">
    <div class="form-group" style="margin: 0;">
        <input type="text" id="phoneNumberSearch" class="form-input" 
               placeholder="Suchen nach Name, Organisation oder Funktion..." 
               style="width: 100%; max-width: 500px;">
    </div>
</div>

<div class="phone-numbers-list" id="phoneNumbersList">
    <?php if (empty($phoneNumbers)): ?>
        <div class="empty-state">
            <span class="material-icons">phone</span>
            <p>Keine Telefonnummern vorhanden</p>
        </div>
    <?php else: ?>
        <?php foreach ($phoneNumbers as $phone): ?>
        <div class="phone-number-card" 
             data-name="<?php echo htmlspecialchars(strtolower($phone['name'])); ?>"
             data-org="<?php echo htmlspecialchars(strtolower($phone['organization'])); ?>"
             data-role="<?php echo htmlspecialchars(strtolower($phone['role'])); ?>">
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
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
// Search functionality
document.getElementById('phoneNumberSearch')?.addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const phoneCards = document.querySelectorAll('.phone-number-card');
    
    phoneCards.forEach(card => {
        const name = card.dataset.name || '';
        const org = card.dataset.org || '';
        const role = card.dataset.role || '';
        
        const matches = name.includes(searchTerm) || 
                       org.includes(searchTerm) || 
                       role.includes(searchTerm);
        
        card.style.display = matches ? '' : 'none';
    });
});
</script>
