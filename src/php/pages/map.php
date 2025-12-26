<?php
/**
 * Map Page - OpenStreetMap with Routing
 */

require_once __DIR__ . '/../auth.php';

Auth::requireOperator();

$user = Auth::getUser();
?>

<div class="page-header">
    <h2>Online Karte</h2>
</div>

<div class="map-container">
    <div id="map" class="map-view"></div>
    
    <div class="map-controls">
        <div class="form-group">
            <label for="routeStart">Start</label>
            <input type="text" id="routeStart" class="form-control" placeholder="Startadresse eingeben...">
        </div>
        
        <div class="form-group">
            <label for="routeEnd">Ziel</label>
            <input type="text" id="routeEnd" class="form-control" placeholder="Zieladresse eingeben...">
        </div>
        
        <button onclick="calculateRoute()" class="btn btn-primary">
            <span class="material-icons">directions</span>
            Route berechnen
        </button>
        
        <button onclick="clearRoute()" class="btn btn-secondary">
            <span class="material-icons">clear</span>
            Route löschen
        </button>
    </div>
    
    <div id="routeInfo" class="route-info" style="display: none;">
        <h3>Routeninformationen</h3>
        <div id="routeDetails"></div>
    </div>
</div>

<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" 
      integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" 
      crossorigin=""/>

<!-- Leaflet Routing Machine CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css" />

<!-- Leaflet JavaScript -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
        crossorigin=""></script>

<!-- Leaflet Routing Machine JavaScript -->
<script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.js"></script>

<style>
/* Ensure Leaflet CSS overrides are applied */
#map {
    z-index: 1;
}
</style>

<script>
let map;
let routingControl;

// Constants
const MAP_SIZE_INVALIDATION_DELAY = 100; // milliseconds
const LEAFLET_CHECK_INTERVAL = 100; // milliseconds

// Wait for Leaflet to be available before initializing
function waitForLeaflet(callback) {
    if (typeof L !== 'undefined') {
        callback();
    } else {
        setTimeout(() => waitForLeaflet(callback), LEAFLET_CHECK_INTERVAL);
    }
}

// Initialize map when the page is fully loaded
document.addEventListener('DOMContentLoaded', function() {
    waitForLeaflet(initMap);
});

// Initialize map
function initMap() {
    try {
        if (map) {
            map.remove(); // Clean up existing map
        }
        
        // Default center: Germany (can be customized)
        map = L.map('map').setView([50.9787, 9.7632], 13);
        
        // Add OpenStreetMap tiles
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 19
        }).addTo(map);
        
        // Mark map as loaded
        window.mapLoaded = true;
        
        // Force map to invalidate size after initialization
        setTimeout(function() {
            if (map) {
                map.invalidateSize();
            }
        }, MAP_SIZE_INVALIDATION_DELAY);
        
        // Try to get user's location
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
                const lat = position.coords.latitude;
                const lon = position.coords.longitude;
                map.setView([lat, lon], 13);
                
                // Add marker for current position
                L.marker([lat, lon]).addTo(map)
                    .bindPopup('Ihr aktueller Standort')
                    .openPopup();
            }, function(error) {
                console.log('Geolocation error:', error);
            });
        }
    } catch (error) {
        console.error('Map initialization error:', error);
        alert('Fehler beim Laden der Karte: ' + error.message);
    }
}

function calculateRoute() {
    // Check if map is loaded
    if (!window.mapLoaded || !map) {
        alert('Die Karte wird noch geladen. Bitte warten Sie einen Moment und versuchen Sie es erneut.');
        return;
    }
    
    if (typeof L === 'undefined' || typeof L.Routing === 'undefined') {
        alert('Die Routing-Bibliothek wird noch geladen. Bitte warten Sie einen Moment und versuchen Sie es erneut.');
        return;
    }
    
    const start = document.getElementById('routeStart').value;
    const end = document.getElementById('routeEnd').value;
    
    if (!start || !end) {
        alert('Bitte geben Sie Start- und Zieladresse ein.');
        return;
    }
    
    // Geocode addresses
    Promise.all([
        geocodeAddress(start),
        geocodeAddress(end)
    ]).then(([startCoords, endCoords]) => {
        if (!startCoords || !endCoords) {
            alert('Eine oder beide Adressen konnten nicht gefunden werden.');
            return;
        }
        
        // Remove existing route
        if (routingControl) {
            map.removeControl(routingControl);
        }
        
        // Create new route
        routingControl = L.Routing.control({
            waypoints: [
                L.latLng(startCoords[0], startCoords[1]),
                L.latLng(endCoords[0], endCoords[1])
            ],
            routeWhileDragging: true,
            language: 'de',
            lineOptions: {
                styles: [{color: '#dc2626', opacity: 0.8, weight: 6}]
            }
        }).addTo(map);
        
        // Show route info
        routingControl.on('routesfound', function(e) {
            const routes = e.routes;
            const summary = routes[0].summary;
            
            document.getElementById('routeInfo').style.display = 'block';
            document.getElementById('routeDetails').innerHTML = `
                <p><strong>Entfernung:</strong> ${(summary.totalDistance / 1000).toFixed(2)} km</p>
                <p><strong>Dauer:</strong> ${Math.round(summary.totalTime / 60)} Minuten</p>
            `;
        });
    }).catch(error => {
        console.error('Routing error:', error);
        alert('Fehler bei der Routenberechnung: ' + error.message);
    });
}

function clearRoute() {
    if (routingControl) {
        map.removeControl(routingControl);
        routingControl = null;
    }
    document.getElementById('routeStart').value = '';
    document.getElementById('routeEnd').value = '';
    document.getElementById('routeInfo').style.display = 'none';
}

async function geocodeAddress(address) {
    try {
        const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(address)}`);
        const data = await response.json();
        
        if (data && data.length > 0) {
            return [parseFloat(data[0].lat), parseFloat(data[0].lon)];
        }
        return null;
    } catch (error) {
        console.error('Geocoding error:', error);
        return null;
    }
}
</script>
