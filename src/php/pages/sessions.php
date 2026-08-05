<?php
/**
 * Session overview – remember-me devices
 */
require_once __DIR__ . '/../auth.php';
Auth::requireAuth();
$isAdmin = Auth::isAdmin();
?>

<div class="page-container">
    <div class="page-header">
        <h1 class="page-title"><span class="material-icons">devices</span> Sitzungen</h1>
        <p class="page-subtitle">Angemeldete Geräte („Angemeldet bleiben“) verwalten und widerrufen</p>
    </div>

    <div class="card">
        <div class="card-content">
            <div style="display:flex; flex-wrap:wrap; gap:0.75rem; margin-bottom:1rem;">
                <button type="button" class="btn btn-danger" id="btn-revoke-all">
                    <span class="material-icons">logout</span> Alle meine Sitzungen beenden
                </button>
                <?php if ($isAdmin): ?>
                <label class="form-check" style="display:flex;align-items:center;gap:0.4rem;margin:0;">
                    <input type="checkbox" id="show-all-sessions">
                    <span>Alle Benutzer anzeigen (Admin)</span>
                </label>
                <?php endif; ?>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <?php if ($isAdmin): ?><th>Benutzer</th><?php endif; ?>
                            <th>Gerät / Browser</th>
                            <th>IP</th>
                            <th>Erstellt</th>
                            <th>Gültig bis</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="sessions-body">
                        <tr><td colspan="6">Laden…</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
(async function () {
    const isAdmin = <?php echo $isAdmin ? 'true' : 'false'; ?>;
    const body = document.getElementById('sessions-body');

    async function load() {
        const all = isAdmin && document.getElementById('show-all-sessions')?.checked;
        const res = await fetch('/src/php/api/sessions.php' + (all ? '?all=1' : ''));
        const data = await res.json();
        const rows = data.data || [];
        if (!rows.length) {
            body.innerHTML = `<tr><td colspan="${isAdmin ? 6 : 5}">Keine aktiven „Angemeldet bleiben“-Sitzungen.</td></tr>`;
            return;
        }
        body.innerHTML = rows.map(s => {
            const ua = (s.user_agent || 'unbekannt').slice(0, 80);
            const created = s.created ? new Date(s.created * 1000).toLocaleString('de-DE') : '–';
            const expiry = s.expiry ? new Date(s.expiry * 1000).toLocaleString('de-DE') : '–';
            const badge = s.current ? ' <span class="badge badge-primary">dieses Gerät</span>' : '';
            return `<tr>
                ${isAdmin ? `<td>${escapeHtml(s.username || s.user_id || '–')}</td>` : ''}
                <td>${escapeHtml(ua)}${badge}</td>
                <td>${escapeHtml(s.ip || '–')}</td>
                <td>${escapeHtml(created)}</td>
                <td>${escapeHtml(expiry)}</td>
                <td>
                    <button type="button" class="icon-btn" style="color:var(--error-color)" title="Widerrufen"
                        onclick="revokeSession('${escapeHtml(s.id)}')">
                        <span class="material-icons">delete</span>
                    </button>
                </td>
            </tr>`;
        }).join('');
    }

    function escapeHtml(t) {
        const d = document.createElement('div');
        d.textContent = t == null ? '' : String(t);
        return d.innerHTML;
    }

    window.revokeSession = async function (id) {
        const ok = await window.feuerwehrApp.confirmAction('Sitzung beenden', 'Dieses Gerät abmelden?');
        if (!ok) return;
        const res = await fetch('/src/php/api/sessions.php?action=revoke', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });
        const data = await res.json();
        if (data.success) {
            window.feuerwehrApp.showAlert('success', data.message);
            load();
        } else {
            window.feuerwehrApp.showAlert('error', data.message || 'Fehler');
        }
    };

    document.getElementById('btn-revoke-all').addEventListener('click', async () => {
        const ok = await window.feuerwehrApp.confirmAction(
            'Alle Sitzungen beenden',
            'Alle „Angemeldet bleiben“-Geräte für Ihr Konto widerrufen?'
        );
        if (!ok) return;
        const res = await fetch('/src/php/api/sessions.php?action=revoke', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ all: true })
        });
        const data = await res.json();
        if (data.success) {
            window.feuerwehrApp.showAlert('success', data.message);
            load();
        } else {
            window.feuerwehrApp.showAlert('error', data.message || 'Fehler');
        }
    });

    document.getElementById('show-all-sessions')?.addEventListener('change', load);
    load();
})();
</script>
