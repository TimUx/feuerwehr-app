# 🚒 Feuerwehr Management App

Progressive Web App (PWA) für das interne Koordinationsmanagement von Feuerwehren. Keine Datenbank erforderlich - läuft mit Apache + PHP und verschlüsselten JSON-Dateien.

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-blue)](https://php.net)
[![PWA](https://img.shields.io/badge/PWA-enabled-green)](https://web.dev/progressive-web-apps/)

---

## 📑 Inhaltsverzeichnis

- [Features](#-features)
- [Installation](#-installation)
  - [Voraussetzungen](#voraussetzungen)
  - [Installation mit Web-Installer](#installation-mit-web-installer-empfohlen)
  - [Manuelle Installation](#manuelle-installation-alternativ)
  - [PWA-Installation](#pwa-installation-mobile)
- [Update / Upgrade (Datenübernahme)](#-update--upgrade-datenübernahme)
- [Offline-Funktionalität](#-offline-funktionalität)
- [Erste Schritte](#-erste-schritte)
  - [Login](#login)
  - [Hauptmenü](#hauptmenü)
- [Administration](#-administration)
  - [Benutzerverwaltung](#benutzerverwaltung)
  - [Standorte-Verwaltung](#standorte-verwaltung)
  - [Einsatzkräfte-Verwaltung](#einsatzkräfte-verwaltung)
  - [Fahrzeug-Verwaltung](#fahrzeug-verwaltung)
  - [Telefonnummern-Verwaltung](#telefonnummern-verwaltung)
  - [Backup & Export](#backup--export)
  - [Audit-Log](#audit-log)
  - [Allgemeine Einstellungen](#allgemeine-einstellungen)
  - [E-Mail-Einstellungen](#e-mail-einstellungen)
- [Operator-Bereich](#-operator-bereich)
  - [Formulare](#formulare)
  - [Globale Suche](#globale-suche)
  - [Kalender](#kalender)
  - [Sitzungen](#sitzungen)
  - [Nachricht senden](#nachricht-senden-ntfy)
  - [Einsatztools](#einsatztools)
  - [Statistiken](#statistiken)
  - [Formulardaten](#formulardaten)
- [Push-Benachrichtigungen (ntfy)](#-push-benachrichtigungen-ntfy)
- [Konfiguration](#️-konfiguration)
- [Sicherheit](#-sicherheit)
- [Health-Check & Tests](#-health-check--tests)
- [Technologie-Stack](#-technologie-stack)
- [Support](#-support)
- [Lizenz](#-lizenz)

---

## ✨ Features

### 🔐 Authentifizierung & Sicherheit
- **Drei Benutzerrollen**: 
  - **Global-Admin**: Vollzugriff auf alle Standorte und Systemeinstellungen
  - **Standort-Admin**: Verwaltung eines spezifischen Standorts
  - **Operator**: Formulare & Ansichten (keine Verwaltung)
- **Multi-Standort-Unterstützung**: Mehrere Einsatzabteilungen/Standorte verwalten
- **Verschlüsselte Datenspeicherung**: Alle Daten AES-256-CBC verschlüsselt
- **Sichere Passwörter**: bcrypt-Hashing; neue Passwörter min. **10** Zeichen (bestehende kürzere bleiben gültig)
- **CSRF-Schutz**: Token-Validierung für state-changing Requests
- **Rate-Limiting**: Login und Passwort-Reset (5 Fehlversuche / 15 Min.)
- **Remember-Me / Sitzungsübersicht**: Geräte widerrufen („Angemeldet bleiben“)
- **Audit-Log**: Nachvollziehbare Admin-/Sicherheitsaktionen
- **Session-Management**: Automatischer Timeout
- **XSS & Command Injection Schutz**: Output-Escaping und Whitelisting

### 👥 Personal-Management
- **Einsatzkräfte-Verwaltung**: Zentrale Datenbank aller Mitglieder
- **Qualifikationen**: AGT, Maschinist, Sanitäter
- **Führungsrollen**: Truppführer, Gruppenführer, Zugführer, Verbandsführer
- **Ausbilder-Kennzeichnung**: Separate Markierung für Übungsleiter

### 🚒 Fahrzeug-Management
- **Zentrale Fahrzeugverwaltung**: Ort, Typ, Funkrufname
- **Multi-Select Integration**: Automatische Verfügbarkeit in allen Formularen

### 📋 Dynamische Formulare
- **Anwesenheitsliste** (Übungsdienste):
  - Übungsleiter-Auswahl (nur Ausbilder)
  - Teilnehmer (Multi-Select)
  - Automatische Zeitberechnung
  - Teilnehmerzählung
  - Datei-Upload mit E-Mail-Anhang
  
- **Einsatzbericht**:
  - Vollständige Einsatzdaten (Grund, Ort, Leiter, Lage, Tätigkeiten)
  - Dynamische Fahrzeugbesatzung (1-20 Einsatzkräfte)
  - Beteiligte Personen (dynamisch 0-10)
  - Verdienstausfall-Tracking
  - Kostenpflichtigkeit
  - **Entwurf speichern**: Autosave in localStorage (Wiederaufnahme vor Absenden)

- **E-Mail & PDF**: Automatischer Versand als HTML-E-Mail mit PDF-Anhang

### 🛠️ Einsatz-Tools
- **Online Karte**: OpenStreetMap mit Leaflet + leaflet-routing-machine
- **Gefahrenmatrix**: AAAA-CCCC-EEEE Einsatzstellen-Gefahren
- **Gefahrstoffkennzeichen**: UN-Nummern Datenbank mit GHS/ADR-Klassen
- **Wichtige Telefonnummern**: Notfallkontakte mit Direktwahl (tel:-Links)

### 🔍 Organisation & Kommunikation
- **Globale Suche**: Personal, Fahrzeuge, Übungen und Einsätze
- **Kalender**: Monatsübersicht für Übungen und Einsätze
- **ntfy Nachrichten**: Push-Hinweise an standortbezogene ntfy-Kanäle senden
- **Backup & Export**: Vollbackups (Rotation, letzte 15) sowie JSON/CSV-Export
- **Health-Check**: `/health.php` für Runtime-Status ohne Login

### 📊 Statistiken
- **Jahres-Übersicht**: Abteilungsweit
- **Personen-Statistiken**: Einzelauswertung je Einsatzkraft
- **Auswertungen**: Übungsstunden, Einsatzstunden, Anzahl Dienste

### 🎨 Design & UX
- **Progressive Web App**: Installierbar auf mobilen Geräten
- **Responsive Design**: Optimiert für Mobile (iPhone 13 Pro) und Desktop
- **Light/Dark Mode**: Manuell umschaltbar; Default aus `prefers-color-scheme`
- **Haptik & Toasts**: Vibration/Feedback und Toasts statt `alert`/`confirm`
- **Deep-Links / Browser-History**: Navigation mit `?page=` und Zurück-Button
- **Touch-optimiert**: Große Buttons für mobile Bedienung
- **Material Design Icons**: Moderne, intuitive Benutzeroberfläche
- **Offline-Funktionalität**: Service Worker + IndexedDB für Offline-Formulare
- **Push-Benachrichtigungen (ntfy)**: Standortbezogene Nachrichten an Mobilgeräte versenden

---

## 🚀 Installation

### Voraussetzungen

- **PHP 7.4+** mit Extensions: `openssl`, `mbstring`, `json`
- **Apache** oder anderer PHP-kompatibler Webserver
- **Git** (für Installation via Repository)

**Hinweis:** Alle PHP-Abhängigkeiten (mPDF, PHPMailer) sind bereits im Repository enthalten - Composer ist nicht erforderlich!

### Installation mit Web-Installer (Empfohlen)

Der Web-Installer ist die einfachste Methode und erfordert **keinen Zugriff auf die Kommandozeile**.

#### 1. Repository klonen oder hochladen
```bash
git clone https://github.com/TimUx/feuerwehr-app.git
cd feuerwehr-app
```

Alternativ: Laden Sie die Dateien per FTP auf Ihren Webserver hoch.

#### 2. Installations-Wizard öffnen
Navigieren Sie zu Ihrer Domain im Browser:
```
http://ihre-domain.de/install.php
```

#### 3. Installations-Schritte durchlaufen
Der Wizard führt Sie durch folgende Schritte:

##### Schritt 1: System-Voraussetzungen prüfen
<img src="screenshots/20-install-prerequisites.png" width="600" alt="System-Voraussetzungen">

Der Installer prüft automatisch:
- ✅ **PHP Version** (7.4.0 oder höher erforderlich)
- ✅ **PHP Extensions**: 
  - Erforderlich: `openssl`, `mbstring`, `json`, `session`
  - Empfohlen: `curl`, `gd`, `zip`
- ✅ **Verzeichnis-Berechtigungen** (`config/`, `data/`)
- ✅ **PHP-Konfiguration** (`upload_max_filesize`, `post_max_size`, `memory_limit`)

Sie können erst fortfahren, wenn alle **erforderlichen** Voraussetzungen erfüllt sind. Warnungen bei empfohlenen Features erlauben das Fortfahren.

##### Schritt 2: Willkommen
<img src="screenshots/21-install-welcome.png" width="600" alt="Willkommen">

Übersicht über die Einrichtung und was konfiguriert wird.

##### Schritt 3: Administrator-Benutzer erstellen
<img src="screenshots/22-install-admin.png" width="600" alt="Admin-Benutzer">

Erstellen Sie den ersten Admin-Benutzer:
- **Benutzername** (min. 3 Zeichen)
- **Passwort** (min. **10** Zeichen, mit Bestätigung)

Das Passwort wird automatisch mit bcrypt gehashed und verschlüsselt gespeichert.

##### Schritt 4: E-Mail-Einstellungen
<img src="screenshots/23-install-email.png" width="600" alt="E-Mail-Einstellungen">

Konfigurieren Sie E-Mail-Einstellungen für Formular-Übermittlungen:
- **Absender E-Mail-Adresse und Name**
- **Standard-Empfänger** (optional)
- **SMTP Server-Einstellungen**:
  - Host, Port, Verschlüsselung (TLS/SSL)
  - Optional: SMTP-Authentifizierung mit Benutzername/Passwort

##### Schritt 5: Installation abgeschlossen
<img src="screenshots/24-install-complete.png" width="600" alt="Installation abgeschlossen">

✅ Verschlüsselungsschlüssel automatisch generiert (64 Zeichen, AES-256-CBC)  
✅ Administrator-Benutzer erstellt  
✅ E-Mail-Einstellungen konfiguriert  
✅ Datenverzeichnis erstellt mit sicheren Berechtigungen

**Wichtig:** Der Verschlüsselungsschlüssel wird automatisch generiert - keine Kommandozeile erforderlich!

#### 4. Logo hochladen (optional)
Platzieren Sie Ihr Feuerwehr-Logo als `public/assets/logo.png`. Dieses wird in E-Mails und PDFs verwendet.

#### 5. Anmeldung
Nach erfolgreicher Installation können Sie sich mit Ihrem erstellten Administrator-Benutzer anmelden und die App nutzen.

**Hinweis:** Sobald `config/config.php` existiert, ist der Installations-Wizard (`install.php`) gesperrt (HTTP 403). Für Updates siehe [Update / Upgrade](#-update--upgrade-datenübernahme) – `install.php` nicht erneut ausführen.

---

### Manuelle Installation (Alternativ)

Wenn Sie Zugriff auf die Kommandozeile haben, können Sie die App auch manuell einrichten:

#### 1. Repository klonen
```bash
git clone https://github.com/TimUx/feuerwehr-app.git
cd feuerwehr-app
```

#### 2. Konfigurationsdatei erstellen
```bash
cp config/config.example.php config/config.php
```

#### 3. Verschlüsselungsschlüssel generieren
```bash
php -r "echo bin2hex(random_bytes(32));"
```
Kopieren Sie den generierten Schlüssel und fügen Sie ihn in `config/config.php` als `encryption_key` ein (64 Hex-Zeichen für AES-256).

#### 4. E-Mail-Konfiguration anpassen
Öffnen Sie `config/config.php` und passen Sie die E-Mail-Einstellungen an:
```php
'email' => [
    'from_address' => 'noreply@ihre-feuerwehr.de',
    'from_name' => 'Feuerwehr Willingshausen',
    'smtp_host' => 'localhost',
    'smtp_port' => 25,
],
'app_base_url' => 'https://ihre-domain.de', // Für Passwort-Reset-Links
```

#### 5. Berechtigungen setzen
```bash
chmod 700 data
chmod 600 config/config.php
```

#### 6. Anwendung öffnen
Navigieren Sie zu Ihrer Domain im Browser und melden Sie sich an.

**Standard-Zugangsdaten:**
- Benutzername: `admin`
- Passwort: `admin123`

⚠️ **WICHTIG**: Ändern Sie das Passwort sofort nach dem ersten Login!

### PWA-Installation (Mobile)
1. Öffnen Sie die App im Browser auf Ihrem Smartphone
2. Tippen Sie auf "Zum Startbildschirm hinzufügen" (iOS) oder "Installieren" (Android)
3. Die App erscheint als eigenständige Anwendung auf Ihrem Gerät

---

## 🔄 Update / Upgrade (Datenübernahme)

Bestehende Installationen können aktualisiert werden, **ohne** `install.php` erneut auszuführen. Der Verschlüsselungsschlüssel und die Daten bleiben erhalten.

### Sicheres Update – Schritte

1. **`config/config.php` und `data/` behalten**  
   Diese Verzeichnisse/Dateien beim Deploy **nicht überschreiben**. Der `encryption_key` darf sich nicht ändern – sonst sind bestehende Daten unlesbar.

2. **Neuen Code ausrollen**  
   PHP-/Frontend-Dateien aktualisieren (z. B. per Git-Pull oder Upload), Konfiguration und Daten unverändert lassen.

3. **App einmal öffnen**  
   Beim nächsten Request führt `AppUpgrade` (`src/php/upgrade.php`) ausstehende Migrationen aus (aktuell Schema **v2**):
   - legt unter `data/backups/pre_upgrade_v*_…/` eine Vor-Upgrade-Sicherung an
   - schreibt/aktualisiert `data/app_meta.json` (Schema-Version, Zeitstempel)

4. **Health-Check prüfen**  
   `https://ihre-domain.de/health.php` – Config, Encryption-Key, `data/` und Backup-Verzeichnis sollten `ok` melden.

5. **`install.php` nicht erneut ausführen**  
   Der Installer ist nach der Erstinstallation gesperrt und würde bei erzwungenem Reset den Schlüssel und den Datenzugriff gefährden.

### Hinweise
- Migrationen sind idempotent und brechen ab, wenn Kern-Dateien nicht entschlüsselbar sind (kein Daten-Wipe bei falschem Key).
- Manuelle Vollbackups und JSON/CSV-Exports sind zusätzlich unter **Backup & Export** (Admin) verfügbar.

---

## 📱 Offline-Funktionalität

Die App unterstützt vollständige Offline-Funktionalität für kritische Features - perfekt für den Einsatz in Gebieten mit schlechter Netzabdeckung.

### ✨ Features

**Erweiterte Caching-Strategie**
- Cache-First für statische Assets (CSS, JS, Icons, Fonts)
- Network-First mit Cache-Fallback für API-Endpunkte
- Dynamisches Caching für Seiteninhalte
- Intelligentes Cache-Versioning und automatisches Cleanup

**Offline-Formular-Speicherung**
- Formulare können offline ausgefüllt werden
- Daten werden lokal in IndexedDB gespeichert
- Automatische Synchronisation bei Verbindungswiederherstellung
- Background Sync API für automatische Formular-Übermittlung im Hintergrund

**Benutzeroberfläche**
- Online/Offline-Statusanzeige (unten rechts)
- Sync-Button mit Badge für ausstehende Formulare
- Benachrichtigungssystem für Sync-Feedback
- Offline-Banner auf Formularseiten

### 📋 Verwendung

#### Offline-Formulare ausfüllen

1. **Navigieren Sie zu einem Formular** (Anwesenheitsliste oder Einsatzbericht)
2. **Wenn offline:** Ein gelber Banner wird oben im Formular angezeigt
3. **Füllen Sie das Formular aus** wie gewohnt
4. **Klicken Sie auf "Absenden"**
5. **Das Formular wird lokal gespeichert** und zeigt eine Bestätigung

#### Synchronisation

**Automatisch:**
- Wenn die Verbindung wiederhergestellt wird, synchronisiert die App automatisch alle ausstehenden Formulare
- Eine Benachrichtigung bestätigt erfolgreiche Synchronisationen

**Manuell:**
- Klicken Sie auf das Sync-Symbol (🔄) in der Kopfzeile
- Das Badge zeigt die Anzahl der ausstehenden Formulare
- Nach dem Klicken werden alle ausstehenden Formulare sofort übermittelt

#### Offline-Status

- **Grünes Symbol:** Online und synchronisiert
- **Rotes Symbol:** Offline-Modus aktiv
- Der Status wird automatisch aktualisiert

### 🎨 Neue UI-Komponenten

#### 1. Online/Offline-Statusanzeige (unten rechts)

**Wenn OFFLINE:**
- Erscheint unten rechts mit rotem Rahmen
- Rotes Cloud-Off-Icon
- Text: "Offline"

**Wenn ONLINE:**
- Grüner Rahmen
- Grünes Cloud-Done-Icon
- Text: "Online"
- Verschwindet automatisch nach kurzer Zeit

#### 2. Sync-Button in der Kopfzeile

**Normal (keine ausstehenden Formulare):**
- Button ist versteckt

**Mit ausstehenden Formularen:**
- Erscheint rechts neben Theme-Toggle
- Badge zeigt Anzahl der ausstehenden Formulare
- Badge pulsiert zur Aufmerksamkeit
- Klick startet manuelle Synchronisation
- Button rotiert während Synchronisation

#### 3. Offline-Banner in Formularen

**In Anwesenheitsliste und Einsatzbericht (wenn offline):**
- Gelber Hintergrund mit Warnsymbol
- Informiert Benutzer über Offline-Modus
- Text: "Offline-Modus - Formulare können offline ausgefüllt werden und werden automatisch gesendet, sobald Sie wieder online sind."
- Verschwindet wenn online

#### 4. Benachrichtigungssystem

**Bei Offline-Speicherung:**
- Gelbe Warnung: "Keine Internetverbindung. [Formular] wurde offline gespeichert und wird automatisch gesendet, sobald Sie wieder online sind."

**Bei erfolgreicher Synchronisation:**
- Grüne Bestätigung: "X Formular(e) erfolgreich synchronisiert"

**Bei Sync-Fehler:**
- Rote Fehlermeldung: "Synchronisierung fehlgeschlagen"

### 📱 Benutzer-Workflows

#### Szenario 1: Offline Formular ausfüllen

1. **Benutzer öffnet Anwesenheitsliste**
   - Offline-Banner erscheint (gelb)
   - Status-Indikator zeigt "Offline" (rot, unten rechts)

2. **Benutzer füllt Formular aus**
   - Alle Felder funktionieren normal
   - Datepicker, Dropdowns, etc. funktionieren

3. **Benutzer klickt "Absenden"**
   - Warnung erscheint: "Keine Internetverbindung. Anwesenheitsliste wurde offline gespeichert..."
   - Formular wird zurückgesetzt
   - Sync-Button erscheint mit Badge "1"

4. **Benutzer kann weitermachen**
   - Weitere Formulare ausfüllen möglich
   - Badge erhöht sich: "2", "3", etc.

#### Szenario 2: Automatische Synchronisation

1. **Internet kehrt zurück**
   - Status-Indikator wechselt zu "Online" (grün)
   - Automatische Sync startet (im Hintergrund)

2. **Während Sync**
   - Sync-Button rotiert
   - Badge bleibt sichtbar

3. **Nach erfolgreicher Sync**
   - Benachrichtigung: "2 Formular(e) erfolgreich synchronisiert"
   - Badge verschwindet
   - Sync-Button verschwindet
   - Formulare wurden an Server gesendet
   - E-Mails wurden versendet

#### Szenario 3: Manuelle Synchronisation

1. **Benutzer ist online**
   - Hat ausstehende Formulare (Badge "2")

2. **Benutzer klickt Sync-Button**
   - Button dreht sich
   - Formulare werden übermittelt

3. **Nach Sync**
   - Benachrichtigung zeigt Ergebnis
   - Badge wird aktualisiert oder verschwindet

### 🛠️ Technische Details

**Unterstützte Formulare:**
- ✅ Anwesenheitsliste
- ✅ Einsatzbericht

#### Technische Implementierung

**1. Enhanced Service Worker (`sw.js`)**
```
Cache-Strategien:
├── Cache-First: Statische Assets (CSS, JS, Icons)
├── Network-First: API-Endpunkte, Seiteninhalte
└── Network-Only: Formular-Submissions (mit Offline-Fallback)

Features:
├── Intelligentes Caching mit Versioning (v2)
├── Automatische Cache-Cleanup
├── Background Sync Support
└── IndexedDB-Integration für Form-Sync
```

**2. Offline Storage (`public/js/offline-storage.js`)**
```
Funktionen:
├── IndexedDB-Initialisierung
├── Formulare speichern
├── Ausstehende Formulare abrufen
├── Synchronisation mit Server
├── Background Sync Registrierung
└── Fehlerbehandlung
```

**3. Offline UI (`public/js/offline-ui.js`)**
```
UI-Komponenten:
├── Online/Offline-Statusanzeige (unten rechts)
├── Sync-Button mit Badge (Kopfzeile)
├── Benachrichtigungssystem
└── Offline-Banner auf Formularen
```

**4. Integration in Haupt-App (`public/js/app.js`)**
```
Erweiterungen:
├── Offline-Support-Initialisierung
├── Formular-Handler mit Offline-Erkennung
├── Konfiguration für unterstützte Formulare
└── Graceful Degradation
```

**5. Shared Utilities (`public/js/offline-utils.js`)**
```
Hilfsfunktionen:
├── Offline-Banner-Management
├── DOM-Ready-Checks
└── Wiederverwendbare Utilities
```

**Cache-Strategien:**

*Cache-First (Statische Assets)*
```
Request → Cache → Network (fallback)
```
Verwendet für CSS, JavaScript, Bilder, Icons und Fonts

*Network-First (API & Pages)*
```
Request → Network → Cache (fallback)
```
Verwendet für API-Endpunkte, Seiteninhalte und dynamische Daten

*Network-Only (Formulare & Verwaltung)*
```
Request → Network (no cache)
```
Verwendet für Formular-Submissions (außer bei Offline), Admin-Funktionen und Benutzer-Verwaltung

**Browser-Unterstützung:**

| Feature | Chrome/Edge | Firefox | Safari | Mobile |
|---------|------------|---------|--------|--------|
| Service Worker | ✅ 45+ | ✅ 44+ | ✅ 11.1+ | ✅ |
| IndexedDB | ✅ 24+ | ✅ 10+ | ✅ 10+ | ✅ |
| Background Sync | ✅ 49+ | ⚠️* | ⚠️* | ⚠️** |
| Cache API | ✅ 40+ | ✅ 41+ | ✅ 11.1+ | ✅ |

*Fallback auf manuelle Synchronisation verfügbar  
**Teilweise unterstützt auf Android Chrome

⚠️ Hinweis: Manuelle Synchronisation steht als Fallback in allen Browsern zur Verfügung

**Datenspeicherung:**

Alle offline gespeicherten Formulare werden in IndexedDB gespeichert:
- **Datenbank:** `FeuerwehrAppDB`
- **Object Store:** `pending-forms`
- **Gespeicherte Felder:** ID, Formulartyp, Ziel-URL, FormData, Zeitstempel, Status

**Dateigröße:**
```
Neue Dateien:
├── sw.js (erweitert): +5 KB
├── offline-storage.js: 8 KB
├── offline-ui.js: 9 KB
├── offline-utils.js: 1 KB
└── style.css (Ergänzung): +3 KB

Gesamt: ~26 KB zusätzlich (unkomprimiert)
```

**Performance:**
- **Keine Auswirkungen** im Online-Modus
- **Schnellere Ladezeiten** durch Caching
- **Offline-Formulare** speichern in <100ms
- **Synchronisation** hängt von Netzwerkgeschwindigkeit ab

### 🔒 Sicherheit

- ✅ Alle Daten werden nur lokal im Browser gespeichert
- ✅ Keine sensiblen Daten werden im Cache gespeichert
- ✅ Formulardaten werden nach erfolgreicher Synchronisation gelöscht
- ✅ HTTPS erforderlich für Service Worker in Produktion
- ✅ CodeQL Scan: 0 Alerts - keine Sicherheitsrisiken

### 🧪 Testing und Debugging

#### Offline-Test durchführen:

1. Chrome DevTools öffnen (F12)
2. Network Tab → Online → **Offline** wählen
3. Formular ausfüllen und absenden
4. In IndexedDB (Application Tab) prüfen
5. Online → **Online** wechseln
6. Automatische Sync beobachten

#### IndexedDB anzeigen (Chrome):

```
F12 → Application Tab → IndexedDB
└─► FeuerwehrAppDB
    └─► pending-forms
        └─► Gespeicherte Formulare anzeigen
```

**Struktur eines gespeicherten Formulars:**
```json
{
  "id": 1,
  "type": "Anwesenheitsliste",
  "url": "/src/php/forms/submit_attendance.php",
  "data": FormData {},
  "timestamp": "2025-01-15T10:30:00.000Z",
  "status": "pending"
}
```

#### Cache Storage anzeigen (Chrome):

```
F12 → Application Tab → Cache Storage
└─► feuerwehr-app-static-v2
    ├─► /, /index.php, /public/css/style.css
    ├─► /public/js/app.js
    └─► /public/icons/...
└─► feuerwehr-app-dynamic-v2
    └─► Seiteninhalte
└─► feuerwehr-app-api-v2
    └─► API-Responses
```

#### Service Worker anzeigen (Chrome):

```
F12 → Application Tab → Service Workers
└─► sw.js
    └─► Status: activated and is running
    └─► Update on reload ☐
```

#### Konsolen-Logs:

**Beim Laden der App:**
```
[SW] Installing service worker...
[SW] Caching static assets
[SW] Activating service worker...
[App] Offline support initialized
[OfflineStorage] Database opened successfully
[OfflineUI] Offline UI initialized
```

**Bei Offline-Formular:**
```
Form submission error: TypeError: Failed to fetch
[OfflineStorage] Form saved offline: Anwesenheitsliste 1
[OfflineUI] Pending count: 1
[OfflineStorage] Background sync registered
```

**Bei Sync:**
```
[SW] Background sync triggered
[SW] Found 2 pending forms to sync
[OfflineStorage] Submitting form: 1 Anwesenheitsliste
[SW] Successfully synced form: 1
[OfflineUI] Form synced by service worker: 1
[OfflineStorage] Form submitted successfully: 1
```

### ⚠️ Bekannte Einschränkungen

1. **Datei-Uploads:** Datei-Uploads in der Anwesenheitsliste funktionieren offline, werden aber mit dem Formular gespeichert
2. **Browser-Storage-Limits:** IndexedDB hat Browser-abhängige Speichergrenzen (typisch 50-100MB)
3. **Background Sync:** Nicht in allen Browsern verfügbar (siehe Browser-Unterstützung)

### 🐛 Fehlerbehebung

**Problem: Formulare werden nicht synchronisiert**

Lösung:
1. Überprüfen Sie die Internetverbindung
2. Klicken Sie auf das Sync-Symbol in der Kopfzeile
3. Öffnen Sie die Browser-Konsole (F12) für Details

**Problem: Offline-Status wird nicht angezeigt**

Lösung:
1. Stellen Sie sicher, dass JavaScript aktiviert ist
2. Löschen Sie den Browser-Cache und laden Sie die Seite neu
3. Überprüfen Sie, dass der Service Worker registriert ist (F12 → Application → Service Workers)

**Problem: Cache wird nicht aktualisiert**

Lösung:
1. Die App verwendet Cache-Versionierung - alte Caches werden automatisch gelöscht
2. Bei Problemen: Browser-Cache manuell löschen
3. Service Worker-Update erzwingen: F12 → Application → Service Workers → "Update"

### 📊 Zusammenfassung der Implementierung

Die Offline-Funktionalität wurde vollständig umgesetzt:

✅ **Offline-Cache vorhanden** - Wichtige Informationen und Funktionen offline verfügbar  
✅ **Formulare offline ausfüllbar** - Anwesenheitsliste und Einsatzbericht  
✅ **Lokale Speicherung** - IndexedDB speichert Formulardaten sicher  
✅ **Automatische Synchronisation** - Daten werden automatisch gesendet, wenn online  
✅ **E-Mail-Versand** - Nach erfolgreicher Sync werden E-Mails wie gewohnt versendet  
✅ **Benutzerfreundlich** - Klare visuelle Indikatoren und Benachrichtigungen  
✅ **Sicher** - Keine Sicherheitsrisiken, alle Daten lokal  
✅ **Browser-kompatibel** - Funktioniert in allen modernen Browsern  
✅ **Erweiterbar** - Einfach weitere Formulare hinzufügbar

Die PWA ist jetzt vollständig offline-fähig! 🎉

---

## 🚀 Erste Schritte

### Login

Nach der Installation können Sie sich mit Ihrem Administrator-Benutzer anmelden. Die App bietet einen modernen Login-Bildschirm im Light- und Dark-Mode:

<table>
<tr>
<td width="50%">
<b>Light Mode</b><br/>
<img src="screenshots/01-login-light.png" width="100%" alt="Login Light Mode">
</td>
<td width="50%">
<b>Dark Mode</b><br/>
<img src="screenshots/03-login-dark.png" width="100%" alt="Login Dark Mode">
</td>
</tr>
</table>

Die App unterstützt **drei Benutzerrollen**:
- **Global-Admin**: Vollzugriff auf alle Standorte und Systemeinstellungen
- **Standort-Admin**: Verwaltung eines spezifischen Standorts
- **Operator**: Zugriff auf Formulare und Ansichten (keine Verwaltung)

### Hauptmenü

Nach erfolgreicher Anmeldung gelangen Sie zum Hauptmenü, das schnellen Zugriff auf alle wichtigen Funktionen bietet:

<table>
<tr>
<td width="50%">
<b>Light Mode</b><br/>
<img src="screenshots/02-main-menu-light.png" width="100%" alt="Hauptmenü Light Mode">
</td>
<td width="50%">
<b>Dark Mode</b><br/>
<img src="screenshots/04-main-menu-dark.png" width="100%" alt="Hauptmenü Dark Mode">
</td>
</tr>
</table>

Das Hauptmenü ist in zwei Bereiche unterteilt:

**Operator-Funktionen** (für alle Benutzer verfügbar):
- 📋 Anwesenheitsliste
- 🚒 Einsatzbericht
- 🚗 Fahrzeuge (Ansicht)
- 📞 Wichtige Telefonnummern
- 🗺️ Online Karte
- ⚠️ Gefahrenmatrix
- ☣️ Gefahrstoffkennzeichen
- 📊 Statistiken
- 📁 Formulardaten
- 🔍 Suche
- 📅 Kalender
- 📱 Sitzungen
- 🔔 Nachricht senden (ntfy)

**Administration** (nur für Admins sichtbar):
- 📍 Standorte verwalten
- 🔧 Fahrzeuge verwalten
- 👥 Einsatzkräfte verwalten
- 📞 Telefonnummern verwalten
- 👤 Benutzerverwaltung
- 💾 Backup & Export
- 📜 Audit-Log
- ⚙️ Allgemeine Einstellungen (nur Global-Admin)
- ✉️ E-Mail-Einstellungen (nur Global-Admin)

---

## 🔧 Administration

Der Administrationsbereich steht nur Benutzern mit Admin-Rechten (Global-Admin oder Standort-Admin) zur Verfügung.

### Benutzerverwaltung

Die Benutzerverwaltung ermöglicht das Erstellen und Verwalten von App-Benutzern mit verschiedenen Rollen und Zugriff.

<img src="screenshots/15-user-management.png" width="390" alt="Benutzerverwaltung">

#### Benutzerrollen

##### 1. **Global-Admin** (Globaler Administrator)
- 🌍 **Vollzugriff** auf das gesamte System
- ✅ Kann alle Einsatzabteilungen/Standorte verwalten
- ✅ Kann alle Benutzer (Global und Standort) erstellen, bearbeiten und löschen
- ✅ Zugriff auf alle Fahrzeuge, Einsatzkräfte und Daten aller Standorte
- ✅ Kann globale Einstellungen (E-Mail, Allgemein) konfigurieren
- ✅ Kann Backup & Export sowie Audit-Log nutzen
- ✅ Kann neue Standorte anlegen und verwalten
- 🔑 **Erkennung**: Kein Standort zugewiesen (wird als "Global" angezeigt)

##### 2. **Standort-Admin** (Lokations-Administrator)
- 📍 **Eingeschränkter Zugriff** auf einen bestimmten Standort
- ✅ Kann nur Benutzer des eigenen Standorts verwalten
- ✅ Kann nur Fahrzeuge des eigenen Standorts verwalten
- ✅ Kann nur Einsatzkräfte des eigenen Standorts verwalten
- ✅ Kann Formulare für den eigenen Standort ausfüllen
- ✅ Kann Statistiken des eigenen Standorts einsehen
- ✅ Kann Backup & Export sowie Audit-Log nutzen (Admin-Rechte)
- ❌ **Kein Zugriff** auf:
  - Globale Einstellungen (E-Mail, Allgemein)
  - Andere Standorte und deren Daten
  - Anlegen neuer Standorte
- 🔑 **Erkennung**: Hat einen Standort zugewiesen (z.B. "Willingshausen")

##### 3. **Operator** (Sachbearbeiter)
- 📋 **Lesezugriff** und Formularnutzung
- ✅ Kann Formulare ausfüllen (Anwesenheitsliste, Einsatzbericht inkl. Entwurf)
- ✅ Kann Einsatztools nutzen (Karte, Gefahrenmatrix, Gefahrstoffkennzeichen)
- ✅ Kann Suche, Kalender, Sitzungen und standortbezogene ntfy-Nachrichten nutzen
- ✅ Kann Statistiken einsehen
- ✅ Kann Telefonnummern einsehen
- ❌ **Keine Verwaltungsrechte**:
  - Keine Bearbeitung von Einsatzkräften
  - Keine Bearbeitung von Fahrzeugen
  - Keine Benutzerverwaltung
  - Keine Systemeinstellungen / Backup / Audit-Log

#### Anwendungsfälle

**Szenario 1: Einzelne Feuerwehr**
- Ein Global-Admin für die Verwaltung
- Mehrere Operators für Formular-Eingabe

**Szenario 2: Mehrere Standorte (z.B. Gemeinde mit mehreren Ortswehren)**
- Ein Global-Admin für übergreifende Verwaltung
- Je ein Standort-Admin pro Ortswehr (Willingshausen, Leimbach, etc.)
- Operators an jedem Standort für tägliche Arbeit
- Jeder Standort-Admin verwaltet nur seine eigene Ortswehr

#### Funktionen der Benutzerverwaltung
- ➕ Benutzer erstellen
- ✏️ Benutzer bearbeiten
- 🔒 Passwort ändern
- 🗑️ Benutzer löschen
- 📍 Standort zuweisen (für Standort-Admins und Operators)
- 👁️ Übersicht aller Benutzer (Global-Admin) oder Standort-Benutzer (Standort-Admin)

### Standorte-Verwaltung

Zentrale Verwaltung aller Einsatzabteilungen und Standorte der Feuerwehr.

<img src="screenshots/16-locations-management.png" width="390" alt="Standorte-Verwaltung">

**Verwaltete Informationen:**
- Name des Standorts
- Adresse
- E-Mail-Adresse (für standortspezifische E-Mails)
- ntfy Publish-URL (pro Standort)
- Optionaler ntfy Zugangsschlüssel (Bearer-Token)

**Funktionen**:
- ➕ Standort hinzufügen (nur Global-Admin)
- ✏️ Standort bearbeiten
- 🗑️ Standort löschen (nur Global-Admin)
- 🔍 Übersichtliche Tabellen-Darstellung

**Verwendung:**
Standorte werden bei der Verwaltung von Fahrzeugen, Einsatzkräften und in Formularen als Dropdown zur Verfügung gestellt. Standort-Admins sehen nur ihren zugewiesenen Standort, Global-Admins können alle Standorte verwalten.

**Hinweis zu ntfy:** Die Zugangsschlüssel werden absichtlich nie im Klartext zurück an das Frontend geliefert. In der Standortliste wird nur angezeigt, ob ein Schlüssel hinterlegt ist.

### Einsatzkräfte-Verwaltung

Zentrale Verwaltung aller Feuerwehrmitglieder mit umfassenden Informationen zu Qualifikationen und Führungsrollen.

<img src="screenshots/06-personnel-management.png" width="390" alt="Einsatzkräfte-Verwaltung">

**Verwaltete Informationen:**
- **Persönliche Daten**: Name
- **Qualifikationen**: 
  - AGT (Atemschutzgeräteträger)
  - Maschinist
  - Sanitäter
- **Führungsrollen**:
  - Truppführer
  - Gruppenführer
  - Zugführer
  - Verbandsführer
- **Ausbilder**: Kennzeichnung für Übungsleiter

**Funktionen**:
- ➕ Einsatzkraft hinzufügen
- ✏️ Einsatzkraft bearbeiten
- 🗑️ Einsatzkraft löschen
- 🔍 Übersichtliche Tabellen-Darstellung

Die Einsatzkräfte werden automatisch in allen Formularen (Anwesenheitsliste, Einsatzbericht) zur Auswahl bereitgestellt.

### Fahrzeug-Verwaltung

Verwaltung aller Feuerwehrfahrzeuge mit detaillierten Informationen für den Einsatz.

<img src="screenshots/07-vehicle-management.png" width="390" alt="Fahrzeug-Verwaltung">

**Verwaltete Informationen:**
- **Standort** (Ort)
- **Fahrzeugtyp** (z.B. TSF-W, LF 16)
- **Funkrufname** (z.B. Florian Willingshausen 1/44)

**Funktionen**:
- ➕ Fahrzeug hinzufügen
- ✏️ Fahrzeug bearbeiten
- 🗑️ Fahrzeug löschen

Fahrzeuge werden automatisch in allen Formularen (Einsatzbericht) zur Auswahl bereitgestellt.

### Telefonnummern-Verwaltung

Verwaltung wichtiger Notfallkontakte und Telefonnummern für schnellen Zugriff im Einsatzfall.

**Verwaltete Informationen:**
- Name
- Firma/Organisation
- Funktion
- Telefonnummer

**Funktionen**:
- ➕ Telefonnummer hinzufügen
- ✏️ Telefonnummer bearbeiten
- 🗑️ Telefonnummer löschen

Die Telefonnummern sind für alle Benutzer (auch Operators) im Hauptmenü sichtbar und können direkt per tel:-Link angerufen werden.

### Backup & Export

Admin-Bereich für Vollsicherungen und Audit-fähige Exporte.

<img src="screenshots/28-backup-export.png" width="390" alt="Backup & Export">

**Vollbackup:**
- Momentaufnahme aller verschlüsselten Datendateien unter `data/backups/`
- Automatische Rotation (die letzten **15** Vollbackups werden behalten)
- Zusätzlich: Pre-Upgrade-Snapshots bei Schema-Migrationen

**Export:**
- Entschlüsselte Daten als **JSON** oder **CSV**
- Datensätze wählbar (z. B. Personal, Fahrzeuge, Anwesenheit, Einsätze, Audit)
- Ohne Passwort-Hashes und SMTP-Geheimnisse

**Hinweis:** Nur für Admins. Für Updates siehe [Update / Upgrade](#-update--upgrade-datenübernahme).

### Audit-Log

Nachvollziehbare Protokollierung wichtiger Aktionen (Login-relevant, Admin-Änderungen u. a.).

<img src="screenshots/29-audit-log.png" width="390" alt="Audit-Log">

**Funktionen:**
- Übersicht der letzten Einträge (Aktion, Benutzer, IP, Zeitpunkt)
- Clientseitige Filterung nach Aktion/Benutzer/IP
- Daten verschlüsselt in `data/audit.json`

### Allgemeine Einstellungen

Konfiguration der Feuerwehr-Informationen und des Logos (nur Global-Admin).

<img src="screenshots/17-general-settings.png" width="390" alt="Allgemeine Einstellungen">

**Verwaltete Einstellungen:**
- **Name der Feuerwehr**: Wird in E-Mails und PDFs verwendet
- **Stadt/Gemeinde**: Optional, wird auf separater Zeile angezeigt
- **Logo**: Upload und Verwaltung des Feuerwehr-Logos für E-Mails und PDFs

**Funktionen**:
- ✏️ Feuerwehr-Informationen bearbeiten
- 📤 Logo hochladen (PNG, max. 2MB)
- 🗑️ Logo entfernen
- 💾 Einstellungen speichern

**Hinweis:** Diese Einstellungen sind nur für Global-Admins zugänglich und wirken sich auf alle Standorte aus.

### E-Mail-Einstellungen

SMTP-Konfiguration für den automatischen Versand von Formular-E-Mails (nur Global-Admin).

<img src="screenshots/18-email-settings.png" width="390" alt="E-Mail-Einstellungen">

**Konfigurierbare Parameter:**
- **SMTP Server**: Hostname oder IP-Adresse
- **Port**: SMTP-Port (z.B. 25, 465, 587)
- **Verschlüsselung**: Keine, TLS oder SSL
- **Authentifizierung**: Optional mit Benutzername und Passwort
- **Absender**: E-Mail-Adresse und Name
- **Standard-Empfänger**: E-Mail-Adressen für Formular-Versand

**Funktionen**:
- ✏️ SMTP-Einstellungen bearbeiten
- 🧪 Testmail senden zur Überprüfung
- 💾 Konfiguration speichern

**Hinweis:** Diese Einstellungen sind nur für Global-Admins zugänglich und gelten für alle Standorte.

---

## 👤 Operator-Bereich

Der Operator-Bereich steht allen angemeldeten Benutzern zur Verfügung und bietet Zugriff auf Formulare, Einsatztools und Statistiken.

### Formulare

#### Anwesenheitsliste (Übungsdienste)

Vollständiges Formular zur Dokumentation von Übungsdiensten mit automatischer Berechnung und E-Mail-Versand.

<img src="screenshots/08-attendance-form.png" width="390" alt="Anwesenheitsliste-Formular">

**Felder**:
- 📅 Datum & Uhrzeit (Von/Bis mit automatischer Dauerberechnung)
- 📝 Thema der Übung
- 👨‍🏫 Übungsleiter (nur Einsatzkräfte mit "Ausbilder"-Kennzeichnung oder Freitext)
- 👥 Teilnehmer (Multi-Select aus Einsatzkräften)
- 🔢 Automatische Teilnehmerzählung
- 💬 Anmerkungen (optional)
- 📎 Datei-Upload (optional, wird per E-Mail mitgeschickt)

**Ausgabe**:
- ✉️ HTML-E-Mail mit formatiertem Bericht
- 📄 PDF-Anhang
- 💾 Lokale verschlüsselte Speicherung

#### Einsatzbericht

Umfangreiches Formular basierend auf JetForm-Spezifikation zur vollständigen Dokumentation von Einsätzen.

<img src="screenshots/09-mission-report-form.png" width="390" alt="Einsatzbericht-Formular">

**Basis-Informationen**:
- 🚨 Einsatzgrund (max. 150 Zeichen) *
- 📅 Einsatzdatum *
- ⏰ Beginn & Ende (mit automatischer Dauerberechnung) *
- 📍 Einsatzort *
- 👨‍🚒 Einsatzleiter *

**Einsatz-Details**:
- 📋 Einsatzlage (Beschreibung) *
- ⚙️ Tätigkeiten der Feuerwehr *
- 🧯 Verbrauchte Mittel (optional)
- ⚠️ Besondere Vorkommnisse (optional)
- 💰 Einsatz kostenpflichtig? (Ja/Nein)

**Fahrzeuge & Besatzung**:
- 🚒 Eingesetzte Fahrzeuge * (Multi-Select aus Fahrzeug-Verwaltung + Sonstiges)
- 👥 **Dynamische Fahrzeugbesatzung** (1-20 Einsatzkräfte):
  - Funktion (Dropdown: Fahrzeugführer, Melder, Maschinist, Angriffstrupp-, Wassertrupp-, Schlauchtrupp- Führer/Mann)
  - Name (aus Einsatzkräfte-Liste)
  - Fahrzeug (aus ausgewählten Fahrzeugen)
  - Verdienstausfall (Checkbox)

**Beteiligte Personen**:
- 👤 **Dynamische Beteiligte Personen** (0-10):
  - Beteiligungsart (Verursacher, Geschädigter, Zeuge, Sonstiges)
  - Name
  - Telefonnummer
  - Adresse
  - KFZ-Kennzeichen

(*) = Pflichtfelder

**Ausgabe**:
- ✉️ HTML-E-Mail mit vollständigem Einsatzbericht
- 📄 PDF-Anhang mit Fahrzeugbesatzungs- und Personentabellen
- 💾 Lokale verschlüsselte Speicherung mit eindeutiger ID
- 📝 **Entwurf**: Autosave in `localStorage` – Entwurf laden/verwerfen vor dem Absenden (bei neuen Berichten)

### Globale Suche

Durchsucht Personal, Fahrzeuge, Übungen und Einsätze standortbezogen.

<img src="screenshots/25-search.png" width="390" alt="Globale Suche">

**Funktionen:**
- Live-Suche nach Name, Funkrufname, Thema, Ort u. a.
- Gruppierte Trefferlisten mit Sprung in die jeweilige Ansicht

### Kalender

Monatsübersicht über Übungsdienste und Einsätze.

<img src="screenshots/26-calendar.png" width="390" alt="Kalender">

**Funktionen:**
- Monat vor-/zurückblättern
- Markierung von Übungen und Einsätzen
- Tagesdetail mit Einträgen

### Sitzungen

Verwaltung von „Angemeldet bleiben“-Geräten (Remember-Me).

<img src="screenshots/27-sessions.png" width="390" alt="Sitzungen">

**Funktionen:**
- Übersicht aktiver Geräte (Browser, IP, Gültigkeit)
- Einzelne Sitzungen widerrufen
- Alle eigenen Sitzungen beenden
- Admins können optional Sitzungen aller Benutzer einsehen

### Nachricht senden (ntfy)

Versand kurzer Push-Hinweise über [ntfy](https://ntfy.sh/) an den konfigurierten Kanal des Standorts.

**Voraussetzung:** In der Standortverwaltung Publish-URL (und optional Token) hinterlegen.

**Funktionen:**
- Nachricht an eigenen Standort oder (mit Berechtigung) an alle Standorte mit ntfy-URL
- Optionaler Titel und TTL

### Einsatztools

#### 🗺️ Online Karte

OpenStreetMap-Integration mit **Leaflet** und **leaflet-routing-machine** für Routenplanung und Navigation im Einsatz.

<img src="screenshots/12-map.png" width="390" alt="Online Karte">

**Funktionen**:
- 📍 Aktuelle Position ermitteln
- 🛣️ Routenberechnung zwischen zwei Adressen (OSRM)
- 📏 Entfernungs- und Zeitanzeige
- 📱 Touch-optimierte Bedienung
- 🗺️ Kartenlayer (OSM / Topo / Satellit)
- 🎯 Interaktive Marker für Start- und Zielpunkte

#### ⚠️ Gefahrenmatrix

Interaktive AAAA-CCCC-EEEE Einsatzstellengefahren-Matrix zur systematischen Gefahrenerkennung.

<img src="screenshots/10-danger-matrix.png" width="390" alt="Gefahrenmatrix">

**Gefahrenkategorien**:
- **A** - Atemgifte, Angstreaktionen, Ausbreitung, Atomare Gefahren
- **C** - Chemische Stoffe, Container, Strahlende Stoffe, Elektrizität
- **E** - Erkrankung/Verletzung, Explosion, Einsturz
- Weitere: Tiere, Gewalt, Wasser, Hitze, Verkehr, Umwelt, Radioaktiv

**Funktionen**:
- ✓ Antippen zum Markieren identifizierter Gefahren
- 📋 Echtzeit-Zusammenfassung markierter Gefahren
- 🔄 Reset-Funktion

#### ☣️ Gefahrstoffkennzeichen

Umfassende Gefahrstoff-Datenbank mit GHS-Piktogrammen, ADR-Klassen und UN-Nummern.

<img src="screenshots/11-hazmat.png" width="390" alt="Gefahrstoffkennzeichen">

**GHS-Piktogramme** (9 Symbole):
- Explosiv, Entzündbar, Oxidierend, Druckgase, Ätzend
- Giftig, Gesundheitsschädlich, Gesundheitsgefahr, Umweltgefährlich

**ADR-Gefahrgutklassen** (1-9):
- Mit detaillierten Beschreibungen

**UN-Nummern Suche**:
- Datenbank mit 15+ häufigen Gefahrstoffen
- Detailansicht mit:
  - Beschreibung
  - Gefahren
  - Erste-Hilfe-Maßnahmen
  - Brandbekämpfung
  - Freisetzungsmaßnahmen
- Schnellreferenz-Buttons für häufige Stoffe

#### 📞 Wichtige Telefonnummern

Schneller Zugriff auf wichtige Notfallkontakte mit One-Tap-Calling.

<img src="screenshots/13-phone-numbers.png" width="390" alt="Wichtige Telefonnummern">

**Funktionen**:
- 📋 Übersichtliche Liste aller Kontakte
- 📱 Direkter Anruf via tel:-Link (One-Tap-Calling)
- 🔍 Anzeige von Name, Firma, Funktion und Telefonnummer

#### 🔔 Nachricht senden (ntfy)

Versendet standortbezogene Push-Benachrichtigungen über `ntfy`.

**Funktionen**:
- ✉️ Nachricht mit optionalem Titel senden
- ⏱️ Optionales TTL-Feld (als `X-Ntfy-TTL` Header)
- 📍 Versand an den eigenen Standort oder (bei globalen Rechten) an alle Standorte mit hinterlegter ntfy-URL
- 🧾 Detaillierte Rückmeldung pro Standort (gesendet/übersprungen/fehlerhaft)

**Berechtigungen**:
- **Operator/Standort-Admin mit Standortbindung**: Versand nur an den eigenen Standort
- **Benutzer ohne Standortbindung (z. B. Global-Admin/Operator global)**: Optionaler Versand an alle Standorte

### Statistiken

Umfassende Auswertungen für Übungsdienste und Einsätze auf Abteilungs- und Personenebene.

<img src="screenshots/14-statistics.png" width="390" alt="Statistiken">

#### Abteilungs-Statistik (Jahresansicht)
- 📊 Anzahl Übungsdienste
- ⏱️ Gesamte Übungsstunden
- 🚒 Anzahl Einsätze
- ⏱️ Gesamte Einsatzstunden
- 📅 Jahres-Auswahl per Dropdown

#### Personen-Statistik
- 👤 Auswahl einzelner Einsatzkraft
- 📊 Detaillierte Aufschlüsselung:
  - Teilgenommene Übungen
  - Absolvierte Übungsstunden
  - Teilgenommene Einsätze
  - Absolvierte Einsatzstunden
  - Gesamtstunden

### Formulardaten

Archiv aller eingereichten Formulare mit Übersicht, Detailansicht und Verwaltungsfunktionen.

<img src="screenshots/19-form-data.png" width="390" alt="Formulardaten">

**Verfügbare Daten:**
- **Anwesenheitslisten**: Alle eingereichten Übungsdienste
- **Einsatzberichte**: Alle dokumentierten Einsätze

**Funktionen**:
- 📋 Übersicht aller Formulare nach Datum sortiert
- 🔍 Details einzelner Formulare anzeigen
- 📄 PDF-Dokumente anzeigen/herunterladen
- ✉️ Formulare erneut per E-Mail versenden
- 🗑️ Formulare löschen (nur Admins)
- 🔎 Filterung nach Typ (Anwesenheit/Einsatz)

**Datenschutz:**
- Standort-beschränkte Benutzer sehen nur Formulare ihres Standorts
- Global-Admins haben Zugriff auf alle Formulare
- Alle Daten sind verschlüsselt gespeichert

---

## 🔔 Push-Benachrichtigungen (ntfy)

Die App unterstützt den Versand von Push-Benachrichtigungen über [ntfy](https://ntfy.sh/) mit standortbezogener Konfiguration.

### Konfiguration pro Standort
1. **Administration → Standorte verwalten** öffnen
2. Standort anlegen oder bearbeiten
3. **ntfy Publish-URL** hinterlegen (z. B. `https://ntfy.sh/geheimes-thema` oder eigener Server)
4. Optional **ntfy Zugangsschlüssel** (Bearer-Token) speichern

### Versand
- Auf der Seite **Nachricht senden (ntfy)** Nachricht und optional Titel/TTL eingeben
- Bei entsprechender Berechtigung kann der Versand auf **alle Standorte** erweitert werden
- Nur Standorte mit hinterlegter ntfy-URL werden berücksichtigt

### Technische Hinweise
- Der Versand erfolgt serverseitig über die API `src/php/api/ntfy-send.php`
- `ntfy_token` wird serverseitig gespeichert, aber in API-Antworten nicht im Klartext ausgegeben
- Leere oder fehlende ntfy-URLs werden beim Sammelversand übersprungen und als Hinweis zurückgemeldet

---

## ⚙️ Konfiguration

### Grundeinstellungen

Alle Einstellungen werden in `config/config.php` vorgenommen:

#### Verschlüsselungsschlüssel
```php
'encryption_key' => 'IHR_32_ZEICHEN_SCHLUESSEL_HIER'
```
Generieren mit:
```bash
php -r "echo bin2hex(random_bytes(32));"
```

#### E-Mail-Einstellungen
```php
'email' => [
    'from_address' => 'noreply@ihre-feuerwehr.de',
    'from_name' => 'Feuerwehr Willingshausen',
    'smtp_host' => 'localhost',
    'smtp_port' => 25,
    'smtp_auth' => false,
    'smtp_username' => '',
    'smtp_password' => '',
    'smtp_secure' => '', // 'tls' oder 'ssl'
]
```

#### App-Einstellungen
```php
'app_name' => 'Feuerwehr Management',
'timezone' => 'Europe/Berlin',
'session_timeout' => 3600, // 1 Stunde
```

#### Datenverzeichnis-Pfade
⚠️ **WICHTIG**: Die Pfade für `data_dir` und `backup_dir` sollten **immer** relative Pfade mit `__DIR__` verwenden:
```php
'data_dir' => __DIR__ . '/../data',
'backup_dir' => __DIR__ . '/../data/backups',
```

**Verwenden Sie KEINE absoluten Pfade** wie `/var/www/html/data`, da diese nicht funktionieren, wenn die Anwendung in einem anderen Verzeichnis installiert wird. Der Installations-Wizard generiert automatisch die korrekten relativen Pfade.

### Logo konfigurieren
Platzieren Sie Ihr Feuerwehr-Logo unter:
```
public/assets/logo.png
```
- Empfohlene Größe: 200x200px oder höher
- Format: PNG mit Transparenz
- Wird verwendet in: E-Mails, PDF-Dokumenten

### Erweiterte Konfiguration

#### Apache .htaccess
Für saubere URLs und erhöhte Sicherheit:
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /
    
    # Schutz für Konfigurationsdateien
    <FilesMatch "config\.php">
        Require all denied
    </FilesMatch>
</IfModule>

# Verzeichnis-Auflistungen deaktivieren
Options -Indexes

# PHP-Einstellungen
php_value upload_max_filesize 10M
php_value post_max_size 10M
```

#### PHP-Einstellungen
Empfohlene `php.ini` Einstellungen:
```ini
upload_max_filesize = 10M
post_max_size = 10M
max_execution_time = 60
memory_limit = 128M
session.gc_maxlifetime = 3600
```

---

## 🔒 Sicherheit

### Implementierte Sicherheitsmaßnahmen

#### Datenverschlüsselung
- **AES-256-CBC**: Alle JSON-Dateien (Einsatzkräfte, Fahrzeuge, Formulare)
- **Unique Initialization Vector (IV)**: Für jede Verschlüsselung
- **OpenSSL**: Moderne Kryptographie-Bibliothek

#### Passwort-Sicherheit
- **bcrypt-Hashing**: Mit Kostenfaktor 10
- **Salted Hashes**: Automatisch durch bcrypt
- **Keine Klartextspeicherung**
- **Mindestlänge**: 10 Zeichen für neue Passwörter (Create/Update/Reset); bestehende kürzere Hashes bleiben gültig
- **Rate-Limiting**: Login und Passwort-Reset – 5 Fehlversuche in 15 Minuten → 15 Min. Sperre

#### CSRF & Session-Sicherheit
- **CSRF-Tokens**: Pflicht für state-changing API-/Formular-Requests
- **Session-Timeout**: Automatisches Logout nach Inaktivität
- **Secure Cookies**: httponly & secure Flags (bei HTTPS)
- **Session-Regeneration**: Nach Login
- **Remember-Me**: Widerrufbar über die Sitzungsübersicht

#### Input-Validierung
- **XSS-Schutz**: `htmlspecialchars()` für alle Ausgaben
- **Command Injection Prevention**: Whitelisting + `escapeshellarg()`
- **Upload MIME-Checks**: Dateityp-Prüfung per `finfo` (u. a. Anwesenheitsliste, Logo)
- **SQL Injection**: Nicht relevant (keine SQL-Datenbank)

#### Dateisystem-Sicherheit
- **Verschlüsselte Speicherung**: Alle sensiblen Daten
- **Sicheres Entschlüsseln**: Bei falschem Key werden Dateien **nicht** geleert/überschrieben
- **Installer-Sperre**: `install.php` nach erfolgreicher Installation deaktiviert
- **SMTP-Passwort**: Wird im Formular nicht im Klartext zurückgegeben (Platzhalter)
- **Beschränkte Berechtigungen**: 
  - `data/` Verzeichnis: 700
  - `config/config.php`: 600
- **Web-Zugriff verweigert**: `data/.htaccess` blockiert direkten HTTP-Zugriff auf alle Datendateien
- **Fallback-Schutz**: `data/index.php` beendet Ausführung, wenn `.htaccess` nicht greift

#### data/-Verzeichnis außerhalb des Document Root (empfohlen)

Für maximale Sicherheit sollte das `data/`-Verzeichnis **außerhalb des Web-Document-Root** platziert werden, sodass der Webserver es gar nicht erst ausliefern kann.

**Beispiel-Konfiguration:**

1. Verzeichnis außerhalb des Document Root erstellen:
   ```bash
   sudo mkdir -p /var/feuerwehr/data
   sudo chown www-data:www-data /var/feuerwehr/data
   sudo chmod 700 /var/feuerwehr/data
   ```

2. `config/config.php` anpassen:
   ```php
   'data_dir' => '/var/feuerwehr/data',
   ```

3. Das `data/`-Verzeichnis im App-Root kann dann leer bleiben (oder entfernt werden, da es nicht mehr genutzt wird).

### Best Practices

1. **Ändern Sie Standard-Passwörter sofort**
2. **Verwenden Sie HTTPS** in Produktionsumgebungen
3. **Regelmäßige Backups** der `data/` und `config/` Verzeichnisse (auch über Admin → Backup & Export)
4. **Firewall-Regeln** für Admin-Bereich
5. **Regelmäßige Updates** von PHP und Abhängigkeiten – siehe [Update / Upgrade](#-update--upgrade-datenübernahme)
6. **Monitoring** über `/health.php` und Log-Dateien

---

## 🔧 Troubleshooting

### Login-Probleme nach der Installation?

Wenn Sie nach dem Installations-Wizard die Fehlermeldung **"Ungültiger Benutzername oder Passwort"** erhalten, gibt es verschiedene mögliche Ursachen.

#### Häufigste Ursachen:
- ❌ Session-Verzeichnis nicht beschreibbar (Nginx/PHP-FPM)
- ❌ Falsche Dateiberechtigungen für config/ oder data/
- ❌ Config-Datei wurde nicht erstellt
- ❌ Browser-Cookies blockiert

#### Schnelle Lösung für Nginx + PHP 8.4:
```bash
# Session-Verzeichnis Berechtigungen
sudo chown www-data:www-data /var/lib/php/sessions/
sudo chmod 733 /var/lib/php/sessions/

# App-Verzeichnis Berechtigungen
sudo chown -R www-data:www-data /pfad/zur/app/config /pfad/zur/app/data
sudo chmod 755 /pfad/zur/app/config /pfad/zur/app/data

# PHP-FPM neu starten
sudo systemctl restart php8.4-fpm

# Browser-Cookies löschen und erneut versuchen
```

---

## 🩺 Health-Check & Tests

### Health-Check

Öffentlicher Status-Endpunkt ohne Login:

```
GET /health.php
```

Liefert JSON mit Prüfungen u. a. zu Config, Encryption-Key, Schreibbarkeit von `data/` und Backup-Verzeichnis. Nützlich nach Updates und für Monitoring.

### Tests

Minimales Test-Suite ohne PHPUnit:

```bash
php tests/run.php
```

Prüft u. a. Verschlüsselung, Auth-Helfer und `AppUpgrade`-Migrationen in einer temporären Umgebung.

---

## 🛠️ Technologie-Stack

### Backend
- **PHP 7.4+**: Hauptprogrammiersprache
- **OpenSSL**: Verschlüsselung (AES-256-CBC)
- **JSON**: Datenspeicherung (verschlüsselt)
- **Sessions**: Authentifizierung & Autorisierung
- **upgrade.php**: Idempotente Schema-Migrationen (`AppUpgrade`, aktuell v2)
- **health.php**: Runtime-Health-Check
- **tests/run.php**: CLI-Testläufer

### Frontend
- **HTML5**: Semantisches Markup
- **CSS3**: Responsive Design, Flexbox, Grid
- **JavaScript (Vanilla)**: Keine Frameworks, moderne ES6+ Features
- **Material Design Icons**: Icon-Set

### PWA-Technologien
- **Service Worker**: Offline-Funktionalität & Caching
- **Web App Manifest**: Installierbarkeit
- **Cache API**: Asset-Caching
- **IndexedDB**: Offline-Formulare (implementiert)

### Externe Bibliotheken
- **Leaflet** + **leaflet-routing-machine**: Karten & Routen
- **OpenStreetMap**: Kartenmaterial (Raster-Tiles)
- **OSRM**: Routing-API (Open Source Routing Machine)

### Architektur
```
feuerwehr-app/
├── config/             # Konfigurationsdateien
│   ├── config.php      # Hauptkonfiguration (nicht überschreiben!)
│   └── config.example.php
├── data/               # Verschlüsselte JSON-Dateien
│   ├── users.json
│   ├── personnel.json
│   ├── vehicles.json
│   ├── attendance.json
│   ├── missions.json
│   ├── phone_numbers.json
│   ├── audit.json
│   ├── app_meta.json   # Schema-Version nach Upgrade
│   └── backups/        # Vollbackups & pre_upgrade_* Snapshots
├── public/             # Öffentliche Assets
│   ├── css/
│   ├── js/
│   ├── icons/          # PWA Icons
│   └── assets/         # Logo, Bilder
├── src/php/            # PHP Backend
│   ├── api/            # REST API Endpoints
│   ├── forms/          # Formular-Handler
│   ├── pages/          # Seiten-Templates
│   ├── auth.php        # Authentifizierung
│   ├── datastore.php   # Datenverwaltung
│   ├── email_pdf.php   # E-Mail & PDF
│   ├── encryption.php  # AES-Verschlüsselung
│   └── upgrade.php     # Auto-Migration
├── tests/
│   └── run.php         # Test Suite
├── health.php          # Health-Check
├── index.php           # Haupteinstiegspunkt
├── manifest.json       # PWA Manifest
└── sw.js               # Service Worker
```

---

## 🎨 Design-Philosophie

Das Design orientiert sich an der [alarm-messenger](https://github.com/TimUx/alarm-messenger) App:

- **Farbschema**: Rot (Feuerwehr-Thema) mit Akzenten
- **Light/Dark Mode**: Manuell umschaltbar; ohne gespeicherte Wahl Default aus `prefers-color-scheme`
- **Mobile First**: Primär für Smartphone-Nutzung optimiert
- **Touch-freundlich**: Große Buttons, ausreichend Abstand
- **Material Design**: Moderne, intuitive UI-Komponenten
- **Konsistenz**: Einheitliche Bedienung über alle Bereiche

---

## 📄 Lizenz

MIT License

Copyright (c) 2025–2026 Freiwillige Feuerwehr Willingshausen

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.

---

## 💬 Support

### Bei Fragen oder Problemen

1. **Issues erstellen**: [GitHub Issues](https://github.com/TimUx/feuerwehr-app/issues)
2. **Dokumentation lesen**: Diese README-Datei
3. **Code-Beispiele**: Siehe `config/config.example.php`

### Weiterentwicklung

Geplante / offene Features:
- [x] Export-Funktionen (JSON/CSV) – umgesetzt unter Backup & Export
- [x] Kalender-Integration – Monatsübersicht Übungen/Einsätze
- [~] Push-Benachrichtigungen – teilweise: ntfy-Versand aus der App (kein In-App-Push-Empfang)
- [ ] Multi-Mandanten-Fähigkeit

### Beitragen

Pull Requests sind willkommen! Bitte erstellen Sie zunächst ein Issue für größere Änderungen.

---

**Entwickelt für die Freiwillige Feuerwehr Willingshausen** 🚒

Made with ❤️ in Germany
