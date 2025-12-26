# Karten-Migration: Leaflet.js → MapLibre GL JS

## Übersicht

Die Kartenintegration wurde von **Leaflet.js** auf **MapLibre GL JS** migriert, um Kompatibilitätsprobleme zu beheben und eine modernere Kartendarstellung zu ermöglichen.

## Änderungen

### Vorher (Leaflet.js)
- Leaflet 1.9.4
- Leaflet Routing Machine 3.2.12
- DOM-basierte Kartendarstellung
- Separate Routing-Bibliothek erforderlich

### Nachher (MapLibre GL JS)
- MapLibre GL JS 3.6.2
- WebGL-basierte Hardware-Beschleunigung
- Integrierte Vector-Tile Unterstützung
- OSRM API für Routing (direkte API-Nutzung)
- Bessere Performance und flüssigere Animationen

## Vorteile der Migration

### 1. **Bessere Performance**
- Hardware-beschleunigte Rendering durch WebGL
- Flüssigere Zoom- und Pan-Animationen
- Effizientere Speichernutzung

### 2. **Modernere API**
- Aktiv entwickelt und gewartet (Fork von Mapbox GL JS)
- Open-Source und keine API-Keys erforderlich
- Zukunftssichere Technologie

### 3. **Erweiterte Funktionen**
- Vector Tiles Support (für zukünftige Erweiterungen)
- Bessere Touch-Gesten
- 3D-Terrain Support möglich
- Pitch und Rotation

### 4. **Kompatibilität**
- Löst bekannte Ladeproblem mit Leaflet
- Bessere Cross-Browser-Kompatibilität
- Mobile-optimiert

## Funktionen

Alle bisherigen Funktionen bleiben erhalten:

✅ **Karten-Anzeige**
- OpenStreetMap Raster-Tiles
- Navigation Controls (Zoom, Rotation)
- Scale Control (Maßstab)

✅ **Geolocation**
- Automatische Standortermittlung
- Marker für aktuelle Position
- Karte zentriert sich automatisch

✅ **Routing**
- Adresssuche via Nominatim (OSM)
- Routenberechnung via OSRM
- Visuelle Routendarstellung
- Start- und Ziel-Marker (grün/rot)
- Entfernungs- und Zeitberechnung

## Technische Details

### Verwendete APIs

1. **OpenStreetMap Tiles**
   - URL: `https://{a,b,c}.tile.openstreetmap.org/{z}/{x}/{y}.png`
   - Kostenlos, keine API-Keys erforderlich

2. **Nominatim (Geocoding)**
   - URL: `https://nominatim.openstreetmap.org/search`
   - Konvertiert Adressen zu Koordinaten

3. **OSRM (Routing)**
   - URL: `https://router.project-osrm.org/route/v1/driving/`
   - Berechnet optimale Routen

### Code-Struktur

```javascript
// Initialisierung
map = new maplibregl.Map({
    container: 'map',
    style: { /* OSM Raster Tiles */ },
    center: [lon, lat],
    zoom: 12
});

// Marker hinzufügen
const marker = new maplibregl.Marker({color: '#d32f2f'})
    .setLngLat([lon, lat])
    .addTo(map);

// Route zeichnen
map.addLayer({
    id: 'route',
    type: 'line',
    source: 'route',
    paint: {
        'line-color': '#dc2626',
        'line-width': 6
    }
});
```

## Migration für Entwickler

Falls Sie den Code erweitern möchten, beachten Sie:

### Koordinaten-Format
- **Leaflet:** `[lat, lon]` (Breitengrad, Längengrad)
- **MapLibre:** `[lon, lat]` (Längengrad, Breitengrad) ⚠️

### Marker-Erstellung
```javascript
// Leaflet
L.marker([lat, lon]).addTo(map);

// MapLibre
new maplibregl.Marker()
    .setLngLat([lon, lat])
    .addTo(map);
```

### Event-Handling
```javascript
// Leaflet
map.on('click', function(e) {
    console.log(e.latlng);
});

// MapLibre
map.on('click', function(e) {
    console.log(e.lngLat);
});
```

## Testen

Die Karte kann getestet werden unter:
- Menü → **Online Karte**

Funktionen zum Testen:
1. Karte wird geladen ✓
2. Automatische Standortermittlung (optional) ✓
3. Start-Adresse eingeben (z.B. "Berlin, Germany")
4. Ziel-Adresse eingeben (z.B. "Hamburg, Germany")
5. "Route berechnen" klicken
6. Route wird auf der Karte angezeigt ✓
7. Entfernung und Dauer werden angezeigt ✓

## Bekannte Einschränkungen

- Geolocation funktioniert nur über HTTPS oder localhost
- OSRM API hat Rate-Limits (für Produktion evtl. eigenen Server)
- Nominatim hat Rate-Limits (1 Anfrage/Sekunde)

## Support

Bei Problemen:
1. Browser-Konsole auf Fehler prüfen
2. WebGL-Support im Browser prüfen
3. Netzwerk-Tab auf blockierte Anfragen prüfen

## Changelog

**Version 2.0 - Dezember 2025**
- ✨ MapLibre GL JS integriert
- 🗑️ Leaflet.js und Leaflet Routing Machine entfernt
- 🚀 OSRM für Routing implementiert
- 📝 Dokumentation aktualisiert
