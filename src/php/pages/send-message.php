<?php
/**
 * Send ntfy push messages (per-location configuration).
 */

require_once __DIR__ . '/../auth.php';

Auth::requireAuth();

$user = Auth::getUser();
$canSendAll = Auth::isOperatorOrAbove() && !Auth::hasLocationRestriction();
$hasOwnLocation = !empty(Auth::getUserLocationId());
?>

<div class="card">
    <div class="card-header">
        <span>Nachricht senden (ntfy)</span>
    </div>
    <div class="card-content">
        <p style="color: var(--text-secondary); margin-bottom: 1rem;">
            Sendet eine Benachrichtigung über den Dienst <strong>ntfy</strong> an den konfigurierten Kanal des eigenen Standorts oder optional an alle Standorte.
            Die ntfy-Adresse (Publish-URL) und ggf. Zugangsschlüssel werden in der Standortverwaltung gepflegt.
        </p>

        <form id="send-ntfy-form">
            <div class="form-group">
                <label class="form-label">Empfang</label>
                <?php if ($canSendAll): ?>
                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                    <label class="form-check" style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                        <input type="radio" name="scope" value="own" <?php echo $hasOwnLocation ? 'checked' : 'disabled'; ?>>
                        <span>Nur mein Standort</span>
                    </label>
                    <label class="form-check" style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                        <input type="radio" name="scope" value="all" <?php echo !$hasOwnLocation ? 'checked' : ''; ?>>
                        <span>Alle Standorte (mit hinterlegter ntfy-URL)</span>
                    </label>
                </div>
                <?php else: ?>
                <input type="hidden" name="scope" value="own">
                <p style="margin: 0;">Nur <strong>mein Standort</strong> (gemäß Ihrer Berechtigung).</p>
                <?php endif; ?>
                <?php if (!$hasOwnLocation && !$canSendAll): ?>
                <p style="color: var(--error-color, #c62828); margin-top: 0.75rem;">
                    Ihrem Benutzer ist kein Standort zugewiesen. Bitte wenden Sie sich an einen Administrator.
                </p>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label" for="ntfy-title">Titel (optional)</label>
                <input type="text" id="ntfy-title" name="title" class="form-input" maxlength="250" placeholder="z.B. Übung heute Abend">
            </div>

            <div class="form-group">
                <label class="form-label" for="ntfy-message">Nachricht *</label>
                <textarea id="ntfy-message" name="message" class="form-textarea" rows="5" maxlength="4096" required placeholder="Kurzer Hinweistext …"></textarea>
            </div>

            <div class="form-group">
                <label class="form-label" for="ntfy-ttl">TTL in Sekunden (optional)</label>
                <input type="number" id="ntfy-ttl" name="ttl" class="form-input" min="1" max="<?php echo 86400 * 30; ?>" step="1" placeholder="z.B. 3600">
                <small class="form-help" style="display: block; color: var(--text-secondary); margin-top: 0.25rem;">
                    Wird als HTTP-Header <code style="font-size: 0.85em;">X-Ntfy-TTL</code> mitgesendet. Standard-ntfy-Server ignorieren das oft; bei manchen Installationen oder Proxies kann es die Cache-Zeit steuern.
                </small>
            </div>

            <div class="form-group" style="margin-top: 1rem;">
                <button type="submit" class="btn btn-primary" id="send-ntfy-submit" <?php echo (!$hasOwnLocation && !$canSendAll) ? 'disabled' : ''; ?>>
                    <span class="material-icons">send</span>
                    Senden
                </button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
  const form = document.getElementById('send-ntfy-form');
  if (!form) return;

  form.addEventListener('submit', async function (e) {
    e.preventDefault();
    const submitBtn = document.getElementById('send-ntfy-submit');
    const scopeEl = form.querySelector('input[name="scope"]:checked') || form.querySelector('input[name="scope"]');
    const scope = scopeEl ? scopeEl.value : 'own';
    const message = (document.getElementById('ntfy-message').value || '').trim();
    const title = (document.getElementById('ntfy-title').value || '').trim();
    const ttlRaw = (document.getElementById('ntfy-ttl').value || '').trim();
    const ttl = ttlRaw === '' ? null : parseInt(ttlRaw, 10);

    if (!message) {
      window.feuerwehrApp.showAlert('error', 'Bitte einen Nachrichtentext eingeben.');
      return;
    }

    const payload = { scope, message };
    if (title) payload.title = title;
    if (ttl !== null && !Number.isNaN(ttl)) payload.ttl = ttl;

    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.dataset.prevHtml = submitBtn.innerHTML;
      submitBtn.innerHTML = '<span class="material-icons" style="animation:spin 1s linear infinite;vertical-align:middle;">refresh</span>';
    }

    try {
      const response = await fetch('/src/php/api/ntfy-send.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
      });
      const result = await response.json();
      if (result.success) {
        window.feuerwehrApp.showAlert('success', result.message);
        form.reset();
      } else if (result.partial) {
        window.feuerwehrApp.showAlert('warning', result.message);
      } else {
        window.feuerwehrApp.showAlert('error', result.message || 'Versand fehlgeschlagen');
      }
    } catch (err) {
      window.feuerwehrApp.showAlert('error', 'Netzwerkfehler beim Senden');
    } finally {
      if (submitBtn) {
        submitBtn.disabled = false;
        if (submitBtn.dataset.prevHtml) submitBtn.innerHTML = submitBtn.dataset.prevHtml;
      }
    }
  });
})();
</script>
