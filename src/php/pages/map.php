<?php
/**
 * Map Page - Embedded OpenStreetMap
 * Uses embedded iframe solution for better compatibility
 */

require_once __DIR__ . '/../auth.php';

Auth::requireOperator();

$user = Auth::getUser();

// Default location (Germany center)
$defaultLat = 50.9787;
$defaultLon = 9.7632;
$defaultZoom = 12;
?>

<div class="page-header">
    <h2>Online Karte</h2>
    <p class="page-subtitle">OpenStreetMap - Interaktive Karte und Routenplanung</p>
</div>

<div class="card">
    <div class="card-content">
        <!-- Map Selector Tabs -->
        <div class="map-tabs" style="margin-bottom: 20px; display: flex; gap: 10px; border-bottom: 2px solid #e0e0e0; padding-bottom: 10px;">
            <button class="map-tab-btn active" onclick="switchMapMode('explore')" id="tab-explore">
                <span class="material-icons">explore</span>
                Karte erkunden
            </button>
            <button class="map-tab-btn" onclick="switchMapMode('route')" id="tab-route">
                <span class="material-icons">directions</span>
                Routenplanung
            </button>
            <button class="map-tab-btn" onclick="switchMapMode('search')" id="tab-search">
                <span class="material-icons">search</span>
                Adresse suchen
            </button>
        </div>

        <!-- Map Mode: Explore -->
        <div id="mode-explore" class="map-mode">
            <div class="alert" style="background: #e3f2fd; border-left: 4px solid #2196f3; padding: 15px; margin-bottom: 20px;">
                <span class="material-icons" style="vertical-align: middle; color: #1976d2;">info</span>
                <span style="color: #1565c0;">
                    <strong>Tipp:</strong> Klicken und ziehen Sie die Karte, um zu navigieren. Verwenden Sie die Maus oder Touch-Gesten zum Zoomen.
                </span>
            </div>
            
            <div class="map-frame-container">
                <iframe id="map-iframe-explore"
                    width="100%"
                    height="600"
                    scrolling="no"
                    marginheight="0"
                    marginwidth="0"
                    src="https://www.openstreetmap.org/export/embed.html?bbox=<?php echo urlencode($defaultLon - 0.1); ?>%2C<?php echo urlencode($defaultLat - 0.05); ?>%2C<?php echo urlencode($defaultLon + 0.1); ?>%2C<?php echo urlencode($defaultLat + 0.05); ?>&amp;layer=mapnik"
                    style="border: 1px solid #ccc; border-radius: 8px;">
                </iframe>
                <br/>
                <small>
                    <a href="https://www.openstreetmap.org/#map=<?php echo urlencode($defaultZoom); ?>/<?php echo urlencode($defaultLat); ?>/<?php echo urlencode($defaultLon); ?>" target="_blank">
                        Größere Karte anzeigen
                    </a>
                </small>
            </div>
        </div>

        <!-- Map Mode: Route Planning -->
        <div id="mode-route" class="map-mode" style="display: none;">
            <div class="alert" style="background: #fff3e0; border-left: 4px solid #ff9800; padding: 15px; margin-bottom: 20px;">
                <span class="material-icons" style="vertical-align: middle; color: #f57c00;">info</span>
                <span style="color: #e65100;">
                    <strong>Hinweis:</strong> Geben Sie Start- und Zieladresse ein, um eine Route zu berechnen.
                </span>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="routeStart">
                    <span class="material-icons" style="vertical-align: middle; color: #4caf50;">place</span>
                    Startpunkt
                </label>
                <input type="text" 
                       id="routeStart" 
                       class="form-input" 
                       placeholder="z.B. Berlin, Alexanderplatz"
                       value="">
            </div>
            
            <div class="form-group">
                <label class="form-label" for="routeEnd">
                    <span class="material-icons" style="vertical-align: middle; color: #f44336;">flag</span>
                    Zielpunkt
                </label>
                <input type="text" 
                       id="routeEnd" 
                       class="form-input" 
                       placeholder="z.B. München, Marienplatz"
                       value="">
            </div>
            
            <div class="form-actions" style="margin-bottom: 20px;">
                <button type="button" class="btn btn-primary" onclick="calculateRoute()">
                    <span class="material-icons">directions</span>
                    Route berechnen
                </button>
                <button type="button" class="btn btn-secondary" onclick="openInGoogleMaps()">
                    <span class="material-icons">open_in_new</span>
                    In Google Maps öffnen
                </button>
            </div>
            
            <div id="routeResult" style="display: none;">
                <div class="map-frame-container">
                    <iframe id="map-iframe-route"
                        width="100%"
                        height="600"
                        scrolling="no"
                        marginheight="0"
                        marginwidth="0"
                        src=""
                        style="border: 1px solid #ccc; border-radius: 8px;">
                    </iframe>
                    <br/>
                    <small id="routeLink"></small>
                </div>
            </div>
        </div>

        <!-- Map Mode: Search -->
        <div id="mode-search" class="map-mode" style="display: none;">
            <div class="alert" style="background: #e8f5e9; border-left: 4px solid #4caf50; padding: 15px; margin-bottom: 20px;">
                <span class="material-icons" style="vertical-align: middle; color: #2e7d32;">info</span>
                <span style="color: #1b5e20;">
                    <strong>Tipp:</strong> Suchen Sie nach Adressen, Orten oder Sehenswürdigkeiten.
                </span>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="searchAddress">
                    <span class="material-icons" style="vertical-align: middle;">search</span>
                    Adresse oder Ort suchen
                </label>
                <input type="text" 
                       id="searchAddress" 
                       class="form-input" 
                       placeholder="z.B. Brandenburger Tor, Berlin"
                       value="">
            </div>
            
            <div class="form-actions" style="margin-bottom: 20px;">
                <button type="button" class="btn btn-primary" onclick="searchAddress()">
                    <span class="material-icons">search</span>
                    Suchen
                </button>
            </div>
            
            <div id="searchResult" style="display: none;">
                <div class="map-frame-container">
                    <iframe id="map-iframe-search"
                        width="100%"
                        height="600"
                        scrolling="no"
                        marginheight="0"
                        marginwidth="0"
                        src=""
                        style="border: 1px solid #ccc; border-radius: 8px;">
                    </iframe>
                    <br/>
                    <small id="searchLink"></small>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.map-tabs {
    display: flex;
    flex-wrap: wrap;
}

.map-tab-btn {
    background: #f5f5f5;
    border: none;
    padding: 12px 20px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    border-radius: 8px 8px 0 0;
    font-size: 14px;
    font-weight: 500;
    color: #666;
    transition: all 0.3s ease;
    font-family: inherit;
}

.map-tab-btn:hover {
    background: #e0e0e0;
    color: #333;
}

.map-tab-btn.active {
    background: #2196f3;
    color: white;
}

.map-tab-btn .material-icons {
    font-size: 20px;
}

.map-frame-container {
    width: 100%;
    position: relative;
}

.map-mode {
    animation: fadeIn 0.3s ease-in;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@media (max-width: 768px) {
    .map-tabs {
        flex-direction: column;
    }
    
    .map-tab-btn {
        border-radius: 8px;
        margin-bottom: 5px;
    }
    
    #map-iframe-explore,
    #map-iframe-route,
    #map-iframe-search {
        height: 400px;
    }
}
</style>

<script>
// Switch between map modes
function switchMapMode(mode) {
    // Hide all modes
    document.querySelectorAll('.map-mode').forEach(el => el.style.display = 'none');
    document.querySelectorAll('.map-tab-btn').forEach(btn => btn.classList.remove('active'));
    
    // Show selected mode
    document.getElementById('mode-' + mode).style.display = 'block';
    document.getElementById('tab-' + mode).classList.add('active');
}

// Calculate and display route
function calculateRoute() {
    const start = document.getElementById('routeStart').value.trim();
    const end = document.getElementById('routeEnd').value.trim();
    
    if (!start || !end) {
        alert('Bitte geben Sie Start- und Zieladresse ein.');
        return;
    }
    
    // Create OpenStreetMap directions URL
    // Format: start and end as search queries
    const osmUrl = `https://www.openstreetmap.org/directions?engine=fossgis_osrm_car&route=${encodeURIComponent(start)}%3B${encodeURIComponent(end)}`;
    
    // Show embedded map with route
    const iframe = document.getElementById('map-iframe-route');
    iframe.src = osmUrl;
    
    // Update link
    document.getElementById('routeLink').innerHTML = `<a href="${osmUrl}" target="_blank">Route in neuem Tab öffnen</a>`;
    
    // Show result
    document.getElementById('routeResult').style.display = 'block';
}

// Open route in Google Maps
function openInGoogleMaps() {
    const start = document.getElementById('routeStart').value.trim();
    const end = document.getElementById('routeEnd').value.trim();
    
    if (!start || !end) {
        alert('Bitte geben Sie Start- und Zieladresse ein.');
        return;
    }
    
    const googleMapsUrl = `https://www.google.com/maps/dir/?api=1&origin=${encodeURIComponent(start)}&destination=${encodeURIComponent(end)}&travelmode=driving`;
    window.open(googleMapsUrl, '_blank');
}

// Search for address
function searchAddress() {
    const address = document.getElementById('searchAddress').value.trim();
    
    if (!address) {
        alert('Bitte geben Sie eine Adresse ein.');
        return;
    }
    
    // Create OpenStreetMap search URL
    const osmSearchUrl = `https://www.openstreetmap.org/search?query=${encodeURIComponent(address)}`;
    
    // Show embedded map
    const iframe = document.getElementById('map-iframe-search');
    iframe.src = osmSearchUrl;
    
    // Update link
    document.getElementById('searchLink').innerHTML = `<a href="${osmSearchUrl}" target="_blank">Suchergebnis in neuem Tab öffnen</a>`;
    
    // Show result
    document.getElementById('searchResult').style.display = 'block';
}

// Handle Enter key in input fields
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('routeStart').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') calculateRoute();
    });
    
    document.getElementById('routeEnd').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') calculateRoute();
    });
    
    document.getElementById('searchAddress').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') searchAddress();
    });
});
</script>
