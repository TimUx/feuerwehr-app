<?php
/**
 * Email Settings Page - SMTP Configuration
 */

require_once __DIR__ . '/../auth.php';

Auth::requireAuth();
Auth::requireGlobalAdmin();

$user = Auth::getUser();

// Load current config
$configFile = __DIR__ . '/../../../config/config.php';
$config = file_exists($configFile) ? require $configFile : [];
$emailConfig = $config['email'] ?? [];
?>

<div class="page-container">
    <div class="page-header">
        <h1 class="page-title">
            <span class="material-icons">email</span>
            E-Mail Einstellungen
        </h1>
        <p class="page-subtitle">SMTP-Konfiguration für Formular-Versand</p>
    </div>

    <div class="card">
        <form id="emailSettingsForm">
            <div class="form-section">
                <h3 class="form-section-title">SMTP Server Einstellungen</h3>
                
                <div class="form-row">
                    <div class="form-group" style="flex: 2;">
                        <label class="form-label" for="smtp_host">SMTP Server *</label>
                        <input type="text" 
                               id="smtp_host" 
                               name="smtp_host" 
                               class="form-input" 
                               value="<?php echo htmlspecialchars($emailConfig['smtp_host'] ?? 'localhost'); ?>" 
                               placeholder="smtp.example.com" 
                               required>
                        <small class="form-help">Hostname oder IP-Adresse des SMTP-Servers</small>
                    </div>
                    
                    <div class="form-group" style="flex: 1;">
                        <label class="form-label" for="smtp_port">Port *</label>
                        <input type="number" 
                               id="smtp_port" 
                               name="smtp_port" 
                               class="form-input" 
                               value="<?php echo htmlspecialchars($emailConfig['smtp_port'] ?? '587'); ?>" 
                               placeholder="587" 
                               min="1" 
                               max="65535" 
                               required>
                        <small class="form-help">Meist 587 (TLS) oder 465 (SSL)</small>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="smtp_secure">Verschlüsselung</label>
                    <select id="smtp_secure" name="smtp_secure" class="form-input">
                        <option value="" <?php echo empty($emailConfig['smtp_secure']) ? 'selected' : ''; ?>>Keine</option>
                        <option value="tls" <?php echo ($emailConfig['smtp_secure'] ?? '') === 'tls' ? 'selected' : ''; ?>>TLS (empfohlen)</option>
                        <option value="ssl" <?php echo ($emailConfig['smtp_secure'] ?? '') === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                    </select>
                    <small class="form-help">Empfohlen: TLS für Port 587, SSL für Port 465</small>
                </div>
            </div>
            
            <div class="form-section">
                <h3 class="form-section-title">Authentifizierung</h3>
                
                <div class="form-group">
                    <label class="form-checkbox">
                        <input type="checkbox" 
                               id="smtp_auth" 
                               name="smtp_auth" 
                               <?php echo !empty($emailConfig['smtp_auth']) ? 'checked' : ''; ?>>
                        <span>SMTP-Authentifizierung verwenden</span>
                    </label>
                    <small class="form-help">Aktivieren, wenn der Server Anmeldedaten benötigt</small>
                </div>
                
                <div id="authFields" style="display: <?php echo !empty($emailConfig['smtp_auth']) ? 'block' : 'none'; ?>;">
                    <div class="form-group">
                        <label class="form-label" for="smtp_username">Benutzername</label>
                        <input type="text" 
                               id="smtp_username" 
                               name="smtp_username" 
                               class="form-input" 
                               value="<?php echo htmlspecialchars($emailConfig['smtp_username'] ?? ''); ?>" 
                               placeholder="user@example.com"
                               autocomplete="off">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="smtp_password">Passwort</label>
                        <input type="password" 
                               id="smtp_password" 
                               name="smtp_password" 
                               class="form-input" 
                               value="<?php echo htmlspecialchars($emailConfig['smtp_password'] ?? ''); ?>" 
                               placeholder="••••••••"
                               autocomplete="new-password">
                        <small class="form-help">Passwort wird verschlüsselt gespeichert</small>
                    </div>
                </div>
            </div>
            
            <div class="form-section">
                <h3 class="form-section-title">E-Mail Adressen</h3>
                
                <div class="form-group">
                    <label class="form-label" for="from_address">Absender-Adresse *</label>
                    <input type="email" 
                           id="from_address" 
                           name="from_address" 
                           class="form-input" 
                           value="<?php echo htmlspecialchars($emailConfig['from_address'] ?? 'noreply@feuerwehr.local'); ?>" 
                           placeholder="noreply@feuerwehr.de" 
                           required>
                    <small class="form-help">E-Mail-Adresse, von der alle Formulare versendet werden</small>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="from_name">Absender-Name</label>
                    <input type="text" 
                           id="from_name" 
                           name="from_name" 
                           class="form-input" 
                           value="<?php echo htmlspecialchars($emailConfig['from_name'] ?? 'Feuerwehr Management System'); ?>" 
                           placeholder="Feuerwehr Willingshausen">
                </div>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="window.feuerwehrApp.navigateTo('home')">
                    <span class="material-icons">arrow_back</span>
                    Zurück
                </button>
                <button type="button" class="btn btn-secondary" onclick="testEmailSettings()">
                    <span class="material-icons">send</span>
                    Test-E-Mail senden
                </button>
                <button type="submit" class="btn btn-primary">
                    <span class="material-icons">save</span>
                    Speichern
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Toggle auth fields
document.getElementById('smtp_auth').addEventListener('change', function() {
    document.getElementById('authFields').style.display = this.checked ? 'block' : 'none';
});

// Form submission
document.getElementById('emailSettingsForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = {};
    
    // Convert FormData to object
    for (let [key, value] of formData.entries()) {
        if (key === 'smtp_auth') {
            data[key] = true;
        } else {
            data[key] = value;
        }
    }
    
    // Add smtp_auth as false if not checked
    if (!formData.has('smtp_auth')) {
        data['smtp_auth'] = false;
    }
    
    try {
        const response = await fetch('/src/php/api/email-settings.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('✅ E-Mail-Einstellungen erfolgreich gespeichert!');
        } else {
            alert('❌ Fehler beim Speichern: ' + (result.error || 'Unbekannter Fehler'));
        }
    } catch (error) {
        alert('❌ Fehler beim Speichern: ' + error.message);
    }
});

// Test email function
async function testEmailSettings() {
    if (!confirm('Eine Test-E-Mail an die konfigurierte Empfänger-Adresse senden?')) {
        return;
    }
    
    try {
        const response = await fetch('/src/php/api/email-settings.php?action=test', {
            method: 'POST'
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('✅ Test-E-Mail erfolgreich versendet!\nBitte prüfen Sie Ihr Postfach.');
        } else {
            alert('❌ Test-E-Mail konnte nicht versendet werden:\n' + (result.error || 'Unbekannter Fehler'));
        }
    } catch (error) {
        alert('❌ Fehler beim Senden der Test-E-Mail: ' + error.message);
    }
}
</script>
