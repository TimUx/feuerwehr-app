<?php
/**
 * Hazardous Materials Page - Gefahrstoffkennzeichen
 */

require_once __DIR__ . '/../auth.php';

Auth::requireOperator();

$user = Auth::getUser();
?>

<div class="page-header">
    <h2>Gefahrstoffkennzeichen</h2>
    <p class="page-description">UN-Nummern und Gefahrgutkennzeichnung</p>
</div>

<div class="hazmat-container">
    <!-- Search Section -->
    <div class="hazmat-search-section">
        <h3>UN-Nummer suchen</h3>
        <div class="search-form">
            <div class="form-group">
                <input type="text" id="unNumberSearch" class="form-control" 
                       placeholder="UN-Nummer eingeben (z.B. 1203)" 
                       onkeypress="if(event.key==='Enter') searchHazmat()">
            </div>
            <button onclick="searchHazmat()" class="btn btn-primary">
                <span class="material-icons">search</span>
                Suchen
            </button>
        </div>
        
        <div id="hazmatDetails" class="hazmat-details" style="display: none;">
            <!-- Details will be inserted here -->
        </div>
    </div>
    
    <!-- GHS Pictograms -->
    <div class="hazmat-symbols-section">
        <h3>GHS-Gefahrenpiktogramme (Global harmonisiertes System)</h3>
        <p class="ghs-info">Standardisierte EU-Gefahrenpiktogramme nach CLP-Verordnung (EG) Nr. 1272/2008</p>
        <div class="hazmat-symbols-grid">
            <div class="hazmat-symbol-card">
                <img src="/public/images/hazmat/GHS01.svg" alt="GHS01" class="ghs-pictogram">
                <h4>GHS01 - Explosiv</h4>
                <p>Explosive Stoffe/Gemische und Erzeugnisse mit Explosivstoff</p>
            </div>
            
            <div class="hazmat-symbol-card">
                <img src="/public/images/hazmat/GHS02.svg" alt="GHS02" class="ghs-pictogram">
                <h4>GHS02 - Entzündbar</h4>
                <p>Entzündbare Gase, Aerosole, Flüssigkeiten, Feststoffe</p>
            </div>
            
            <div class="hazmat-symbol-card">
                <img src="/public/images/hazmat/GHS03.svg" alt="GHS03" class="ghs-pictogram">
                <h4>GHS03 - Oxidierend</h4>
                <p>Oxidierende Gase, Flüssigkeiten, Feststoffe</p>
            </div>
            
            <div class="hazmat-symbol-card">
                <img src="/public/images/hazmat/GHS04.svg" alt="GHS04" class="ghs-pictogram">
                <h4>GHS04 - Gase unter Druck</h4>
                <p>Verdichtete, verflüssigte, tiefgekühlt verflüssigte und gelöste Gase</p>
            </div>
            
            <div class="hazmat-symbol-card">
                <img src="/public/images/hazmat/GHS05.svg" alt="GHS05" class="ghs-pictogram">
                <h4>GHS05 - Ätzend</h4>
                <p>Auf Metalle korrosiv wirkende Stoffe, Hautätzend, Schwere Augenschädigung</p>
            </div>
            
            <div class="hazmat-symbol-card">
                <img src="/public/images/hazmat/GHS06.svg" alt="GHS06" class="ghs-pictogram">
                <h4>GHS06 - Giftig</h4>
                <p>Akute Toxizität (oral, dermal, inhalativ) Kategorie 1-3</p>
            </div>
            
            <div class="hazmat-symbol-card">
                <img src="/public/images/hazmat/GHS07.svg" alt="GHS07" class="ghs-pictogram">
                <h4>GHS07 - Gesundheitsschädlich</h4>
                <p>Akute Toxizität Kat. 4, Hautreizung, Augenreizung, Sensibilisierung</p>
            </div>
            
            <div class="hazmat-symbol-card">
                <img src="/public/images/hazmat/GHS08.svg" alt="GHS08" class="ghs-pictogram">
                <h4>GHS08 - Gesundheitsgefahr</h4>
                <p>Sensibilisierung der Atemwege, Keimzellmutagenität, Karzinogenität</p>
            </div>
            
            <div class="hazmat-symbol-card">
                <img src="/public/images/hazmat/GHS09.svg" alt="GHS09" class="ghs-pictogram">
                <h4>GHS09 - Umweltgefährlich</h4>
                <p>Gewässergefährdend, Umweltgefährlich</p>
            </div>
        </div>
    </div>
    
    <!-- ADR Classes -->
    <div class="hazmat-classes-section">
        <h3>ADR-Gefahrgutklassen</h3>
        <div class="hazmat-classes-grid">
            <div class="hazmat-class-card class-1">
                <img src="/public/images/hazmat/class-1.svg" alt="Klasse 1" class="class-symbol">
                <h4>Explosive Stoffe</h4>
                <p>Sprengstoffe, pyrotechnische Gegenstände, Munition</p>
            </div>
            
            <div class="hazmat-class-card class-2">
                <img src="/public/images/hazmat/class-2.svg" alt="Klasse 2" class="class-symbol">
                <h4>Gase</h4>
                <p>2.1: Entzündbar<br>2.2: Nicht entzündbar<br>2.3: Giftig</p>
            </div>
            
            <div class="hazmat-class-card class-3">
                <img src="/public/images/hazmat/class-3.svg" alt="Klasse 3" class="class-symbol">
                <h4>Entzündbare Flüssigkeiten</h4>
                <p>Benzin, Diesel, Alkohole, Lösemittel</p>
            </div>
            
            <div class="hazmat-class-card class-4">
                <img src="/public/images/hazmat/class-4.svg" alt="Klasse 4" class="class-symbol">
                <h4>Entzündbare Feststoffe</h4>
                <p>4.1: Entzündbar<br>4.2: Selbstentzündlich<br>4.3: Mit Wasser reagierend</p>
            </div>
            
            <div class="hazmat-class-card class-5">
                <img src="/public/images/hazmat/class-5.svg" alt="Klasse 5" class="class-symbol">
                <h4>Oxidierende Stoffe</h4>
                <p>5.1: Oxidierende Stoffe<br>5.2: Organische Peroxide</p>
            </div>
            
            <div class="hazmat-class-card class-6">
                <img src="/public/images/hazmat/class-6.svg" alt="Klasse 6" class="class-symbol">
                <h4>Giftige Stoffe</h4>
                <p>6.1: Giftige Stoffe<br>6.2: Ansteckungsgefährliche Stoffe</p>
            </div>
            
            <div class="hazmat-class-card class-7">
                <img src="/public/images/hazmat/class-7.svg" alt="Klasse 7" class="class-symbol">
                <h4>Radioaktive Stoffe</h4>
                <p>Radioaktive Materialien aller Art</p>
            </div>
            
            <div class="hazmat-class-card class-8">
                <img src="/public/images/hazmat/class-8.svg" alt="Klasse 8" class="class-symbol">
                <h4>Ätzende Stoffe</h4>
                <p>Säuren, Laugen, korrosive Stoffe</p>
            </div>
            
            <div class="hazmat-class-card class-9">
                <img src="/public/images/hazmat/class-9.svg" alt="Klasse 9" class="class-symbol">
                <h4>Verschiedene gefährliche Stoffe</h4>
                <p>Lithiumbatterien, Asbest, umweltgefährdende Stoffe</p>
            </div>
        </div>
    </div>
    
    <!-- Common UN Numbers Quick Reference -->
    <div class="hazmat-quick-ref">
        <h3>Häufige UN-Nummern (Schnellreferenz)</h3>
        <div class="quick-ref-grid">
            <button class="quick-ref-btn" onclick="quickSearchHazmat('1011')">UN 1011 - Butan</button>
            <button class="quick-ref-btn" onclick="quickSearchHazmat('1202')">UN 1202 - Diesel</button>
            <button class="quick-ref-btn" onclick="quickSearchHazmat('1203')">UN 1203 - Benzin</button>
            <button class="quick-ref-btn" onclick="quickSearchHazmat('1005')">UN 1005 - Ammoniak</button>
            <button class="quick-ref-btn" onclick="quickSearchHazmat('1965')">UN 1965 - Propan-Butan</button>
            <button class="quick-ref-btn" onclick="quickSearchHazmat('1230')">UN 1230 - Methanol</button>
            <button class="quick-ref-btn" onclick="quickSearchHazmat('1824')">UN 1824 - Natronlauge</button>
            <button class="quick-ref-btn" onclick="quickSearchHazmat('1789')">UN 1789 - Salzsäure</button>
            <button class="quick-ref-btn" onclick="quickSearchHazmat('1170')">UN 1170 - Ethanol</button>
            <button class="quick-ref-btn" onclick="quickSearchHazmat('1428')">UN 1428 - Natrium</button>
            <button class="quick-ref-btn" onclick="quickSearchHazmat('1950')">UN 1950 - Aerosole</button>
            <button class="quick-ref-btn" onclick="quickSearchHazmat('2031')">UN 2031 - Salpetersäure</button>
        </div>
    </div>
</div>

<script>
async function searchHazmat() {
    const unNumber = document.getElementById('unNumberSearch').value.trim();
    
    if (!unNumber) {
        alert('Bitte geben Sie eine UN-Nummer ein.');
        return;
    }
    
    try {
        const response = await fetch(`/src/php/api/hazmat.php?un=${encodeURIComponent(unNumber)}`);
        const result = await response.json();
        
        if (result.success) {
            displayHazmatDetails(result.data);
        } else {
            document.getElementById('hazmatDetails').innerHTML = `
                <div class="alert alert-warning">
                    <span class="material-icons">warning</span>
                    <p>${result.message}</p>
                    <p>Versuchen Sie es mit einer anderen UN-Nummer oder verwenden Sie die Schnellreferenz unten.</p>
                </div>
            `;
            document.getElementById('hazmatDetails').style.display = 'block';
        }
    } catch (error) {
        console.error('Search error:', error);
        alert('Fehler bei der Suche: ' + error.message);
    }
}

function quickSearchHazmat(unNumber) {
    document.getElementById('unNumberSearch').value = unNumber;
    searchHazmat();
    // Scroll to details
    document.getElementById('hazmatDetails').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function displayHazmatDetails(material) {
    const detailsDiv = document.getElementById('hazmatDetails');
    
    const html = `
        <div class="hazmat-detail-card">
            <div class="hazmat-detail-header">
                <h3>UN ${material.un} - ${material.name}</h3>
                <div class="hazmat-detail-badges">
                    <span class="badge badge-class">Klasse ${material.class}</span>
                    ${material.packingGroup !== '-' ? `<span class="badge badge-pg">VG ${material.packingGroup}</span>` : ''}
                </div>
            </div>
            
            <div class="hazmat-detail-body">
                <div class="hazmat-detail-section">
                    <h4><span class="material-icons">info</span> Beschreibung</h4>
                    <p>${material.description}</p>
                </div>
                
                <div class="hazmat-detail-section">
                    <h4><span class="material-icons">warning</span> Gefahren</h4>
                    <ul>
                        ${material.dangers.map(d => `<li>${d}</li>`).join('')}
                    </ul>
                </div>
                
                <div class="hazmat-detail-section">
                    <h4><span class="material-icons">label</span> Gefahrzettel</h4>
                    <div class="hazmat-labels">
                        ${material.hazardLabels.map(label => `<span class="hazmat-label">${label}</span>`).join('')}
                    </div>
                </div>
                
                <div class="hazmat-detail-section">
                    <h4><span class="material-icons">medical_services</span> Erste Hilfe</h4>
                    <p>${material.firstAid}</p>
                </div>
                
                <div class="hazmat-detail-section">
                    <h4><span class="material-icons">local_fire_department</span> Brandbekämpfung</h4>
                    <p>${material.firefighting}</p>
                </div>
                
                <div class="hazmat-detail-section">
                    <h4><span class="material-icons">cleaning_services</span> Freisetzung</h4>
                    <p>${material.spillage}</p>
                </div>
            </div>
        </div>
    `;
    
    detailsDiv.innerHTML = html;
    detailsDiv.style.display = 'block';
}
</script>
