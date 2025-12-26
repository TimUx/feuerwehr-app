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

<!-- MapLibre GL JS CSS -->
<link rel="stylesheet" href="https://unpkg.com/maplibre-gl@3.6.2/dist/maplibre-gl.css" crossorigin="anonymous">

<!-- MapLibre GL JS JavaScript -->
<script src="https://unpkg.com/maplibre-gl@3.6.2/dist/maplibre-gl.js" crossorigin="anonymous"></script>

<style>
#map {
    z-index: 1;
}
.maplibregl-ctrl-logo {
    display: none !important;
}
</style>

<script>
let map;
let routeLayer;
let markers = [];

// Constants
const MAP_INIT_DELAY = 100; // milliseconds
const MAPLIBRE_CHECK_INTERVAL = 100; // milliseconds

// Wait for MapLibre GL to be available before initializing
function waitForMapLibre(callback) {
    if (typeof maplibregl !== 'undefined') {
        callback();
    } else {
        setTimeout(() => waitForMapLibre(callback), MAPLIBRE_CHECK_INTERVAL);
    }
}

// Initialize map when the page is fully loaded
document.addEventListener('DOMContentLoaded', function() {
    waitForMapLibre(initMap);
});

// Initialize map
function initMap() {
    try {
        if (map) {
            map.remove(); // Clean up existing map
        }
        
        // Default center: Germany (can be customized)
        map = new maplibregl.Map({
            container: 'map',
            style: {
                version: 8,
                sources: {
                    'osm-tiles': {
                        type: 'raster',
                        tiles: [
                            'https://a.tile.openstreetmap.org/{z}/{x}/{y}.png',
                            'https://b.tile.openstreetmap.org/{z}/{x}/{y}.png',
                            'https://c.tile.openstreetmap.org/{z}/{x}/{y}.png'
                        ],
                        tileSize: 256,
                        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                    }
                },
                layers: [
                    {
                        id: 'osm-tiles',
                        type: 'raster',
                        source: 'osm-tiles',
                        minzoom: 0,
                        maxzoom: 19
                    }
                ]
            },
            center: [9.7632, 50.9787], // [longitude, latitude] - Germany
            zoom: 12
        });
        
        // Add navigation controls
        map.addControl(new maplibregl.NavigationControl(), 'top-right');
        
        // Add scale control
        map.addControl(new maplibregl.ScaleControl(), 'bottom-left');
        
        // Mark map as loaded
        window.mapLoaded = true;
        
        // Try to get user's location
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
                const lat = position.coords.latitude;
                const lon = position.coords.longitude;
                
                // Center map on user location
                map.flyTo({
                    center: [lon, lat],
                    zoom: 14
                });
                
                // Add marker for current position
                const marker = new maplibregl.Marker({color: '#d32f2f'})
                    .setLngLat([lon, lat])
                    .setPopup(new maplibregl.Popup().setHTML('<strong>Ihr aktueller Standort</strong>'))
                    .addTo(map);
                
                marker.togglePopup();
                markers.push(marker);
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
        
        // Clear existing route
        clearRouteLayer();
        
        // Add markers for start and end
        const startMarker = new maplibregl.Marker({color: '#4caf50'})
            .setLngLat([startCoords[1], startCoords[0]])
            .setPopup(new maplibregl.Popup().setHTML('<strong>Start:</strong> ' + start))
            .addTo(map);
        
        const endMarker = new maplibregl.Marker({color: '#f44336'})
            .setLngLat([endCoords[1], endCoords[0]])
            .setPopup(new maplibregl.Popup().setHTML('<strong>Ziel:</strong> ' + end))
            .addTo(map);
        
        markers.push(startMarker, endMarker);
        
        // Get route from OSRM
        getRoute(startCoords, endCoords).then(route => {
            if (route) {
                displayRoute(route);
                
                // Fit map to route bounds
                const bounds = new maplibregl.LngLatBounds();
                route.geometry.coordinates.forEach(coord => {
                    bounds.extend(coord);
                });
                map.fitBounds(bounds, { padding: 50 });
                
                // Show route info
                const distanceKm = (route.distance / 1000).toFixed(2);
                const durationMin = Math.round(route.duration / 60);
                
                document.getElementById('routeInfo').style.display = 'block';
                document.getElementById('routeDetails').innerHTML = `
                    <p><strong>Entfernung:</strong> ${distanceKm} km</p>
                    <p><strong>Dauer:</strong> ${durationMin} Minuten</p>
                `;
            }
        }).catch(error => {
            console.error('Routing error:', error);
            alert('Fehler bei der Routenberechnung. Bitte versuchen Sie es erneut.');
        });
    }).catch(error => {
        console.error('Geocoding error:', error);
        alert('Fehler bei der Adresssuche: ' + error.message);
    });
}

function clearRoute() {
    // Clear route layer
    clearRouteLayer();
    
    // Clear markers
    markers.forEach(marker => marker.remove());
    markers = [];
    
    // Clear input fields
    document.getElementById('routeStart').value = '';
    document.getElementById('routeEnd').value = '';
    document.getElementById('routeInfo').style.display = 'none';
}

function clearRouteLayer() {
    if (map.getLayer('route')) {
        map.removeLayer('route');
    }
    if (map.getSource('route')) {
        map.removeSource('route');
    }
}

function displayRoute(route) {
    // Remove existing route if any
    clearRouteLayer();
    
    // Add route to map
    map.addSource('route', {
        type: 'geojson',
        data: {
            type: 'Feature',
            properties: {},
            geometry: route.geometry
        }
    });
    
    map.addLayer({
        id: 'route',
        type: 'line',
        source: 'route',
        layout: {
            'line-join': 'round',
            'line-cap': 'round'
        },
        paint: {
            'line-color': '#dc2626',
            'line-width': 6,
            'line-opacity': 0.8
        }
    });
}

async function getRoute(start, end) {
    try {
        // Use OSRM public API for routing
        const url = `https://router.project-osrm.org/route/v1/driving/${start[1]},${start[0]};${end[1]},${end[0]}?overview=full&geometries=geojson`;
        const response = await fetch(url);
        const data = await response.json();
        
        if (data.code === 'Ok' && data.routes && data.routes.length > 0) {
            return data.routes[0];
        }
        return null;
    } catch (error) {
        console.error('Routing API error:', error);
        throw error;
    }
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
