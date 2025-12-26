<?php
/**
 * General Settings Administration Page
 */

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../datastore.php';

Auth::requireAuth();

$settings = DataStore::getSettings();
$isAdmin = Auth::isAdmin();
?>

<div class="card">
    <div class="card-header">
        <span class="material-icons">settings</span>
        Allgemeine Einstellungen
    </div>
    <div class="card-content">
        <form id="settings-form">
            <h3 style="margin-top: 0;">Feuerwehr-Informationen</h3>
            
            <div class="form-group">
                <label class="form-label" for="fire_department_name">Name der Feuerwehr *</label>
                <input type="text" id="fire_department_name" name="fire_department_name" 
                       class="form-input" 
                       value="<?php echo htmlspecialchars($settings['fire_department_name'] ?? 'Freiwillige Feuerwehr'); ?>" 
                       placeholder="z.B. Freiwillige Feuerwehr Willingshausen" 
                       <?php echo $isAdmin ? 'required' : 'readonly'; ?>>
                <small style="color: var(--text-secondary);">Dieser Name wird in E-Mails und PDFs verwendet.</small>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="fire_department_city">Stadt/Gemeinde</label>
                <input type="text" id="fire_department_city" name="fire_department_city" 
                       class="form-input" 
                       value="<?php echo htmlspecialchars($settings['fire_department_city'] ?? ''); ?>" 
                       placeholder="z.B. Willingshausen"
                       <?php echo $isAdmin ? '' : 'readonly'; ?>>
                <small style="color: var(--text-secondary);">Optional: Wird auf separater Zeile angezeigt</small>
            </div>
            
            <h3>Logo</h3>
            
            <?php if ($isAdmin): ?>
            <div class="form-group">
                <?php if (!empty($settings['logo_filename'])): ?>
                    <div class="current-logo" style="margin-bottom: 15px;">
                        <p><strong>Aktuelles Logo:</strong></p>
                        <img src="/data/settings/<?php echo htmlspecialchars($settings['logo_filename']); ?>" 
                             alt="Feuerwehr Logo" 
                             style="max-height: 150px; max-width: 300px; border: 2px solid var(--border-color); border-radius: 8px; padding: 10px; background: white;">
                        <br>
                        <button type="button" class="btn btn-danger" onclick="removeLogo()" style="margin-top: 10px;">
                            <span class="material-icons">delete</span>
                            Logo entfernen
                        </button>
                    </div>
                <?php endif; ?>
                
                <label class="form-label" for="logo">Logo hochladen</label>
                <input type="file" id="logo" name="logo" class="form-input" accept="image/*">
                <small style="color: var(--text-secondary);">
                    Empfohlen: PNG oder SVG, max. 2 MB. Das Logo wird in E-Mails und PDFs verwendet.
                </small>
            </div>
            <?php else: ?>
            <div class="form-group">
                <?php if (!empty($settings['logo_filename'])): ?>
                    <div class="current-logo" style="margin-bottom: 15px;">
                        <p><strong>Aktuelles Logo:</strong></p>
                        <img src="/data/settings/<?php echo htmlspecialchars($settings['logo_filename']); ?>" 
                             alt="Feuerwehr Logo" 
                             style="max-height: 150px; max-width: 300px; border: 2px solid var(--border-color); border-radius: 8px; padding: 10px; background: white;">
                    </div>
                <?php else: ?>
                    <p style="color: var(--text-secondary);">Kein Logo hochgeladen.</p>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <h3>E-Mail-Einstellungen</h3>
            
            <div class="form-group">
                <label class="form-label" for="email_recipient">Standard-Empfänger für Berichte</label>
                <input type="email" id="email_recipient" name="email_recipient" 
                       class="form-input" 
                       value="<?php echo htmlspecialchars($settings['email_recipient'] ?? ''); ?>" 
                       placeholder="z.B. berichte@feuerwehr-beispiel.de"
                       <?php echo $isAdmin ? '' : 'readonly'; ?>>
                <small style="color: var(--text-secondary);">E-Mail-Adresse, an die Einsatzberichte automatisch gesendet werden.</small>
            </div>
            
            <h3>Weitere Einstellungen</h3>
            
            <div class="form-group">
                <label class="form-label" for="contact_phone">Zentrale Telefonnummer</label>
                <input type="tel" id="contact_phone" name="contact_phone" 
                       class="form-input" 
                       value="<?php echo htmlspecialchars($settings['contact_phone'] ?? ''); ?>" 
                       placeholder="z.B. 06691 12345"
                       <?php echo $isAdmin ? '' : 'readonly'; ?>>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="contact_email">Zentrale E-Mail-Adresse</label>
                <input type="email" id="contact_email" name="contact_email" 
                       class="form-input" 
                       value="<?php echo htmlspecialchars($settings['contact_email'] ?? ''); ?>" 
                       placeholder="z.B. info@feuerwehr-beispiel.de"
                       <?php echo $isAdmin ? '' : 'readonly'; ?>>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="address">Adresse</label>
                <textarea id="address" name="address" class="form-textarea" rows="3" 
                          placeholder="Straße, PLZ, Ort"
                          <?php echo $isAdmin ? '' : 'readonly'; ?>><?php echo htmlspecialchars($settings['address'] ?? ''); ?></textarea>
            </div>
            
            <?php if ($isAdmin): ?>
            <div class="form-group mt-3">
                <button type="submit" class="btn btn-primary">
                    <span class="material-icons">save</span>
                    Einstellungen speichern
                </button>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<style>
.mt-3 {
    margin-top: 1.5rem;
}
</style>

<script>
async function removeLogo() {
    if (!confirm('Möchten Sie das Logo wirklich entfernen?')) {
        return;
    }
    
    try {
        const response = await fetch('/src/php/api/settings.php', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'remove_logo' })
        });
        
        const result = await response.json();
        
        if (result.success) {
            window.feuerwehrApp.showAlert('success', 'Logo wurde entfernt');
            window.feuerwehrApp.loadPage('admin-settings');
        } else {
            window.feuerwehrApp.showAlert('error', result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        window.feuerwehrApp.showAlert('error', 'Fehler beim Entfernen des Logos');
    }
}

document.getElementById('settings-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    
    try {
        const response = await fetch('/src/php/api/settings.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            window.feuerwehrApp.showAlert('success', 'Einstellungen wurden gespeichert');
            // Reload page to show updated logo if uploaded
            window.feuerwehrApp.loadPage('admin-settings');
        } else {
            window.feuerwehrApp.showAlert('error', result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        window.feuerwehrApp.showAlert('error', 'Fehler beim Speichern der Einstellungen');
    }
});
</script>
