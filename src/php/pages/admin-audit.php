<?php
/**
 * Admin: Audit-Log
 */
require_once __DIR__ . '/../auth.php';
Auth::requireAdmin();
?>

<div class="page-container">
    <div class="page-header">
        <h1 class="page-title"><span class="material-icons">history</span> Audit-Log</h1>
        <p class="page-subtitle">Wer hat wann welche Änderungen vorgenommen</p>
    </div>

    <div class="card">
        <div class="card-content">
            <div class="form-group" style="max-width: 280px;">
                <label class="form-label" for="audit-filter">Filtern</label>
                <input type="search" id="audit-filter" class="form-input" placeholder="Aktion, Benutzer, IP…">
            </div>
            <div class="table-responsive">
                <table class="table" id="audit-table">
                    <thead>
                        <tr>
                            <th>Zeit</th>
                            <th>Benutzer</th>
                            <th>Aktion</th>
                            <th>Details</th>
                            <th>IP</th>
                        </tr>
                    </thead>
                    <tbody id="audit-body">
                        <tr><td colspan="5">Laden…</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
(async function () {
    let rows = [];
    const body = document.getElementById('audit-body');

    function render(filter = '') {
        const f = filter.trim().toLowerCase();
        const filtered = !f ? rows : rows.filter(r => {
            const hay = [r.ts, r.username, r.action, r.ip, JSON.stringify(r.details || {})].join(' ').toLowerCase();
            return hay.includes(f);
        });
        if (!filtered.length) {
            body.innerHTML = '<tr><td colspan="5">Keine Einträge</td></tr>';
            return;
        }
        body.innerHTML = filtered.map(r => {
            const when = r.ts ? new Date(r.ts).toLocaleString('de-DE') : '–';
            const details = r.details && Object.keys(r.details).length
                ? `<code style="font-size:0.8rem;">${escapeHtml(JSON.stringify(r.details))}</code>`
                : '–';
            return `<tr>
                <td>${escapeHtml(when)}</td>
                <td>${escapeHtml(r.username || '–')}</td>
                <td><code>${escapeHtml(r.action || '')}</code></td>
                <td>${details}</td>
                <td>${escapeHtml(r.ip || '–')}</td>
            </tr>`;
        }).join('');
    }

    function escapeHtml(t) {
        const d = document.createElement('div');
        d.textContent = t == null ? '' : String(t);
        return d.innerHTML;
    }

    document.getElementById('audit-filter').addEventListener('input', (e) => render(e.target.value));

    const res = await fetch('/src/php/api/audit.php?limit=300');
    const data = await res.json();
    rows = data.data || [];
    render();
})();
</script>
