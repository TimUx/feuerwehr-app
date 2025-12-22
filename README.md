# Feuerwehr Management App

Progressive Web App (PWA) zum internen Koordinieren einer Feuerwehr.

## Screenshots

### Login (Light Mode)
![Login Light](https://github.com/user-attachments/assets/c73c05ff-c7d3-4250-a646-3d2b6d78817d)

### Login (Dark Mode)
![Login Dark](https://github.com/user-attachments/assets/0217fc88-360c-4ce0-bc5f-d57b26eb7ec3)

### Dashboard
![Dashboard](https://github.com/user-attachments/assets/6249beb6-6502-4f4d-8760-e8607e14f8e1)

### Einsatzkräfte Verwaltung
![Personnel Management](https://github.com/user-attachments/assets/3cce9076-9a4e-4a47-8d0e-a454b4fd60cd)

### Einsatzbericht Formular
![Mission Report Form](https://github.com/user-attachments/assets/22bcbf18-e2d1-4f82-a609-7bfc2bda3add)

## Features

- ✅ **Progressive Web App (PWA)** - Installierbar auf mobilen Geräten
- ✅ **Benutzerverwaltung** - Admin und Operator Rollen
- ✅ **Einsatzkräfte-Verwaltung** - Name, Ausbildungen (AGT, Maschinist, Sanitäter), Führungsrollen (Truppführer, Gruppenführer, Zugführer, Verbandsführer)
- ✅ **Fahrzeugverwaltung** - Ort, Typ, Funkrufname
- ✅ **Dynamische Formulare**:
  - Anwesenheitsliste (mit Multi-Select für Einsatzkräfte)
  - Einsatzbericht (mit Multi-Select für Einsatzkräfte und Fahrzeuge)
- ✅ **E-Mail & PDF-Export** - Formulare werden als HTML-E-Mail mit PDF-Anhang versendet
- ✅ **Statistiken** - Jahresübersicht für gesamte Abteilung und je Einsatzkraft
- ✅ **Datenverschlüsselung** - Alle Daten werden verschlüsselt gespeichert
- ✅ **Light/Dark Mode** - Automatische Themenwahl
- ✅ **Responsive Design** - Optimiert für Mobile und Desktop

## Installation

### Voraussetzungen

- PHP 7.4 oder höher
- Apache Webserver (oder anderer PHP-kompatibler Webserver)
- PHP Extensions: `openssl`, `mbstring`, `json`

### Setup

1. **Repository klonen oder herunterladen**
   ```bash
   git clone https://github.com/TimUx/feuerwehr-app.git
   cd feuerwehr-app
   ```

2. **Konfiguration erstellen**
   ```bash
   cp config/config.example.php config/config.php
   ```

3. **Konfiguration anpassen**
   - Öffnen Sie `config/config.php`
   - Ändern Sie `encryption_key` zu einem zufälligen 32-Zeichen-String
   - Passen Sie die E-Mail-Einstellungen an
   - Ändern Sie die Standard-Admin-Zugangsdaten

4. **Verzeichnisrechte setzen**
   ```bash
   chmod 700 data
   chmod 600 config/config.php
   ```

5. **Webserver konfigurieren**
   
   Für Apache mit mod_rewrite (optional, für saubere URLs):
   ```apache
   <Directory /pfad/zur/app>
       Options -Indexes +FollowSymLinks
       AllowOverride All
       Require all granted
   </Directory>
   ```

6. **App öffnen**
   - Navigieren Sie zu Ihrer Domain/IP im Browser
   - Standard-Login: `admin` / `admin123`
   - **WICHTIG**: Ändern Sie das Passwort sofort nach dem ersten Login!

## Sicherheit

- Alle Datendateien werden mit AES-256-CBC verschlüsselt
- Passwörter werden mit bcrypt gehasht
- Session-basierte Authentifizierung
- CSRF-Schutz implementiert
- XSS-Schutz durch Output-Escaping

## Verwendung

### Benutzerrollen

- **Admin**: Voller Zugriff, kann Listen erstellen/bearbeiten/löschen
- **Operator**: Kann nur Formulare ausfüllen und Listen anzeigen

### Navigation

Die App verwendet eine Seitenleiste mit folgenden Bereichen:
- Dashboard - Übersicht
- Einsatzkräfte - Verwaltung des Personals
- Fahrzeuge - Verwaltung der Fahrzeuge
- Anwesenheitsliste - Formular für Übungsdienste
- Einsatzbericht - Formular für Einsätze
- Statistiken - Auswertungen
- Benutzerverwaltung (nur Admin)

### PWA Installation

Auf mobilen Geräten:
1. Öffnen Sie die App im Browser
2. Wählen Sie "Zum Startbildschirm hinzufügen" oder "Installieren"
3. Die App erscheint als eigenständige Anwendung

## Technologie-Stack

- **Frontend**: HTML5, CSS3, JavaScript (Vanilla)
- **Backend**: PHP 7.4+
- **Storage**: Encrypted JSON files (keine Datenbank erforderlich)
- **Design**: Material Design inspiriert
- **PWA**: Service Worker für Offline-Funktionalität
- **Verschlüsselung**: AES-256-CBC

## Design-Referenz

Das Design orientiert sich an der [alarm-messenger](https://github.com/TimUx/alarm-messenger) App:
- Gleiche Farbpalette (Rot-Thema für Feuerwehr)
- Material Design Icons
- Light/Dark Mode Support
- Responsive Layout

## Konfiguration

### E-Mail-Einstellungen

Bearbeiten Sie `config/config.php` um die E-Mail-Einstellungen anzupassen:

```php
'email' => [
    'from_address' => 'noreply@feuerwehr.local',
    'from_name' => 'Feuerwehr Management System',
    // SMTP-Konfiguration optional
    'smtp_host' => 'localhost',
    'smtp_port' => 25,
]
```

### Logo hochladen

Platzieren Sie das Feuerwehr-Logo unter `public/assets/logo.png`. Dieses wird in E-Mails und PDF-Dokumenten verwendet.

### Verschlüsselungsschlüssel

**WICHTIG**: Ändern Sie den `encryption_key` in `config/config.php` zu einem zufälligen 32-Zeichen-String:

```bash
php -r "echo bin2hex(random_bytes(16));"
```

### Standard-Zugangsdaten

- **Benutzername**: admin
- **Passwort**: admin123

**Bitte ändern Sie das Passwort sofort nach dem ersten Login!**

## PDF-Generierung

Die App unterstützt mehrere Methoden für die PDF-Generierung:

1. **wkhtmltopdf** (empfohlen für Produktion): Installieren Sie wkhtmltopdf für beste Ergebnisse
2. **Fallback**: Einfache PDF-Generierung ohne externe Abhängigkeiten

Für professionelle PDF-Dokumente wird die Installation von mPDF, TCPDF oder Dompdf via Composer empfohlen.

## Formulare

Die App verwendet die bereitgestellten HTML-Vorlagen für:
- **Einsatzbericht**: Vollständiger Bericht mit Fahrzeugbesatzung und beteiligten Personen
- **Anwesenheitsliste**: Übungsleiter, Teilnehmer, Zeitraum und Thema

Beide Formulare senden automatisch eine HTML-E-Mail mit PDF-Anhang.

## Lizenz

[MIT License](LICENSE)

## Support

Bei Fragen oder Problemen erstellen Sie bitte ein Issue im GitHub Repository.
