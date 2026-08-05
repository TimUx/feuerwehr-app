<?php
/**
 * Admin: Backup & Export
 */
require_once __DIR__ . '/../auth.php';
Auth::requireAdmin();
?>

<div class="page-container">
    <div class="page-header">
        <h1 class="page-title"><span class="material-icons">backup</span> Datensicherung &amp; Export</h1>
        <p class="page-subtitle">Vollbackups erstellen und Datensätze als JSON/CSV exportieren</p>
    </div>

    <div class="card" style="margin-bottom: 1.5rem;">
        <div class="card-header">Vollbackup</div>
        <div class="card-content">
            <p style="color: var(--text-secondary); margin-bottom: 1rem;">
                Erstellt eine Momentaufnahme aller verschlüsselten Datendateien. Es werden die letzten 15 Vollbackups behalten.
            </p>
            <button type="button" class="btn btn-primary" id="btn-create-backup">
                <span class="material-icons">save</span> Backup jetzt erstellen
            </button>
            <div id="backup-list" style="margin-top: 1.25rem;"></div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Export für Audits</div>
        <div class="card-content">
            <p style="color: var(--text-secondary); margin-bottom: 1rem;">
                Exportiert entschlüsselte Daten ohne Passwort-Hashes und SMTP-Geheimnisse.
            </p>
            <div class="form-group" id="export-datasets" style="display:grid; grid-template-columns: repeat(auto-fill,minmax(160px,1fr)); gap:0.5rem; margin-bottom:1rem;"></div>
            <div style="display:flex; flex-wrap:wrap; gap:0.75rem;">
                <button type="button" class="btn btn-primary" id="btn-export-json">
                    <span class="material-icons">download</span> JSON herunterladen
                </button>
                <button type="button" class="btn btn-secondary" id="btn-export-csv">
                    <span class="material-icons">table_view</span> CSV herunterladen
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(async function () {
    const datasets = ['personnel','vehicles','locations','attendance','missions','phone_numbers','settings','audit'];
    const labels = {
        personnel: 'Personal', vehicles: 'Fahrzeuge', locations: 'Standorte',
        attendance: 'Anwesenheit', missions: 'Einsätze', phone_numbers: 'Telefonnummern',
        settings: 'Einstellungen', audit: 'Audit-Log'
    };
    const box = document.getElementById('export-datasets');
    datasets.forEach(d => {
        box.insertAdjacentHTML('beforeend', `
            <label class="form-check" style="display:flex;align-items:center;gap:0.4rem;">
                <input type="checkbox" name="ds" value="${d}" checked>
                <span>${labels[d] || d}</span>
            </label>`);
    });

    function selectedDatasets() {
        return Array.from(document.querySelectorAll('input[name="ds"]:checked')).map(i => i.value).join(',');
    }

    async function loadBackups() {
        const res = await fetch('/src/php/api/export.php?action=list');
        const data = await res.json();
        const host = document.getElementById('backup-list');
        if (!data.success || !data.backups?.length) {
            host.innerHTML = '<p style="color:var(--text-secondary);">Noch keine Vollbackups.</p>';
            return;
        }
        host.innerHTML = '<table class="table"><thead><tr><th>Backup</th><th>Erstellt</th><th>Dateien</th></tr></thead><tbody>' +
            data.backups.map(b => `<tr>
                <td><code>${b.id}</code></td>
                <td>${b.created ? new Date(b.created * 1000).toLocaleString('de-DE') : '–'}</td>
                <td>${b.files}</td>
            </tr>`).join('') + '</tbody></table>';
    }

    document.getElementById('btn-create-backup').addEventListener('click', async () => {
        const ok = await window.feuerwehrApp.confirmAction('Backup erstellen', 'Jetzt ein Vollbackup aller Datendateien anlegen?');
        if (!ok) return;
        const res = await fetch('/src/php/api/export.php?action=backup', { method: 'POST' });
        const data = await res.json();
        if (data.success) {
            window.feuerwehrApp.showAlert('success', data.message + ': ' + data.id);
            loadBackups();
        } else {
            window.feuerwehrApp.showAlert('error', data.message || 'Fehler');
        }
    });

    document.getElementById('btn-export-json').addEventListener('click', () => {
        window.location.href = '/src/php/api/export.php?action=download&format=json&datasets=' + encodeURIComponent(selectedDatasets());
    });
    document.getElementById('btn-export-csv').addEventListener('click', () => {
        window.location.href = '/src/php/api/export.php?action=download&format=csv&datasets=' + encodeURIComponent(selectedDatasets());
    });

    loadBackups();
})();
</script>
