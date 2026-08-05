<?php
/**
 * Global search page
 */
require_once __DIR__ . '/../auth.php';
Auth::requireOperator();
?>

<div class="page-container">
    <div class="page-header">
        <h1 class="page-title"><span class="material-icons">search</span> Globale Suche</h1>
        <p class="page-subtitle">Personal, Fahrzeuge, Übungen und Einsätze durchsuchen</p>
    </div>

    <div class="card">
        <div class="card-content">
            <div class="form-group">
                <label class="form-label" for="global-search-q">Suchbegriff</label>
                <input type="search" id="global-search-q" class="form-input" placeholder="Name, Funkrufname, Thema, Ort…" autofocus>
            </div>
            <div id="search-results" style="margin-top: 1rem;"></div>
        </div>
    </div>
</div>

<script>
(function () {
    const input = document.getElementById('global-search-q');
    const host = document.getElementById('search-results');
    let timer = null;

    function escapeHtml(t) {
        const d = document.createElement('div');
        d.textContent = t == null ? '' : String(t);
        return d.innerHTML;
    }

    function section(title, items, renderItem) {
        if (!items.length) return '';
        return `<div style="margin-bottom:1.25rem;">
            <h3 style="margin:0 0 0.5rem;font-size:1rem;">${title} (${items.length})</h3>
            <ul style="list-style:none;padding:0;margin:0;">${items.map(renderItem).join('')}</ul>
        </div>`;
    }

    async function runSearch(q) {
        if (q.length < 2) {
            host.innerHTML = '<p style="color:var(--text-secondary);">Mindestens 2 Zeichen eingeben.</p>';
            return;
        }
        host.innerHTML = '<div class="spinner"></div>';
        const res = await fetch('/src/php/api/search.php?q=' + encodeURIComponent(q));
        const data = await res.json();
        const d = data.data || {};
        const html = [
            section('Personal', d.personnel || [], p =>
                `<li class="search-result-item" style="padding:0.5rem 0;border-bottom:1px solid var(--border-color);cursor:pointer;"
                    onclick="window.feuerwehrApp.navigateTo('personnel')">
                    <strong>${escapeHtml(p.name)}</strong>
                </li>`),
            section('Fahrzeuge', d.vehicles || [], v =>
                `<li class="search-result-item" style="padding:0.5rem 0;border-bottom:1px solid var(--border-color);cursor:pointer;"
                    onclick="window.feuerwehrApp.navigateTo('vehicles')">
                    <strong>${escapeHtml(v.type)}</strong>
                    <span style="color:var(--text-secondary);"> · ${escapeHtml(v.radio_call_sign || '')}</span>
                </li>`),
            section('Übungen', d.attendance || [], a =>
                `<li class="search-result-item" style="padding:0.5rem 0;border-bottom:1px solid var(--border-color);cursor:pointer;"
                    onclick="window.feuerwehrApp.navigateTo('form-data')">
                    <strong>${escapeHtml(a.thema || a.description || 'Übung')}</strong>
                    <span style="color:var(--text-secondary);"> · ${escapeHtml(a.datum || a.date || '')}</span>
                </li>`),
            section('Einsätze', d.missions || [], m =>
                `<li class="search-result-item" style="padding:0.5rem 0;border-bottom:1px solid var(--border-color);cursor:pointer;"
                    onclick="window.feuerwehrApp.navigateTo('form-data')">
                    <strong>${escapeHtml(m.einsatzgrund || m.mission_type || 'Einsatz')}</strong>
                    <span style="color:var(--text-secondary);"> · ${escapeHtml(m.einsatzort || m.location || '')} · ${escapeHtml(m.einsatzdatum || m.date || '')}</span>
                </li>`),
        ].join('');

        host.innerHTML = html || '<p style="color:var(--text-secondary);">Keine Treffer.</p>';
    }

    input.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(() => runSearch(input.value.trim()), 250);
    });
})();
</script>
