# Feuerwehr Management App - Features

## Übersicht

Diese Progressive Web App (PWA) wurde speziell für die interne Verwaltung einer Feuerwehr entwickelt und erfüllt alle Anforderungen aus der Spezifikation.

## Hauptfunktionen

### 1. Progressive Web App (PWA)
- ✅ Installierbar auf mobilen Geräten
- ✅ Offline-Funktionalität durch Service Worker
- ✅ App-ähnliches Erlebnis
- ✅ Manifest.json konfiguriert

### 2. Benutzerverwaltung
- ✅ Zwei Benutzerrollen:
  - **Administrator**: Voller Zugriff auf alle Funktionen
  - **Operator**: Nur Formulare und Ansichten
- ✅ Verschlüsselte Passwortspeicherung (bcrypt)
- ✅ Session-basierte Authentifizierung
- ✅ Benutzer erstellen, bearbeiten, löschen (nur Admin)

### 3. Einsatzkräfte-Verwaltung
- ✅ Zentrale Verwaltung aller Einsatzkräfte
- ✅ Name erforderlich
- ✅ Ausbildungen:
  - AGT (Atemschutzgeräteträger)
  - Maschinist
  - Sanitäter
- ✅ Führungsrollen:
  - Truppführer
  - Gruppenführer
  - Zugführer
  - Verbandsführer
- ✅ CRUD-Operationen (nur Admin)
- ✅ Verschlüsselte Speicherung

### 4. Fahrzeugverwaltung
- ✅ Zentrale Verwaltung aller Fahrzeuge
- ✅ Felder:
  - Typ (z.B. LF 10, TLF 16/25)
  - Ort/Standort
  - Funkrufname
- ✅ CRUD-Operationen (nur Admin)
- ✅ Verschlüsselte Speicherung

### 5. Dynamische Formulare

#### Anwesenheitsliste
- ✅ Datum und Zeitraum (von/bis)
- ✅ Thema der Veranstaltung
- ✅ Multi-Select für Übungsleiter
- ✅ Multi-Select für Teilnehmer
- ✅ Automatische Teilnehmerzählung
- ✅ Anmerkungen
- ✅ Speicherung der Daten
- ✅ HTML-E-Mail Versand
- ✅ PDF-Anhang generiert
- ✅ Verwendet bereitgestellte HTML-Vorlage

#### Einsatzbericht
- ✅ Einsatzdatum
- ✅ Einsatzgrund
- ✅ Einsatzort
- ✅ Einsatzleiter
- ✅ Einsatzbeginn und -ende (mit automatischer Dauerberechnung)
- ✅ Einsatzlage (Freitext)
- ✅ Tätigkeiten der Feuerwehr (Freitext)
- ✅ Verbrauchte Materialien (Freitext)
- ✅ Besondere Vorkommnisse (Freitext)
- ✅ Einsatz kostenpflichtig (Ja/Nein)
- ✅ Multi-Select für eingesetzte Fahrzeuge
- ✅ Multi-Select für Fahrzeugbesatzung
- ✅ Multi-Select für beteiligte Personen
- ✅ Speicherung der Daten
- ✅ HTML-E-Mail Versand
- ✅ PDF-Anhang generiert
- ✅ Verwendet bereitgestellte HTML-Vorlage

### 6. Statistiken
- ✅ Jahresauswahl
- ✅ Gesamtstatistik für die Einsatzabteilung:
  - Anzahl Übungsdienste
  - Übungsstunden
  - Anzahl Einsätze
  - Einsatzstunden
- ✅ Personenspezifische Statistik:
  - Übungsdienste pro Person
  - Übungsstunden pro Person
  - Einsätze pro Person
  - Einsatzstunden pro Person
  - Gesamtstunden pro Person

### 7. Sicherheit
- ✅ Verschlüsselte Datenspeicherung (AES-256-CBC)
- ✅ Kein Zugriff ohne Anmeldung
- ✅ Rollenbasierte Zugriffskontrolle
- ✅ Session-Timeout
- ✅ XSS-Schutz durch Output-Escaping
- ✅ Passwort-Hashing mit bcrypt
- ✅ Sichere Session-Einstellungen

### 8. Design
- ✅ Responsive Design für Mobile und Desktop
- ✅ Light/Dark Mode (manuell umschaltbar)
- ✅ Material Design Icons
- ✅ Rotes Farbschema (Feuerwehr-Thema)
- ✅ Orientiert an alarm-messenger App
- ✅ Moderne Kartenlayouts
- ✅ Sidebar-Navigation

### 9. Technische Anforderungen
- ✅ Läuft auf Standard-Webserver (Apache + PHP)
- ✅ Keine Datenbank erforderlich
- ✅ JSON-Dateispeicherung
- ✅ PHP 7.4+ kompatibel
- ✅ Keine externen Abhängigkeiten erforderlich

## E-Mail-Vorlagen

### Einsatzbericht (Mission Report)
Die E-Mail verwendet die bereitgestellte HTML-Vorlage mit:
- Header mit Logo und "Freiwillige Feuerwehr Willingshausen"
- Roter Trennlinie
- Tabelle mit allen Einsatzdaten
- Fahrzeugbesatzung als Tabelle
- Beteiligte Personen als Tabelle

### Anwesenheitsliste (Attendance List)
Die E-Mail verwendet die bereitgestellte HTML-Vorlage mit:
- Header mit Logo und "Freiwillige Feuerwehr Willingshausen"
- Roter Trennlinie
- Tabelle mit:
  - Datum
  - Zeitraum (von - bis)
  - Thema
  - Übungsleiter
  - Teilnehmeranzahl
  - Teilnehmer (Liste)
  - Anmerkungen

## Installation und Konfiguration

### Voraussetzungen
- PHP 7.4 oder höher
- Apache oder anderer PHP-kompatibler Webserver
- PHP Extensions: openssl, mbstring, json

### Einrichtung
1. Repository klonen
2. `config/config.example.php` zu `config/config.php` kopieren
3. Verschlüsselungsschlüssel generieren und eintragen
4. E-Mail-Einstellungen konfigurieren
5. Logo unter `public/assets/logo.png` platzieren
6. Standard-Login: admin / admin123 (sofort ändern!)

## Datenspeicherung

Alle Daten werden verschlüsselt in JSON-Dateien gespeichert:
- `data/users.json` - Benutzerkonten
- `data/personnel.json` - Einsatzkräfte
- `data/vehicles.json` - Fahrzeuge
- `data/attendance.json` - Anwesenheitslisten
- `data/missions.json` - Einsatzberichte

## API-Endpunkte

- `POST /src/php/api/personnel.php` - Einsatzkräfte verwalten
- `POST /src/php/api/vehicles.php` - Fahrzeuge verwalten
- `POST /src/php/api/users.php` - Benutzer verwalten (Admin)
- `POST /src/php/forms/submit_attendance.php` - Anwesenheitsliste absenden
- `POST /src/php/forms/submit_mission_report.php` - Einsatzbericht absenden

## Browser-Kompatibilität

- ✅ Chrome/Edge (empfohlen)
- ✅ Firefox
- ✅ Safari
- ✅ Mobile Browser (iOS Safari, Chrome Mobile)

## Zukünftige Erweiterungen

Mögliche Erweiterungen:
- Backup/Restore Funktionalität
- Excel-Export für Statistiken
- Kalenderfunktion
- Push-Benachrichtigungen
- Mehrsprachigkeit
- Erweiterte Berichterstellung
