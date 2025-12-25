# Änderungen und Verbesserungen

## Übersicht der implementierten Features

### 🔧 Kritische Fehlerbehebungen

#### 1. Hostname mit Bindestrich funktioniert jetzt
- **Problem**: Hostnamen mit Bindestrich (z.B. `timo-msi:8080`) wurden bei Weiterleitungen falsch behandelt
- **Lösung**: Regex-Pattern in `index.php` korrigiert, um Bindestriche zu erlauben
- **Dateien**: `index.php` (Zeile 23)

#### 2. "Hinzufügen"-Buttons funktionieren jetzt
- **Problem**: Alle Modal-Dialoge (Benutzer hinzufügen, Fahrzeug hinzufügen, etc.) öffneten sich nicht
- **Ursache**: JavaScript in dynamisch geladenen Seiten wurde nicht ausgeführt
- **Lösung**: `loadPage()` Funktion in `app.js` überarbeitet, um Skripte korrekt auszuführen
- **Dateien**: `public/js/app.js` (Zeilen 144-193)

#### 3. PWA-Installationsbutton im Header
- **Neu**: Button zum Installieren der App auf dem Home-Screen
- **Position**: Header neben Theme-Toggle und Logout
- **Funktionalität**: Wird nur angezeigt, wenn Browser PWA-Installation unterstützt
- **Dateien**: `index.php`, `public/js/app.js`

### 🧪 Gefahrstoffe (Hazmat)

#### GHS-Piktogramme
- **Alt**: Emoji-basierte Symbole (💣, 🔥, etc.)
- **Neu**: Standardisierte EU-konforme SVG-Piktogramme nach CLP-Verordnung
- **Features**:
  - Rote Raute mit schwarzen Symbolen auf weißem Grund
  - Alle 9 GHS-Piktogramme (GHS01-GHS09)
  - Hover-Effekt für bessere UX
- **Dateien**: `src/php/pages/hazmat.php`, `public/css/style.css`

#### UN-Nummern-Suche
- **Status**: Funktioniert jetzt korrekt
- **Datenbank**: 15+ häufige Gefahrstoffe offline verfügbar
- **Beispiele**: 
  - UN 1203 - Benzin
  - UN 1202 - Diesel
  - UN 1005 - Ammoniak
  - UN 1789 - Salzsäure
- **Features**:
  - Detaillierte Informationen (Klasse, Gefahren, Erste Hilfe, Brandbekämpfung)
  - Schnellreferenz-Buttons für häufige Stoffe
  - Enter-Taste zum Suchen
- **Dateien**: `src/php/pages/hazmat.php`, `src/php/api/hazmat.php`

### ⚠️ Gefahrenmatrix

- **Funktion**: Klickbare Gefahrenfelder funktionieren jetzt
- **Features**:
  - AAAA (Atemgifte) - 4 Optionen
  - CCCC (Chemische Stoffe) - 4 Optionen
  - EEEE (Elektrizität/Explosion/Einsturz) - 4 Optionen
  - Weitere Gefahren - 8 Optionen
- **Zusammenfassung**: Markierte Gefahren werden gruppiert nach Kategorie angezeigt
- **Dateien**: `src/php/pages/hazard-matrix.php` (Skript-Ausführung jetzt aktiv)

### 🗺️ Online Karte

- **Problem behoben**: Karte lädt jetzt korrekt
- **Ursache**: `DOMContentLoaded` Event feuerte nicht bei dynamischem Laden
- **Lösung**: Sofortige Initialisierung mit `setTimeout()`
- **Features**:
  - OpenStreetMap Integration
  - Geolokalisierung (aktueller Standort)
  - Routenberechnung zwischen zwei Adressen
  - Entfernung und Dauer werden angezeigt
- **Dateien**: `src/php/pages/map.php`

### 🏠 Hauptmenü

#### Rote Buttons für Hauptfunktionen
- **Neue Farbe**: Feuerrot (primärfarbe) mit weißer Schrift
- **Buttons**:
  - Anwesenheitsliste
  - Einsatzbericht
  - Fahrzeuge
  - Telefonnummern
  - Online Karte
  - Gefahrenmatrix
  - Gefahrstoffe
  - Statistiken

#### Blaue Buttons für Administration
- **Neue Farbe**: Blau mit weißer Schrift
- **Buttons**:
  - Einsatzkräfte
  - Email Settings
  - Benutzer
  - (Formulardaten - deaktiviert)

**Dateien**: `public/css/style.css` (Zeilen 651-711)

### 🚗 Fahrzeuge

#### Neue Features:
1. **Sortierung nach Funkrufname (Standard)**
   - Klickbare Spaltenüberschriften
   - Sortierung nach Typ, Ort oder Funkrufname
   - Aufsteigend/Absteigend

2. **Filter-Optionen**
   - Nach Ort filtern
   - Nach Typ filtern
   - Beide Filter kombinierbar

3. **Suchfeld**
   - Suche nach Typ oder Funkrufname
   - Echtzeit-Filterung
   - Kombination mit Filtern möglich

**Dateien**: `src/php/pages/vehicles.php`

### 📞 Telefonnummern

#### Suchfunktion
- **Suchfelder**: Name, Organisation, Funktion
- **Echtzeit-Filterung**: Ergebnisse werden sofort angezeigt
- **Beispiel-Keywords**:
  - "Wasserbehörde"
  - "Bauhof"
  - "Leitstelle"
  - "Bürgermeister"

**Dateien**: `src/php/pages/phone-numbers.php`

## 🧪 Testen der Änderungen

### Voraussetzung: Installation
Die App muss zunächst installiert werden:
1. Öffnen Sie `http://localhost:8080` (oder `http://timo-msi:8080`)
2. Falls die App noch nicht installiert ist, wird automatisch zu `install.php` weitergeleitet
3. Folgen Sie dem Installationsassistenten

### Test-Checkliste

#### ✅ Login & Navigation
- [ ] Login funktioniert mit localhost und Hostname mit Bindestrich
- [ ] Alle Menü-Buttons sind rot (Hauptfunktionen) oder blau (Admin)
- [ ] PWA-Install-Button erscheint im Header (wenn unterstützt)

#### ✅ Gefahrstoffe
- [ ] GHS-Piktogramme werden als rote Rauten mit schwarzen Symbolen angezeigt
- [ ] UN-Nummern-Suche funktioniert (z.B. "1203" eingeben und Enter drücken)
- [ ] Schnellreferenz-Buttons funktionieren

#### ✅ Gefahrenmatrix
- [ ] Klick auf Gefahrenfeld markiert es (rot)
- [ ] Zusammenfassung zeigt markierte Gefahren
- [ ] "Alle zurücksetzen" funktioniert

#### ✅ Online Karte
- [ ] Karte lädt und zeigt Standard-Position
- [ ] Geolokalisierung funktioniert (nach Berechtigung)
- [ ] Route zwischen zwei Adressen kann berechnet werden

#### ✅ Fahrzeuge
- [ ] Sortierung durch Klick auf Spaltenüberschriften
- [ ] Filter nach Ort funktioniert
- [ ] Filter nach Typ funktioniert
- [ ] Suchfeld filtert Ergebnisse
- [ ] "Hinzufügen"-Button öffnet Modal

#### ✅ Telefonnummern
- [ ] Suchfeld filtert Telefonnummern
- [ ] "Hinzufügen"-Button öffnet Modal (nur als Admin)

#### ✅ Administration (nur als Admin)
- [ ] "Benutzer hinzufügen" öffnet Modal
- [ ] "Einsatzkräfte hinzufügen" öffnet Modal

## 📝 Technische Details

### Geänderte Dateien
1. `index.php` - Hostname-Regex, PWA-Button
2. `public/js/app.js` - Skript-Ausführung, PWA-Installation
3. `public/css/style.css` - Button-Farben, Filter-Styles
4. `src/php/pages/hazmat.php` - GHS-SVGs, API-Pfad
5. `src/php/pages/hazard-matrix.php` - (Skripte werden jetzt ausgeführt)
6. `src/php/pages/map.php` - Initialisierung
7. `src/php/pages/vehicles.php` - Filter, Sortierung, Suche
8. `src/php/pages/phone-numbers.php` - Suche, API-Pfade

### Keine Breaking Changes
- Alle bestehenden Funktionen bleiben erhalten
- Datenbank-Struktur unverändert
- API-Kompatibilität gewährleistet

## 🚀 Deployment

Alle Änderungen sind rückwärtskompatibel. Nach dem Pull:

```bash
cd /path/to/feuerwehr-app
git pull origin main
```

Kein Cache-Clear oder Neustart erforderlich (außer Service Worker für PWA-Updates).

## 📞 Support

Bei Problemen bitte Issue auf GitHub erstellen oder mich direkt kontaktieren.
