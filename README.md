# 🚒 Feuerwehr Management App

Progressive Web App (PWA) für das interne Koordinationsmanagement von Feuerwehren. Keine Datenbank erforderlich - läuft mit Apache + PHP und verschlüsselten JSON-Dateien.

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-blue)](https://php.net)
[![PWA](https://img.shields.io/badge/PWA-enabled-green)](https://web.dev/progressive-web-apps/)

---

## 📑 Inhaltsverzeichnis

- [Features](#-features)
- [Screenshots](#-screenshots)
- [Installation](#-installation)
- [App-Bereiche](#-app-bereiche)
  - [Hauptmenü](#hauptmenü)
  - [Einsatzkräfte-Verwaltung](#einsatzkräfte-verwaltung)
  - [Fahrzeug-Verwaltung](#fahrzeug-verwaltung)
  - [Formulare](#formulare)
  - [Einsatztools](#einsatztools)
  - [Statistiken](#statistiken)
  - [Benutzerverwaltung](#benutzerverwaltung)
- [Konfiguration](#️-konfiguration)
- [Sicherheit](#-sicherheit)
- [Technologie-Stack](#-technologie-stack)
- [Support](#-support)
- [Lizenz](#-lizenz)

---

## ✨ Features

### 🔐 Authentifizierung & Sicherheit
- **Zwei Benutzerrollen**: Admin (voller Zugriff) und Operator (Formulare & Ansichten)
- **Verschlüsselte Datenspeicherung**: Alle Daten AES-256-CBC verschlüsselt
- **Sichere Passwörter**: bcrypt-Hashing
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

- **E-Mail & PDF**: Automatischer Versand als HTML-E-Mail mit PDF-Anhang

### 🛠️ Einsatz-Tools
- **Online Karte**: OpenStreetMap mit Routenberechnung
- **Gefahrenmatrix**: AAAA-CCCC-EEEE Einsatzstellen-Gefahren
- **Gefahrstoffkennzeichen**: UN-Nummern Datenbank mit GHS/ADR-Klassen
- **Wichtige Telefonnummern**: Notfallkontakte mit Direktwahl (tel:-Links)

### 📊 Statistiken
- **Jahres-Übersicht**: Abteilungsweit
- **Personen-Statistiken**: Einzelauswertung je Einsatzkraft
- **Auswertungen**: Übungsstunden, Einsatzstunden, Anzahl Dienste

### 🎨 Design & UX
- **Progressive Web App**: Installierbar auf mobilen Geräten
- **Responsive Design**: Optimiert für Mobile (iPhone 13 Pro) und Desktop
- **Light/Dark Mode**: Automatische Themenwahl passend zu alarm-messenger
- **Touch-optimiert**: Große Buttons für mobile Bedienung
- **Material Design Icons**: Moderne, intuitive Benutzeroberfläche
- **Offline-Funktionalität**: Service Worker für Offline-Nutzung

---

## 📱 Screenshots

Alle Screenshots in iPhone 13 Pro Auflösung (390x844px):

### Login
<table>
<tr>
<td width="50%">
<b>Light Mode</b><br/>
<img src="https://github.com/user-attachments/assets/c73c05ff-c7d3-4250-a646-3d2b6d78817d" width="100%" alt="Login Light Mode">
</td>
<td width="50%">
<b>Dark Mode</b><br/>
<img src="https://github.com/user-attachments/assets/0217fc88-360c-4ce0-bc5f-d57b26eb7ec3" width="100%" alt="Login Dark Mode">
</td>
</tr>
</table>

### Hauptmenü
<table>
<tr>
<td width="50%">
<b>Light Mode</b><br/>
<img src="https://github.com/user-attachments/assets/e463670a-8c82-4dd6-99da-970c9b8a705f" width="100%" alt="Hauptmenü Light Mode">
</td>
<td width="50%">
<b>Dark Mode</b><br/>
<img src="https://github.com/user-attachments/assets/bc864160-3420-4735-b789-7f93805408fc" width="100%" alt="Hauptmenü Dark Mode">
</td>
</tr>
</table>

### Navigation
<img src="https://github.com/user-attachments/assets/d25dc4d8-e078-4ad1-aae7-465f7e572a68" width="390" alt="Navigation Menü">

### Einsatzkräfte-Verwaltung
<img src="https://github.com/user-attachments/assets/3cce9076-9a4e-4a47-8d0e-a454b4fd60cd" width="390" alt="Einsatzkräfte Verwaltung">

### Einsatzbericht-Formular
<img src="https://github.com/user-attachments/assets/22bcbf18-e2d1-4f82-a609-7bfc2bda3add" width="390" alt="Einsatzbericht Formular">

---

## 🚀 Installation

### Voraussetzungen

- **PHP 7.4+** mit Extensions: `openssl`, `mbstring`, `json`
- **Apache** oder anderer PHP-kompatibler Webserver
- **Git** (für Installation via Repository)

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
<img src="https://github.com/user-attachments/assets/a8235fe8-fe93-47e2-89d8-54094c59cc45" width="600" alt="System-Voraussetzungen">

Der Installer prüft automatisch:
- ✅ **PHP Version** (7.4.0 oder höher erforderlich)
- ✅ **PHP Extensions**: 
  - Erforderlich: `openssl`, `mbstring`, `json`, `session`
  - Empfohlen: `curl`, `gd`, `zip`
- ✅ **Verzeichnis-Berechtigungen** (`config/`, `data/`)
- ✅ **PHP-Konfiguration** (`upload_max_filesize`, `post_max_size`, `memory_limit`)

Sie können erst fortfahren, wenn alle **erforderlichen** Voraussetzungen erfüllt sind. Warnungen bei empfohlenen Features erlauben das Fortfahren.

##### Schritt 2: Willkommen
<img src="https://github.com/user-attachments/assets/01a9529a-c781-419d-ac0d-8a8bac1f53bc" width="600" alt="Willkommen">

Übersicht über die Einrichtung und was konfiguriert wird.

##### Schritt 3: Administrator-Benutzer erstellen
<img src="https://github.com/user-attachments/assets/2626d66a-c1d9-4368-b1f1-fe023d0b07b4" width="600" alt="Admin-Benutzer">

Erstellen Sie den ersten Admin-Benutzer:
- **Benutzername** (min. 3 Zeichen)
- **Passwort** (min. 6 Zeichen, mit Bestätigung)

Das Passwort wird automatisch mit bcrypt gehashed und verschlüsselt gespeichert.

##### Schritt 4: E-Mail-Einstellungen
<img src="https://github.com/user-attachments/assets/3caa9c1a-c498-4fe8-aabe-96c688861c3a" width="600" alt="E-Mail-Einstellungen">

Konfigurieren Sie E-Mail-Einstellungen für Formular-Übermittlungen:
- **Absender E-Mail-Adresse und Name**
- **Standard-Empfänger** (optional)
- **SMTP Server-Einstellungen**:
  - Host, Port, Verschlüsselung (TLS/SSL)
  - Optional: SMTP-Authentifizierung mit Benutzername/Passwort

##### Schritt 5: Installation abgeschlossen
<img src="https://github.com/user-attachments/assets/bd483670-9d30-4b7a-a787-fff44919689e" width="600" alt="Installation abgeschlossen">

✅ Verschlüsselungsschlüssel automatisch generiert (64 Zeichen, AES-256-CBC)  
✅ Administrator-Benutzer erstellt  
✅ E-Mail-Einstellungen konfiguriert  
✅ Datenverzeichnis erstellt mit sicheren Berechtigungen

**Wichtig:** Der Verschlüsselungsschlüssel wird automatisch generiert - keine Kommandozeile erforderlich!

#### 4. Diagnose-Tests (empfohlen)
Nach erfolgreicher Installation sollten Sie die Diagnose-Tests durchführen, um sicherzustellen, dass alles korrekt funktioniert:

- **Im Wizard:** Klicken Sie auf "Diagnose-Tests durchführen"
- **Direkter Link:** `http://ihre-domain.de/install.php?step=4&diagnose=run`
- **Standalone Tool:** `http://ihre-domain.de/diagnose.php`

Die Diagnose prüft:
- ✅ Konfigurationsdatei und Verschlüsselung
- ✅ Dateiberechtigungen (wichtig für Nginx)
- ✅ Session-Funktionalität
- ✅ PHP Extensions
- ✅ Login-Funktionalität
- ✅ Nginx/PHP-FPM Konfiguration (bei Nginx)

#### 5. Logo hochladen (optional)
Platzieren Sie Ihr Feuerwehr-Logo als `public/assets/logo.png`. Dieses wird in E-Mails und PDFs verwendet.

#### 6. Anmeldung
Nach erfolgreicher Installation und Diagnose können Sie sich mit Ihrem erstellten Administrator-Benutzer anmelden.

**Bei Login-Problemen:** Siehe [TROUBLESHOOTING.md](TROUBLESHOOTING.md) für detaillierte Hilfe.

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
php -r "echo bin2hex(random_bytes(16));"
```
Kopieren Sie den generierten Schlüssel und fügen Sie ihn in `config/config.php` als `encryption_key` ein.

#### 4. E-Mail-Konfiguration anpassen
Öffnen Sie `config/config.php` und passen Sie die E-Mail-Einstellungen an:
```php
'email' => [
    'from_address' => 'noreply@ihre-feuerwehr.de',
    'from_name' => 'Feuerwehr Willingshausen',
    'smtp_host' => 'localhost',
    'smtp_port' => 25,
]
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

## 📂 App-Bereiche

### Hauptmenü
Das Hauptmenü bietet schnellen Zugriff auf alle wichtigen Funktionen mit großen, touch-optimierten Buttons:

**Funktionen** (für alle Benutzer):
- 📋 Anwesenheitsliste
- 🚒 Einsatzbericht
- 🚗 Fahrzeuge (Ansicht)
- 🗺️ Online Karte
- ⚠️ Gefahrenmatrix
- ☣️ Gefahrstoffkennzeichen
- 📞 Wichtige Telefonnummern
- 📊 Statistiken

**Administration** (nur für Admins):
- 👥 Einsatzkräfte verwalten
- 🔧 Fahrzeuge verwalten
- 📞 Telefonnummern verwalten
- 👤 Benutzerverwaltung

---

### Einsatzkräfte-Verwaltung

Zentrale Verwaltung aller Feuerwehrmitglieder mit folgenden Informationen:

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

---

### Fahrzeug-Verwaltung

Verwaltung aller Feuerwehrfahrzeuge mit:

- **Standort** (Ort)
- **Fahrzeugtyp** (z.B. TSF-W, LF 16)
- **Funkrufname** (z.B. Florian Willingshausen 1/44)

Fahrzeuge werden automatisch in allen Formularen zur Auswahl bereitgestellt.

---

### Formulare

#### Anwesenheitsliste (Übungsdienste)

Vollständiges Formular zur Dokumentation von Übungsdiensten:

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

---

#### Einsatzbericht

Umfangreiches Formular basierend auf JetForm-Spezifikation:

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

---

### Einsatztools

#### 🗺️ Online Karte
OpenStreetMap-Integration mit Leaflet.js:
- 📍 Aktuelle Position ermitteln
- 🛣️ Routenberechnung zwischen zwei Adressen
- 📏 Entfernungs- und Zeitanzeige
- 📱 Touch-optimierte Bedienung

#### ⚠️ Gefahrenmatrix
Interaktive AAAA-CCCC-EEEE Einsatzstellengefahren-Matrix:
- **A** - Atemgifte, Angstreaktionen, Ausbreitung, Atomare Gefahren
- **C** - Chemische Stoffe, Container, Strahlende Stoffe, Elektrizität
- **E** - Erkrankung/Verletzung, Explosion, Einsturz
- Weitere: Tiere, Gewalt, Wasser, Hitze, Verkehr, Umwelt, Radioaktiv
- ✓ Antippen zum Markieren identifizierter Gefahren
- 📋 Echtzeit-Zusammenfassung markierter Gefahren
- 🔄 Reset-Funktion

#### ☣️ Gefahrstoffkennzeichen
Umfassende Gefahrstoff-Datenbank:

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
Notfallkontakte-Verwaltung:
- Admin-CRUD-Interface (Erstellen/Bearbeiten/Löschen)
- Felder: Name, Firma, Funktion, Telefonnummer
- Anzeige für alle Benutzer
- 📱 Direkter Anruf via tel:-Link (One-Tap-Calling)

---

### Statistiken

Umfassende Auswertungen für:

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

---

### Benutzerverwaltung

Verwaltung der App-Benutzer (nur für Admins):

**Benutzerrollen**:
- **Admin**: Vollzugriff auf alle Funktionen
- **Operator**: Zugriff auf Formulare und Ansichten (keine Verwaltung)

**Funktionen**:
- ➕ Benutzer erstellen
- ✏️ Benutzer bearbeiten
- 🔒 Passwort ändern
- 🗑️ Benutzer löschen

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
php -r "echo bin2hex(random_bytes(16));"
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

#### Session-Sicherheit
- **Session-Timeout**: Automatisches Logout nach Inaktivität
- **Secure Cookies**: httponly & secure Flags (bei HTTPS)
- **Session-Regeneration**: Nach Login

#### Input-Validierung
- **XSS-Schutz**: `htmlspecialchars()` für alle Ausgaben
- **Command Injection Prevention**: Whitelisting + `escapeshellarg()`
- **SQL Injection**: Nicht relevant (keine SQL-Datenbank)

#### Dateisystem-Sicherheit
- **Verschlüsselte Speicherung**: Alle sensiblen Daten
- **Beschränkte Berechtigungen**: 
  - `data/` Verzeichnis: 700
  - `config/config.php`: 600

### Best Practices

1. **Ändern Sie Standard-Passwörter sofort**
2. **Verwenden Sie HTTPS** in Produktionsumgebungen
3. **Regelmäßige Backups** der `data/` und `config/` Verzeichnisse
4. **Firewall-Regeln** für Admin-Bereich
5. **Regelmäßige Updates** von PHP und Abhängigkeiten
6. **Monitoring** der Log-Dateien

---

## 🔧 Troubleshooting

### Login-Probleme nach der Installation?

Wenn Sie nach dem Installations-Wizard die Fehlermeldung **"Ungültiger Benutzername oder Passwort"** erhalten, gibt es verschiedene mögliche Ursachen.

#### Schnelle Diagnose

1. **Führen Sie die Diagnose-Tests durch:**
   ```
   http://ihre-domain.de/diagnose.php
   ```
   oder
   ```
   http://ihre-domain.de/install.php?step=4&diagnose=run
   ```

2. **Häufigste Ursachen:**
   - ❌ Session-Verzeichnis nicht beschreibbar (Nginx/PHP-FPM)
   - ❌ Falsche Dateiberechtigungen für config/ oder data/
   - ❌ Config-Datei wurde nicht erstellt
   - ❌ Browser-Cookies blockiert

3. **Schnelle Lösung für Nginx + PHP 8.4:**
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

4. **Detaillierte Hilfe:**
   Lesen Sie den [TROUBLESHOOTING.md](TROUBLESHOOTING.md) Guide für:
   - Schritt-für-Schritt Problemlösung
   - Nginx-spezifische Konfiguration
   - PHP 8.4 spezifische Hinweise
   - Debug-Befehle
   - Häufige Fehlerszenarien

---

## 🛠️ Technologie-Stack

### Backend
- **PHP 7.4+**: Hauptprogrammiersprache
- **OpenSSL**: Verschlüsselung (AES-256-CBC)
- **JSON**: Datenspeicherung (verschlüsselt)
- **Sessions**: Authentifizierung & Autorisierung

### Frontend
- **HTML5**: Semantisches Markup
- **CSS3**: Responsive Design, Flexbox, Grid
- **JavaScript (Vanilla)**: Keine Frameworks, moderne ES6+ Features
- **Material Design Icons**: Icon-Set

### PWA-Technologien
- **Service Worker**: Offline-Funktionalität & Caching
- **Web App Manifest**: Installierbarkeit
- **Cache API**: Asset-Caching
- **IndexedDB**: Lokaler Speicher (zukünftig)

### Externe Bibliotheken
- **Leaflet.js**: Karten-Darstellung
- **OpenStreetMap**: Kartenmaterial
- **OpenRouteService**: Routing-API

### Architektur
```
feuerwehr-app/
├── config/             # Konfigurationsdateien
│   ├── config.php      # Hauptkonfiguration
│   └── config.example.php
├── data/               # Verschlüsselte JSON-Dateien
│   ├── users.json
│   ├── personnel.json
│   ├── vehicles.json
│   ├── attendance.json
│   ├── missions.json
│   └── phone_numbers.json
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
│   └── encryption.php  # AES-Verschlüsselung
├── index.php           # Haupteinstiegspunkt
├── manifest.json       # PWA Manifest
└── sw.js               # Service Worker
```

---

## 🎨 Design-Philosophie

Das Design orientiert sich an der [alarm-messenger](https://github.com/TimUx/alarm-messenger) App:

- **Farbschema**: Rot (Feuerwehr-Thema) mit Akzenten
- **Light/Dark Mode**: Automatische Anpassung an Systemeinstellungen
- **Mobile First**: Primär für Smartphone-Nutzung optimiert
- **Touch-freundlich**: Große Buttons, ausreichend Abstand
- **Material Design**: Moderne, intuitive UI-Komponenten
- **Konsistenz**: Einheitliche Bedienung über alle Bereiche

---

## 📄 Lizenz

MIT License

Copyright (c) 2025 Freiwillige Feuerwehr Willingshausen

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

Geplante Features:
- [ ] Formular-Verwaltung (Archiv mit Bearbeiten/Löschen/Erneut senden)
- [ ] Export-Funktionen (CSV, Excel)
- [ ] Kalender-Integration
- [ ] Push-Benachrichtigungen
- [ ] Multi-Mandanten-Fähigkeit

### Beitragen

Pull Requests sind willkommen! Bitte erstellen Sie zunächst ein Issue für größere Änderungen.

---

**Entwickelt für die Freiwillige Feuerwehr Willingshausen** 🚒

Made with ❤️ in Germany
