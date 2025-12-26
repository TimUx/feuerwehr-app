<?php
/**
 * Map Page - Interactive OpenStreetMap with Leaflet
 * Uses Leaflet.js library to display OSM tiles directly without iframe limitations
 */

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../datastore.php';

Auth::requireOperator();

$user = Auth::getUser();

// Get settings to retrieve address
$settings = DataStore::getSettings();
$address = $settings['address'] ?? '';

// Default location (Germany center)
$defaultLat = 50.9787;
$defaultLon = 9.7632;
$defaultZoom = 7;

// If address is configured, try to geocode it
if (!empty($address)) {
    // Use Nominatim API to geocode the address
    $geocodeUrl = 'https://nominatim.openstreetmap.org/search?format=json&q=' . urlencode($address) . '&limit=1';
    $ch = curl_init($geocodeUrl);
    if ($ch === false) {
        error_log('Failed to initialize cURL for geocoding');
    } else {
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Feuerwehr-App/1.0');
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($response === false) {
            error_log('Geocoding failed: ' . $curlError);
        } else if ($httpCode === 200 && $response) {
            $data = json_decode($response, true);
            if (!empty($data) && isset($data[0]['lat']) && isset($data[0]['lon'])) {
                $defaultLat = floatval($data[0]['lat']);
                $defaultLon = floatval($data[0]['lon']);
                $defaultZoom = 15; // Closer zoom for specific address
            }
        } else {
            error_log('Geocoding returned HTTP ' . $httpCode);
        }
    }
}
?>

<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" 
      integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" 
      crossorigin=""/>

<!-- Leaflet Routing Machine CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css" />

<!-- Leaflet JS - Load with defer to ensure proper order -->
<script defer src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
        crossorigin=""></script>

<!-- Leaflet Routing Machine - Load with defer AFTER Leaflet -->
<script defer src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.js"></script>

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
                    <strong>Tipp:</strong> Ziehen Sie die Karte zum Navigieren. Nutzen Sie Mausrad oder Touch-Gesten zum Zoomen.
                </span>
            </div>
            
            <div id="map-explore" style="height: 600px; border: 1px solid #ccc; border-radius: 8px;"></div>
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
                <button type="button" class="btn btn-secondary" onclick="clearRoute()">
                    <span class="material-icons">clear</span>
                    Route löschen
                </button>
                <button type="button" class="btn btn-secondary" onclick="openInGoogleMaps()">
                    <span class="material-icons">open_in_new</span>
                    In Google Maps öffnen
                </button>
            </div>
            
            <div id="routeInfo" style="display: none; background: #e8f5e9; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #4caf50;">
                <div style="color: #1b5e20;">
                    <strong>Routeninformation:</strong>
                    <div id="routeDistance" style="margin-top: 5px;"></div>
                    <div id="routeDuration" style="margin-top: 5px;"></div>
                </div>
            </div>
            
            <div id="map-route" style="height: 600px; border: 1px solid #ccc; border-radius: 8px;"></div>
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
                <button type="button" class="btn btn-secondary" onclick="clearSearch()">
                    <span class="material-icons">clear</span>
                    Suche löschen
                </button>
            </div>
            
            <div id="searchResults" style="display: none; background: #e3f2fd; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #2196f3;">
                <div style="color: #1565c0;">
                    <strong>Suchergebnis:</strong>
                    <div id="searchResultText" style="margin-top: 5px;"></div>
                </div>
            </div>
            
            <div id="map-search" style="height: 600px; border: 1px solid #ccc; border-radius: 8px;"></div>
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
    
    #map-explore,
    #map-route,
    #map-search {
        height: 400px;
    }
}
</style>

<script>
// Map instances
let mapExplore = null;
let mapRoute = null;
let mapSearch = null;
let routingControl = null;
let searchMarker = null;

// Default center from PHP
const defaultLat = <?php echo $defaultLat; ?>;
const defaultLon = <?php echo $defaultLon; ?>;
const defaultZoom = <?php echo $defaultZoom; ?>;

// Initialize maps when page loads and Leaflet is ready
function initMaps() {
    // Check if Leaflet is loaded
    if (typeof L === 'undefined') {
        console.log('Waiting for Leaflet to load...');
        setTimeout(initMaps, 100);
        return;
    }
    
    // Initialize explore map (default view)
    initExploreMap();
}

// Start initialization when DOM is ready
document.addEventListener('DOMContentLoaded', initMaps);

// Initialize explore map
function initExploreMap() {
    if (mapExplore) {
        // Map already initialized, just invalidate size
        mapExplore.invalidateSize();
        return;
    }
    
    // Check if Leaflet is loaded
    if (typeof L === 'undefined') {
        console.error('Leaflet not loaded yet');
        return;
    }
    
    const mapContainer = document.getElementById('map-explore');
    if (!mapContainer) {
        console.error('Map container not found');
        return;
    }
    
    try {
        mapExplore = L.map('map-explore').setView([defaultLat, defaultLon], defaultZoom);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 19
        }).addTo(mapExplore);
        
        // Add marker if address is configured
        <?php if ($defaultZoom > 10): ?>
        L.marker([defaultLat, defaultLon]).addTo(mapExplore)
            .bindPopup(<?php echo json_encode($address, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>)
            .openPopup();
        <?php endif; ?>
        
        console.log('Explore map initialized successfully');
    } catch (error) {
        console.error('Error initializing explore map:', error);
    }
}

// Initialize route map
function initRouteMap() {
    if (mapRoute) {
        // Map already initialized, just invalidate size
        mapRoute.invalidateSize();
        return;
    }
    
    // Check if Leaflet is loaded
    if (typeof L === 'undefined') {
        console.error('Leaflet not loaded yet');
        setTimeout(initRouteMap, 100);
        return;
    }
    
    try {
        mapRoute = L.map('map-route').setView([defaultLat, defaultLon], defaultZoom);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 19
        }).addTo(mapRoute);
        
        console.log('Route map initialized successfully');
    } catch (error) {
        console.error('Error initializing route map:', error);
    }
}

// Initialize search map
function initSearchMap() {
    if (mapSearch) {
        // Map already initialized, just invalidate size
        mapSearch.invalidateSize();
        return;
    }
    
    // Check if Leaflet is loaded
    if (typeof L === 'undefined') {
        console.error('Leaflet not loaded yet');
        setTimeout(initSearchMap, 100);
        return;
    }
    
    try {
        mapSearch = L.map('map-search').setView([defaultLat, defaultLon], defaultZoom);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 19
        }).addTo(mapSearch);
        
        console.log('Search map initialized successfully');
    } catch (error) {
        console.error('Error initializing search map:', error);
    }
}

// Switch between map modes
function switchMapMode(mode) {
    // Hide all modes
    document.querySelectorAll('.map-mode').forEach(el => el.style.display = 'none');
    document.querySelectorAll('.map-tab-btn').forEach(btn => btn.classList.remove('active'));
    
    // Show selected mode
    document.getElementById('mode-' + mode).style.display = 'block';
    document.getElementById('tab-' + mode).classList.add('active');
    
    // Initialize map if needed
    if (mode === 'route' && !mapRoute) {
        setTimeout(initRouteMap, 100);
    } else if (mode === 'search' && !mapSearch) {
        setTimeout(initSearchMap, 100);
    }
    
    // Invalidate size to fix display issues
    setTimeout(() => {
        if (mode === 'explore' && mapExplore) mapExplore.invalidateSize();
        if (mode === 'route' && mapRoute) mapRoute.invalidateSize();
        if (mode === 'search' && mapSearch) mapSearch.invalidateSize();
    }, 200);
}

// Calculate and display route
async function calculateRoute() {
    const start = document.getElementById('routeStart').value.trim();
    const end = document.getElementById('routeEnd').value.trim();
    
    if (!start || !end) {
        alert('Bitte geben Sie Start- und Zieladresse ein.');
        return;
    }
    
    // Check if Leaflet Routing Machine is loaded
    if (typeof L === 'undefined' || typeof L.Routing === 'undefined') {
        alert('Routing-Bibliothek lädt noch. Bitte versuchen Sie es in einem Moment erneut.');
        console.error('Leaflet or Leaflet Routing Machine not loaded yet');
        return;
    }
    
    try {
        // Show loading indicator
        document.getElementById('routeInfo').style.display = 'none';
        
        // Geocode start address
        const startResponse = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(start)}&limit=1`, {
            headers: { 'User-Agent': 'Feuerwehr-App/1.0' }
        });
        const startData = await startResponse.json();
        
        if (!startData || startData.length === 0) {
            throw new Error('Startadresse konnte nicht gefunden werden.');
        }
        
        // Geocode end address
        const endResponse = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(end)}&limit=1`, {
            headers: { 'User-Agent': 'Feuerwehr-App/1.0' }
        });
        const endData = await endResponse.json();
        
        if (!endData || endData.length === 0) {
            throw new Error('Zieladresse konnte nicht gefunden werden.');
        }
        
        const startLat = parseFloat(startData[0].lat);
        const startLon = parseFloat(startData[0].lon);
        const endLat = parseFloat(endData[0].lat);
        const endLon = parseFloat(endData[0].lon);
        
        // Remove existing routing control if any
        if (routingControl) {
            mapRoute.removeControl(routingControl);
        }
        
        // Create routing control
        routingControl = L.Routing.control({
            waypoints: [
                L.latLng(startLat, startLon),
                L.latLng(endLat, endLon)
            ],
            router: L.Routing.osrmv1({
                serviceUrl: 'https://router.project-osrm.org/route/v1'
            }),
            routeWhileDragging: false,
            addWaypoints: false,
            lineOptions: {
                styles: [{color: '#d32f2f', opacity: 0.8, weight: 6}]
            },
            createMarker: function(i, waypoint, n) {
                const marker = L.marker(waypoint.latLng, {
                    icon: L.icon({
                        iconUrl: `https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-${i === 0 ? 'green' : 'red'}.png`,
                        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
                        iconSize: [25, 41],
                        iconAnchor: [12, 41],
                        popupAnchor: [1, -34],
                        shadowSize: [41, 41]
                    })
                });
                marker.bindPopup(i === 0 ? start : end);
                return marker;
            }
        }).addTo(mapRoute);
        
        // Listen for route found event
        routingControl.on('routesfound', function(e) {
            const routes = e.routes;
            const summary = routes[0].summary;
            
            // Display route information
            const distance = (summary.totalDistance / 1000).toFixed(2);
            const duration = Math.round(summary.totalTime / 60);
            
            document.getElementById('routeDistance').textContent = `Entfernung: ${distance} km`;
            document.getElementById('routeDuration').textContent = `Fahrzeit: ${duration} Minuten`;
            document.getElementById('routeInfo').style.display = 'block';
        });
        
        routingControl.on('routingerror', function(e) {
            alert('Fehler bei der Routenberechnung: ' + e.error.message);
        });
        
    } catch (error) {
        alert('Fehler bei der Routenberechnung: ' + error.message);
        console.error('Route calculation error:', error);
    }
}

// Clear route
function clearRoute() {
    if (routingControl) {
        mapRoute.removeControl(routingControl);
        routingControl = null;
    }
    document.getElementById('routeInfo').style.display = 'none';
    document.getElementById('routeStart').value = '';
    document.getElementById('routeEnd').value = '';
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
async function searchAddress() {
    const address = document.getElementById('searchAddress').value.trim();
    
    if (!address) {
        alert('Bitte geben Sie eine Adresse ein.');
        return;
    }
    
    try {
        // Geocode address
        const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(address)}&limit=1`, {
            headers: { 'User-Agent': 'Feuerwehr-App/1.0' }
        });
        const data = await response.json();
        
        if (!data || data.length === 0) {
            throw new Error('Adresse konnte nicht gefunden werden.');
        }
        
        const lat = parseFloat(data[0].lat);
        const lon = parseFloat(data[0].lon);
        const displayName = data[0].display_name;
        
        // Remove existing marker
        if (searchMarker) {
            mapSearch.removeLayer(searchMarker);
        }
        
        // Add marker
        searchMarker = L.marker([lat, lon]).addTo(mapSearch)
            .bindPopup(displayName)
            .openPopup();
        
        // Center map on location
        mapSearch.setView([lat, lon], 16);
        
        // Display result info
        document.getElementById('searchResultText').textContent = displayName;
        document.getElementById('searchResults').style.display = 'block';
        
    } catch (error) {
        alert('Fehler bei der Adresssuche: ' + error.message);
    }
}

// Clear search
function clearSearch() {
    if (searchMarker) {
        mapSearch.removeLayer(searchMarker);
        searchMarker = null;
    }
    document.getElementById('searchResults').style.display = 'none';
    document.getElementById('searchAddress').value = '';
    mapSearch.setView([defaultLat, defaultLon], defaultZoom);
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
