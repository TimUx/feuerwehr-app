<?php
/**
 * Map Page - Interactive OpenStreetMap with Leaflet
 * Uses Leaflet.js library to display OSM tiles directly without iframe limitations
 * Focuses on full-width map with sidebar controls
 */

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../datastore.php';

Auth::requireOperator();

$user = Auth::getUser();

// Get settings to retrieve address for fallback
$settings = DataStore::getSettings();
$address = $settings['address'] ?? '';

// Default location (Germany center) - used as last resort fallback
$defaultLat = 50.9787;
$defaultLon = 9.7632;
$defaultZoom = 7;

// If address is configured, try to geocode it for fallback
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

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
        crossorigin=""></script>

<!-- Leaflet Routing Machine -->
<script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.js"></script>

<!-- Map Container with Sidebar Layout -->
<div class="map-page-container">
    <!-- Sidebar for Controls -->
    <div class="map-sidebar">
        <div class="map-sidebar-header">
            <h3 style="margin: 0; font-size: 1.1rem; display: flex; align-items: center; gap: 0.5rem;">
                <span class="material-icons">map</span>
                Karte
            </h3>
        </div>

        <!-- Function Selector -->
        <div class="map-control-section">
            <h4 class="map-control-title">
                <span class="material-icons">tune</span>
                Funktionen
            </h4>
            <div class="function-tabs">
                <button class="function-tab-btn active" onclick="switchFunction('explore')" id="tab-explore">
                    <span class="material-icons">explore</span>
                    Erkunden
                </button>
                <button class="function-tab-btn" onclick="switchFunction('route')" id="tab-route">
                    <span class="material-icons">directions</span>
                    Route
                </button>
                <button class="function-tab-btn" onclick="switchFunction('search')" id="tab-search">
                    <span class="material-icons">search</span>
                    Suchen
                </button>
            </div>
        </div>

        <!-- Function: Explore (Default view) -->
        <div id="function-explore" class="map-function">
        </div>

        <!-- Function: Route Planning -->
        <div id="function-route" class="map-function" style="display: none;">
            <div class="form-group">
                <label class="form-label" for="routeStart">
                    <span class="material-icons" style="vertical-align: middle; color: #4caf50; font-size: 18px;">place</span>
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
                    <span class="material-icons" style="vertical-align: middle; color: #f44336; font-size: 18px;">flag</span>
                    Zielpunkt
                </label>
                <input type="text" 
                       id="routeEnd" 
                       class="form-input" 
                       placeholder="z.B. München, Marienplatz"
                       value="">
            </div>
            
            <button type="button" class="btn btn-primary" style="width: 100%; margin-bottom: 10px;" onclick="calculateRoute()">
                <span class="material-icons">directions</span>
                Route berechnen
            </button>
            <button type="button" class="btn btn-secondary" style="width: 100%; margin-bottom: 10px;" onclick="clearRoute()">
                <span class="material-icons">clear</span>
                Route löschen
            </button>
            <button type="button" class="btn btn-secondary" style="width: 100%;" onclick="openInGoogleMaps()">
                <span class="material-icons">open_in_new</span>
                In Google Maps öffnen
            </button>
            
            <div id="routeInfo" style="display: none; background: #e8f5e9; padding: 12px; border-radius: 8px; margin-top: 15px; border-left: 4px solid #4caf50;">
                <div style="color: #1b5e20; font-size: 0.9rem;">
                    <strong>Routeninformation:</strong>
                    <div id="routeDistance" style="margin-top: 5px;"></div>
                    <div id="routeDuration" style="margin-top: 5px;"></div>
                </div>
            </div>
        </div>

        <!-- Function: Search -->
        <div id="function-search" class="map-function" style="display: none;">
            <div class="form-group">
                <label class="form-label" for="searchAddress">
                    <span class="material-icons" style="vertical-align: middle; font-size: 18px;">search</span>
                    Adresse oder Ort suchen
                </label>
                <input type="text" 
                       id="searchAddress" 
                       class="form-input" 
                       placeholder="z.B. Brandenburger Tor, Berlin"
                       value="">
            </div>
            
            <button type="button" class="btn btn-primary" style="width: 100%; margin-bottom: 10px;" onclick="searchAddress()">
                <span class="material-icons">search</span>
                Suchen
            </button>
            <button type="button" class="btn btn-secondary" style="width: 100%;" onclick="clearSearch()">
                <span class="material-icons">clear</span>
                Suche löschen
            </button>
            
            <div id="searchResults" style="display: none; background: #e3f2fd; padding: 12px; border-radius: 8px; margin-top: 15px; border-left: 4px solid #2196f3;">
                <div style="color: #1565c0; font-size: 0.9rem;">
                    <strong>Suchergebnis:</strong>
                    <div id="searchResultText" style="margin-top: 5px;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Map Area -->
    <div class="map-main">
        <div id="map" style="height: 100%; width: 100%;"></div>
    </div>
</div>

<style>
/* Map Page Container - Full Width Layout */
.map-page-container {
    display: flex;
    height: calc(100vh - 64px); /* Full height minus header */
    width: 100%;
    margin: 0;
    position: relative;
}

/* Map Sidebar */
.map-sidebar {
    width: 320px;
    background: var(--bg-card);
    border-left: 1px solid var(--border-color);
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    z-index: 100;
    order: 2; /* Position sidebar after map */
}

.map-sidebar-header {
    padding: 20px;
    border-bottom: 2px solid var(--border-color);
    background: var(--bg-secondary);
}

.map-control-section {
    padding: 20px;
    border-bottom: 1px solid var(--border-color);
}

.map-control-title {
    font-size: 0.95rem;
    font-weight: 500;
    color: var(--text-secondary);
    margin: 0 0 12px 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.map-control-title .material-icons {
    font-size: 18px;
}

/* Function Tabs */
.function-tabs {
    display: flex;
    gap: 8px;
}

.function-tab-btn {
    background: var(--bg-secondary);
    border: 2px solid var(--border-color);
    padding: 10px;
    cursor: pointer;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 500;
    color: var(--text-primary);
    transition: all 0.2s ease;
    font-family: inherit;
    flex: 1;
}

.function-tab-btn:hover {
    background: var(--bg-primary);
    border-color: var(--primary-color);
}

.function-tab-btn.active {
    background: var(--primary-color);
    color: white;
    border-color: var(--primary-color);
}

.function-tab-btn .material-icons {
    font-size: 24px;
}

/* Function Content */
.map-function {
    padding: 20px;
    animation: fadeIn 0.2s ease-in;
}

/* Main Map Area */
.map-main {
    flex: 1;
    position: relative;
    overflow: hidden;
    order: 1; /* Position map before sidebar */
}

/* Responsive Design */
@media (max-width: 768px) {
    .map-page-container {
        flex-direction: column;
        height: auto;
    }
    
    .map-sidebar {
        width: 100%;
        max-height: 50vh;
        border-left: none;
        border-top: 1px solid var(--border-color);
        order: 2; /* Sidebar below map on mobile */
    }
    
    .map-main {
        height: 50vh;
        min-height: 400px;
        order: 1; /* Map above sidebar on mobile */
    }
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

/* Override Leaflet controls positioning for better visibility */
.leaflet-top.leaflet-right {
    top: 10px;
    right: 10px;
}

.leaflet-control-zoom {
    border: 2px solid rgba(0,0,0,0.2);
    border-radius: 8px;
}
</style>

<script>
(function() {
// Wrap in IIFE to avoid variable conflicts during SPA navigation
// Single map instance
let map = null;
let routingControl = null;
let searchMarker = null;
let locationMarker = null;

// Layer definitions
const layers = {
    osm: {
        url: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 19
    },
    topo: {
        url: 'https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png',
        attribution: 'Map data: &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors, SRTM | Map style: &copy; <a href="https://opentopomap.org">OpenTopoMap</a>',
        maxZoom: 17
    },
    satellite: {
        url: 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
        attribution: 'Tiles &copy; Esri &mdash; Source: Esri, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP, and the GIS User Community',
        maxZoom: 19
    }
};

// Default center from PHP (fallback)
const defaultLat = <?php echo $defaultLat; ?>;
const defaultLon = <?php echo $defaultLon; ?>;
const defaultZoom = <?php echo $defaultZoom; ?>;
const configuredAddress = <?php echo json_encode($address, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;

// State for location
let userLocation = null;
let locationDenied = false;

// Initialize map when page loads and Leaflet is ready
function initMap(retryCount = 0) {
    // Check if Leaflet is loaded
    if (typeof L === 'undefined') {
        if (retryCount < 50) { // Max 5 seconds (50 * 100ms)
            console.log('Waiting for Leaflet to load...');
            setTimeout(() => initMap(retryCount + 1), 100);
        } else {
            console.error('Leaflet failed to load after 5 seconds');
        }
        return;
    }
    
    if (map) {
        // Map already initialized, just invalidate size
        map.invalidateSize();
        return;
    }
    
    const mapContainer = document.getElementById('map');
    if (!mapContainer) {
        console.error('Map container not found');
        return;
    }
    
    try {
        // Create map with default view (will be updated by geolocation)
        map = L.map('map').setView([defaultLat, defaultLon], defaultZoom);
        
        // Create layer objects for Leaflet layer control
        const osmLayer = L.tileLayer(layers.osm.url, {
            attribution: layers.osm.attribution,
            maxZoom: layers.osm.maxZoom
        });
        
        const topoLayer = L.tileLayer(layers.topo.url, {
            attribution: layers.topo.attribution,
            maxZoom: layers.topo.maxZoom
        });
        
        const satelliteLayer = L.tileLayer(layers.satellite.url, {
            attribution: layers.satellite.attribution,
            maxZoom: layers.satellite.maxZoom
        });
        
        // Add default layer (OSM)
        osmLayer.addTo(map);
        
        // Add Leaflet's built-in layer control
        const baseMaps = {
            "Straßenkarte": osmLayer,
            "Gelände": topoLayer,
            "Satellit": satelliteLayer
        };
        
        L.control.layers(baseMaps).addTo(map);
        
        console.log('Map initialized successfully');
        
        // Try to get user location
        getUserLocation();
        
    } catch (error) {
        console.error('Error initializing map:', error);
    }
}

// Get user's current location
function getUserLocation() {
    if (!navigator.geolocation) {
        console.log('Geolocation not supported, using fallback location');
        useFallbackLocation();
        return;
    }
    
    console.log('Requesting user location...');
    
    navigator.geolocation.getCurrentPosition(
        // Success callback
        function(position) {
            userLocation = {
                lat: position.coords.latitude,
                lon: position.coords.longitude
            };
            
            console.log('User location obtained:', userLocation);
            
            // Center map on user location
            map.setView([userLocation.lat, userLocation.lon], 15);
            
            // Add marker for user location
            if (locationMarker) {
                map.removeLayer(locationMarker);
            }
            
            locationMarker = L.marker([userLocation.lat, userLocation.lon], {
                icon: L.icon({
                    iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png',
                    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
                    iconSize: [25, 41],
                    iconAnchor: [12, 41],
                    popupAnchor: [1, -34],
                    shadowSize: [41, 41]
                })
            }).addTo(map)
                .bindPopup('Ihr Standort')
                .openPopup();
        },
        // Error callback
        function(error) {
            console.log('Geolocation error:', error.message);
            locationDenied = true;
            useFallbackLocation();
        },
        // Options
        {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 0
        }
    );
}

// Use fallback location from settings
function useFallbackLocation() {
    console.log('Using fallback location from settings');
    
    // If we have a configured address with coordinates, use it
    if (defaultZoom > 10) {
        map.setView([defaultLat, defaultLon], defaultZoom);
        
        // Add marker if address is configured
        if (configuredAddress) {
            if (locationMarker) {
                map.removeLayer(locationMarker);
            }
            
            locationMarker = L.marker([defaultLat, defaultLon]).addTo(map)
                .bindPopup(configuredAddress)
                .openPopup();
        }
    } else {
        // Use default Germany center
        map.setView([defaultLat, defaultLon], defaultZoom);
    }
}

// Switch function (explore, route, search)
window.switchFunction = function(functionName) {
    // Hide all functions
    document.querySelectorAll('.map-function').forEach(el => el.style.display = 'none');
    document.querySelectorAll('.function-tab-btn').forEach(btn => btn.classList.remove('active'));
    
    // Show selected function
    document.getElementById('function-' + functionName).style.display = 'block';
    document.getElementById('tab-' + functionName).classList.add('active');
    
    // Clear previous state when switching
    if (functionName === 'explore') {
        // Clear route and search
        clearRoute();
        clearSearch();
    } else if (functionName === 'route') {
        // Clear search
        clearSearch();
    } else if (functionName === 'search') {
        // Clear route
        clearRoute();
    }
};

// Calculate and display route
window.calculateRoute = async function() {
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
            map.removeControl(routingControl);
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
        }).addTo(map);
        
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
};

// Clear route
window.clearRoute = function() {
    if (routingControl) {
        map.removeControl(routingControl);
        routingControl = null;
    }
    document.getElementById('routeInfo').style.display = 'none';
    document.getElementById('routeStart').value = '';
    document.getElementById('routeEnd').value = '';
};

// Open route in Google Maps
window.openInGoogleMaps = function() {
    const start = document.getElementById('routeStart').value.trim();
    const end = document.getElementById('routeEnd').value.trim();
    
    if (!start || !end) {
        alert('Bitte geben Sie Start- und Zieladresse ein.');
        return;
    }
    
    const googleMapsUrl = `https://www.google.com/maps/dir/?api=1&origin=${encodeURIComponent(start)}&destination=${encodeURIComponent(end)}&travelmode=driving`;
    window.open(googleMapsUrl, '_blank');
};

// Search for address
window.searchAddress = async function() {
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
            map.removeLayer(searchMarker);
        }
        
        // Add marker
        searchMarker = L.marker([lat, lon]).addTo(map)
            .bindPopup(displayName)
            .openPopup();
        
        // Center map on location
        map.setView([lat, lon], 16);
        
        // Display result info
        document.getElementById('searchResultText').textContent = displayName;
        document.getElementById('searchResults').style.display = 'block';
        
    } catch (error) {
        alert('Fehler bei der Adresssuche: ' + error.message);
    }
};

// Clear search
window.clearSearch = function() {
    if (searchMarker) {
        map.removeLayer(searchMarker);
        searchMarker = null;
    }
    document.getElementById('searchResults').style.display = 'none';
    document.getElementById('searchAddress').value = '';
    
    // Return to user location or fallback
    if (userLocation) {
        map.setView([userLocation.lat, userLocation.lon], 15);
    } else {
        map.setView([defaultLat, defaultLon], defaultZoom);
    }
};

// Handle Enter key in input fields
document.addEventListener('DOMContentLoaded', function() {
    const routeStartInput = document.getElementById('routeStart');
    const routeEndInput = document.getElementById('routeEnd');
    const searchAddressInput = document.getElementById('searchAddress');
    
    if (routeStartInput) {
        routeStartInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') calculateRoute();
        });
    }
    
    if (routeEndInput) {
        routeEndInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') calculateRoute();
        });
    }
    
    if (searchAddressInput) {
        searchAddressInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') searchAddress();
        });
    }
});

// Start initialization
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initMap);
} else {
    initMap();
}

})(); // End IIFE
</script>
