<?php
/**
 * Hazard Matrix Page - Gefahrenmatrix
 * Based on: https://de.wikipedia.org/wiki/Gefahren_der_Einsatzstelle
 */

require_once __DIR__ . '/../auth.php';

Auth::requireOperator();

$user = Auth::getUser();
?>

<div class="page-header">
    <h2>Gefahrenmatrix</h2>
    <p class="page-description">AAAA-CCCC-EEEE Regel für Gefahren der Einsatzstelle</p>
</div>

<div class="hazard-matrix-container">
    <div class="hazard-info">
        <p><strong>Anleitung:</strong> Tippen Sie auf die Felder, um Gefahren zu markieren/demarkieren.</p>
        <button onclick="clearHazardMatrix()" class="btn btn-secondary btn-sm">
            <span class="material-icons">clear</span>
            Alle zurücksetzen
        </button>
    </div>
    
    <!-- AAAA - Atemgifte -->
    <div class="hazard-section">
        <h3 class="hazard-section-title">AAAA - Atemgifte</h3>
        <div class="hazard-grid">
            <div class="hazard-item" onclick="toggleHazard(this)" data-category="atemgifte" data-item="atemgift">
                <span class="hazard-letter">A</span>
                <span class="hazard-label">Atemgift</span>
            </div>
            <div class="hazard-item" onclick="toggleHazard(this)" data-category="atemgifte" data-item="angstreaktion">
                <span class="hazard-letter">A</span>
                <span class="hazard-label">Angstreaktion</span>
            </div>
            <div class="hazard-item" onclick="toggleHazard(this)" data-category="atemgifte" data-item="ausbreitung">
                <span class="hazard-letter">A</span>
                <span class="hazard-label">Ausbreitung</span>
            </div>
            <div class="hazard-item" onclick="toggleHazard(this)" data-category="atemgifte" data-item="atomare">
                <span class="hazard-letter">A</span>
                <span class="hazard-label">Atomare Gefahr</span>
            </div>
        </div>
    </div>
    
    <!-- CCCC - Chemische Stoffe -->
    <div class="hazard-section">
        <h3 class="hazard-section-title">CCCC - Chemische Stoffe</h3>
        <div class="hazard-grid">
            <div class="hazard-item" onclick="toggleHazard(this)" data-category="chemisch" data-item="chemische">
                <span class="hazard-letter">C</span>
                <span class="hazard-label">Chemische Stoffe</span>
            </div>
            <div class="hazard-item" onclick="toggleHazard(this)" data-category="chemisch" data-item="cbrnex">
                <span class="hazard-letter">C</span>
                <span class="hazard-label">CBRNEx</span>
            </div>
            <div class="hazard-item" onclick="toggleHazard(this)" data-category="chemisch" data-item="container">
                <span class="hazard-letter">C</span>
                <span class="hazard-label">Container</span>
            </div>
            <div class="hazard-item" onclick="toggleHazard(this)" data-category="chemisch" data-item="corrosiv">
                <span class="hazard-letter">C</span>
                <span class="hazard-label">Korrosiv (Ätzend)</span>
            </div>
        </div>
    </div>
    
    <!-- EEEE - Elektrizität/Explosion/Einsturz -->
    <div class="hazard-section">
        <h3 class="hazard-section-title">EEEE - Elektrizität / Explosion / Einsturz</h3>
        <div class="hazard-grid">
            <div class="hazard-item" onclick="toggleHazard(this)" data-category="elektro" data-item="elektrizitat">
                <span class="hazard-letter">E</span>
                <span class="hazard-label">Elektrizität</span>
            </div>
            <div class="hazard-item" onclick="toggleHazard(this)" data-category="elektro" data-item="erkrankung">
                <span class="hazard-letter">E</span>
                <span class="hazard-label">Erkrankung / Seuche</span>
            </div>
            <div class="hazard-item" onclick="toggleHazard(this)" data-category="elektro" data-item="explosion">
                <span class="hazard-letter">E</span>
                <span class="hazard-label">Explosion</span>
            </div>
            <div class="hazard-item" onclick="toggleHazard(this)" data-category="elektro" data-item="einsturz">
                <span class="hazard-letter">E</span>
                <span class="hazard-label">Einsturz</span>
            </div>
        </div>
    </div>
    
    <!-- Additional Hazards -->
    <div class="hazard-section">
        <h3 class="hazard-section-title">Weitere Gefahren</h3>
        <div class="hazard-grid">
            <div class="hazard-item" onclick="toggleHazard(this)" data-category="weitere" data-item="gefaehrliche-tiere">
                <span class="hazard-letter">G</span>
                <span class="hazard-label">Gefährliche Tiere</span>
            </div>
            <div class="hazard-item" onclick="toggleHazard(this)" data-category="weitere" data-item="gewalt">
                <span class="hazard-letter">G</span>
                <span class="hazard-label">Gewalt</span>
            </div>
            <div class="hazard-item" onclick="toggleHazard(this)" data-category="weitere" data-item="wasser">
                <span class="hazard-letter">W</span>
                <span class="hazard-label">Wasser</span>
            </div>
            <div class="hazard-item" onclick="toggleHazard(this)" data-category="weitere" data-item="hitze-kaelte">
                <span class="hazard-letter">H</span>
                <span class="hazard-label">Hitze / Kälte</span>
            </div>
            <div class="hazard-item" onclick="toggleHazard(this)" data-category="weitere" data-item="verkehr">
                <span class="hazard-letter">V</span>
                <span class="hazard-label">Verkehr</span>
            </div>
            <div class="hazard-item" onclick="toggleHazard(this)" data-category="weitere" data-item="umwelt">
                <span class="hazard-letter">U</span>
                <span class="hazard-label">Umwelt</span>
            </div>
            <div class="hazard-item" onclick="toggleHazard(this)" data-category="weitere" data-item="oel">
                <span class="hazard-letter">Ö</span>
                <span class="hazard-label">Öl / Kraftstoffe</span>
            </div>
            <div class="hazard-item" onclick="toggleHazard(this)" data-category="weitere" data-item="strahlung">
                <span class="hazard-letter">R</span>
                <span class="hazard-label">Radioaktive Strahlung</span>
            </div>
        </div>
    </div>
    
    <div class="hazard-summary">
        <h3>Markierte Gefahren</h3>
        <div id="hazardSummary" class="hazard-summary-list">
            <p class="text-secondary">Keine Gefahren markiert</p>
        </div>
    </div>
</div>

<script>
function toggleHazard(element) {
    element.classList.toggle('active');
    updateHazardSummary();
}

function clearHazardMatrix() {
    document.querySelectorAll('.hazard-item.active').forEach(item => {
        item.classList.remove('active');
    });
    updateHazardSummary();
}

function updateHazardSummary() {
    const activeHazards = document.querySelectorAll('.hazard-item.active');
    const summaryDiv = document.getElementById('hazardSummary');
    
    if (activeHazards.length === 0) {
        summaryDiv.innerHTML = '<p class="text-secondary">Keine Gefahren markiert</p>';
        return;
    }
    
    const hazardsByCategory = {};
    activeHazards.forEach(item => {
        const category = item.dataset.category;
        const label = item.querySelector('.hazard-label').textContent;
        
        if (!hazardsByCategory[category]) {
            hazardsByCategory[category] = [];
        }
        hazardsByCategory[category].push(label);
    });
    
    let html = '';
    for (const [category, hazards] of Object.entries(hazardsByCategory)) {
        html += `<div class="hazard-summary-category">
            <strong>${getCategoryName(category)}:</strong>
            <ul>${hazards.map(h => `<li>${h}</li>`).join('')}</ul>
        </div>`;
    }
    
    summaryDiv.innerHTML = html;
}

function getCategoryName(category) {
    const names = {
        'atemgifte': 'AAAA - Atemgifte',
        'chemisch': 'CCCC - Chemische Stoffe',
        'elektro': 'EEEE - Elektrizität/Explosion/Einsturz',
        'weitere': 'Weitere Gefahren'
    };
    return names[category] || category;
}
</script>
