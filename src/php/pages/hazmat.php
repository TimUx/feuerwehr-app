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
                <svg class="ghs-pictogram" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                    <polygon points="50,5 95,50 50,95 5,50" fill="white" stroke="#C1272D" stroke-width="4"/>
                    <circle cx="50" cy="40" r="8" fill="black"/>
                    <path d="M 35 55 Q 50 50 65 55 Q 50 70 35 55 Z" fill="black"/>
                    <text x="50" y="82" text-anchor="middle" font-size="24" font-weight="bold" fill="black">!</text>
                </svg>
                <h4>GHS01 - Explosiv</h4>
                <p>Explosive Stoffe/Gemische und Erzeugnisse mit Explosivstoff</p>
            </div>
            
            <div class="hazmat-symbol-card">
                <svg class="ghs-pictogram" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                    <polygon points="50,5 95,50 50,95 5,50" fill="white" stroke="#C1272D" stroke-width="4"/>
                    <path d="M 50 25 L 55 35 L 50 40 L 60 50 L 50 60 L 55 70 L 45 75 L 40 65 L 45 60 L 35 50 L 45 40 L 40 30 Z" fill="black"/>
                </svg>
                <h4>GHS02 - Entzündbar</h4>
                <p>Entzündbare Gase, Aerosole, Flüssigkeiten, Feststoffe</p>
            </div>
            
            <div class="hazmat-symbol-card">
                <svg class="ghs-pictogram" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                    <polygon points="50,5 95,50 50,95 5,50" fill="white" stroke="#C1272D" stroke-width="4"/>
                    <circle cx="50" cy="40" r="14" fill="none" stroke="black" stroke-width="3"/>
                    <path d="M 50 55 L 55 65 L 50 70 L 60 80 L 50 90 L 55 100 L 45 105 L 40 95 L 45 90 L 35 80 L 45 70 L 40 60 Z" fill="black" transform="translate(0,-30) scale(0.5) translate(50,90)"/>
                </svg>
                <h4>GHS03 - Oxidierend</h4>
                <p>Oxidierende Gase, Flüssigkeiten, Feststoffe</p>
            </div>
            
            <div class="hazmat-symbol-card">
                <svg class="ghs-pictogram" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                    <polygon points="50,5 95,50 50,95 5,50" fill="white" stroke="#C1272D" stroke-width="4"/>
                    <circle cx="50" cy="50" r="25" fill="none" stroke="black" stroke-width="3"/>
                    <line x1="50" y1="30" x2="50" y2="70" stroke="black" stroke-width="2"/>
                </svg>
                <h4>GHS04 - Gase unter Druck</h4>
                <p>Verdichtete, verflüssigte, tiefgekühlt verflüssigte und gelöste Gase</p>
            </div>
            
            <div class="hazmat-symbol-card">
                <svg class="ghs-pictogram" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                    <polygon points="50,5 95,50 50,95 5,50" fill="white" stroke="#C1272D" stroke-width="4"/>
                    <rect x="25" y="35" width="20" height="35" fill="black" transform="skewX(-10)"/>
                    <path d="M 55 35 L 60 35 L 60 45 L 70 45 L 60 70 L 60 55 L 55 55 Z" fill="black"/>
                    <circle cx="35" cy="55" r="3" fill="white"/>
                    <path d="M 30 75 Q 35 78 40 75" stroke="white" stroke-width="2" fill="none"/>
                </svg>
                <h4>GHS05 - Ätzend</h4>
                <p>Auf Metalle korrosiv wirkende Stoffe, Hautätzend, Schwere Augenschädigung</p>
            </div>
            
            <div class="hazmat-symbol-card">
                <svg class="ghs-pictogram" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                    <polygon points="50,5 95,50 50,95 5,50" fill="white" stroke="#C1272D" stroke-width="4"/>
                    <circle cx="50" cy="30" r="10" fill="black"/>
                    <path d="M 40 45 L 35 50 L 30 65 L 35 70 L 45 68 L 45 78 L 42 85 L 48 88 L 52 88 L 58 85 L 55 78 L 55 68 L 65 70 L 70 65 L 65 50 L 60 45 Z" fill="black"/>
                    <line x1="30" y1="50" x2="70" y2="50" stroke="black" stroke-width="3"/>
                </svg>
                <h4>GHS06 - Giftig</h4>
                <p>Akute Toxizität (oral, dermal, inhalativ) Kategorie 1-3</p>
            </div>
            
            <div class="hazmat-symbol-card">
                <svg class="ghs-pictogram" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                    <polygon points="50,5 95,50 50,95 5,50" fill="white" stroke="#C1272D" stroke-width="4"/>
                    <text x="50" y="65" text-anchor="middle" font-size="48" font-weight="bold" fill="black">!</text>
                </svg>
                <h4>GHS07 - Gesundheitsschädlich</h4>
                <p>Akute Toxizität Kat. 4, Hautreizung, Augenreizung, Sensibilisierung</p>
            </div>
            
            <div class="hazmat-symbol-card">
                <svg class="ghs-pictogram" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                    <polygon points="50,5 95,50 50,95 5,50" fill="white" stroke="#C1272D" stroke-width="4"/>
                    <circle cx="50" cy="35" r="8" fill="black"/>
                    <path d="M 40 50 L 35 55 L 32 65 L 35 70 L 42 68 L 42 75 L 40 80 L 45 82 L 50 82 L 55 82 L 60 80 L 58 75 L 58 68 L 65 70 L 68 65 L 65 55 L 60 50 Z" fill="black"/>
                    <circle cx="38" cy="60" r="4" fill="white"/>
                    <circle cx="62" cy="60" r="4" fill="white"/>
                </svg>
                <h4>GHS08 - Gesundheitsgefahr</h4>
                <p>Sensibilisierung der Atemwege, Keimzellmutagenität, Karzinogenität</p>
            </div>
            
            <div class="hazmat-symbol-card">
                <svg class="ghs-pictogram" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                    <polygon points="50,5 95,50 50,95 5,50" fill="white" stroke="#C1272D" stroke-width="4"/>
                    <path d="M 30 55 Q 35 45 45 48 Q 50 50 50 55 L 50 70 Q 50 75 45 75 Q 40 75 35 70 Z" fill="black"/>
                    <ellipse cx="42" cy="52" rx="3" ry="4" fill="white"/>
                    <path d="M 50 55 Q 55 45 65 48 Q 70 50 70 55 L 70 70 Q 70 75 65 75 Q 60 75 55 70 Z" fill="black"/>
                    <ellipse cx="62" cy="52" rx="3" ry="4" fill="white"/>
                    <line x1="35" y1="70" x2="25" y2="80" stroke="black" stroke-width="2"/>
                    <line x1="70" y1="70" x2="75" y2="80" stroke="black" stroke-width="2"/>
                </svg>
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
                <div class="class-number">1</div>
                <h4>Explosive Stoffe</h4>
                <p>Sprengstoffe, pyrotechnische Gegenstände, Munition</p>
            </div>
            
            <div class="hazmat-class-card class-2">
                <div class="class-number">2</div>
                <h4>Gase</h4>
                <p>2.1: Entzündbar<br>2.2: Nicht entzündbar<br>2.3: Giftig</p>
            </div>
            
            <div class="hazmat-class-card class-3">
                <div class="class-number">3</div>
                <h4>Entzündbare Flüssigkeiten</h4>
                <p>Benzin, Diesel, Alkohole, Lösemittel</p>
            </div>
            
            <div class="hazmat-class-card class-4">
                <div class="class-number">4</div>
                <h4>Entzündbare Feststoffe</h4>
                <p>4.1: Entzündbar<br>4.2: Selbstentzündlich<br>4.3: Mit Wasser reagierend</p>
            </div>
            
            <div class="hazmat-class-card class-5">
                <div class="class-number">5</div>
                <h4>Oxidierende Stoffe</h4>
                <p>5.1: Oxidierende Stoffe<br>5.2: Organische Peroxide</p>
            </div>
            
            <div class="hazmat-class-card class-6">
                <div class="class-number">6</div>
                <h4>Giftige Stoffe</h4>
                <p>6.1: Giftige Stoffe<br>6.2: Ansteckungsgefährliche Stoffe</p>
            </div>
            
            <div class="hazmat-class-card class-7">
                <div class="class-number">7</div>
                <h4>Radioaktive Stoffe</h4>
                <p>Radioaktive Materialien aller Art</p>
            </div>
            
            <div class="hazmat-class-card class-8">
                <div class="class-number">8</div>
                <h4>Ätzende Stoffe</h4>
                <p>Säuren, Laugen, korrosive Stoffe</p>
            </div>
            
            <div class="hazmat-class-card class-9">
                <div class="class-number">9</div>
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
