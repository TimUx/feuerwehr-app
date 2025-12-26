# Karten- und E-Mail-Verbesserungen

## Übersicht

Dieses Update behebt zwei wichtige Probleme:
1. **Karten-Anzeige**: Wechsel von JavaScript-basierten Karten (MapLibre GL JS) zu robusten iframe-Einbettungen
2. **E-Mail-Versand**: Implementierung von SMTP-Unterstützung mit PHPMailer statt PHP `mail()` Funktion

---

## 🗺️ Karten-Funktionalität

### Problem

Die vorherige Implementierung verwendete MapLibre GL JS, welches:
- Externe JavaScript-Bibliotheken von CDN laden musste
- In Docker-Umgebungen oder bei eingeschränkten Netzwerken Probleme hatte
- Keine Fehler in der Browser-Konsole anzeigte, aber trotzdem nicht funktionierte
- Komplex zu debuggen war

### Lösung

**Neue iframe-basierte Implementierung** mit drei Modi:

#### 1. **Karte erkunden** 
- Eingebettete OpenStreetMap-Karte
- Direkte iframe-Einbettung ohne externe JS-Abhängigkeiten
- Funktioniert in allen Umgebungen (auch Docker)
- Zuverlässig und einfach

#### 2. **Routenplanung**
- Start- und Zieleingabe
- Routenberechnung über OpenStreetMap
- Alternative: Route in Google Maps öffnen
- Embedded-Anzeige der Route

#### 3. **Adresse suchen**
- Adresssuche über OpenStreetMap Nominatim
- Direkte Anzeige des Suchergebnisses
- Link zum Öffnen in neuem Tab

### Vorteile der neuen Lösung

✅ **Keine externen JavaScript-Abhängigkeiten**
- Kein Laden von CDN-Ressourcen erforderlich
- Funktioniert auch bei eingeschränktem Netzwerkzugang

✅ **Robuste iframe-Einbettung**
- OpenStreetMap wird direkt eingebettet
- Bewährte Technologie, die überall funktioniert

✅ **Bessere Kompatibilität**
- Funktioniert in Docker-Containern
- Funktioniert hinter Firewalls
- Keine CORS-Probleme

✅ **Einfacher zu warten**
- Weniger Code-Komplexität
- Keine Versionskonflikte mit JS-Bibliotheken

### Migration

Die alte MapLibre GL JS Implementierung wurde **komplett ersetzt**. 

**Keine Änderungen in der Menü-Navigation erforderlich** - die Karten-Seite wird weiterhin über den gleichen Link aufgerufen.

### Nutzung

1. Navigieren Sie zu **Online Karte** im Menü
2. Wählen Sie einen der drei Modi:
   - **Karte erkunden**: Interaktive Karte zum Navigieren
   - **Routenplanung**: Berechnung von Routen zwischen zwei Punkten
   - **Adresse suchen**: Suche nach spezifischen Orten

---

## 📧 E-Mail-Funktionalität (SMTP)

### Problem

Die vorherige Implementierung verwendete PHP's `mail()` Funktion, welche:
- Einen lokalen Mail-Server (sendmail oder postfix) benötigte
- In Docker-Umgebungen oder auf Hosting-Plattformen oft nicht verfügbar war
- Keine SMTP-Authentifizierung unterstützte
- Keine verschlüsselte Verbindung ermöglichte
- Fehlermeldungen waren unklar

### Lösung

**PHPMailer-Integration mit vollständiger SMTP-Unterstützung**

#### Installation

PHPMailer wurde über Composer installiert:
```bash
composer require phpmailer/phpmailer
```

#### Neue Funktionen

✅ **SMTP-Unterstützung**
- Verbindung zu externen Mail-Servern (Gmail, Outlook, etc.)
- SMTP-Authentifizierung (Benutzername/Passwort)
- TLS/SSL-Verschlüsselung

✅ **Flexible Konfiguration**
- SMTP Host und Port konfigurierbar
- Authentifizierung optional
- Verschlüsselung (TLS/SSL) wählbar

✅ **Fallback**
- Wenn kein SMTP konfiguriert ist, wird weiterhin `mail()` verwendet
- Abwärtskompatibel mit bestehenden Installationen

### Konfiguration

Die SMTP-Einstellungen werden über die Admin-Oberfläche konfiguriert:

1. **Login als Administrator**
2. Navigieren zu **Email Einstellungen** im Admin-Menü
3. SMTP-Server konfigurieren:
   - **SMTP Server**: z.B. `smtp.gmail.com`
   - **Port**: `587` (TLS) oder `465` (SSL)
   - **Verschlüsselung**: `TLS` (empfohlen) oder `SSL`
   - **Authentifizierung**: Aktivieren und Zugangsdaten eingeben
   - **Absender-Adresse**: E-Mail-Adresse, von der Mails versendet werden
   - **Empfänger-Adresse**: Standard-Empfänger für Formulare

4. **Test-E-Mail senden** um die Konfiguration zu überprüfen

### Beispiel-Konfigurationen

#### Gmail
```
SMTP Server: smtp.gmail.com
Port: 587
Verschlüsselung: TLS
Authentifizierung: Ja
Benutzername: ihre-email@gmail.com
Passwort: App-Passwort (nicht Ihr reguläres Gmail-Passwort!)
```

**Hinweis**: Für Gmail benötigen Sie ein [App-Passwort](https://support.google.com/accounts/answer/185833)

#### Microsoft 365 / Outlook
```
SMTP Server: smtp.office365.com
Port: 587
Verschlüsselung: TLS
Authentifizierung: Ja
Benutzername: ihre-email@outlook.com
Passwort: Ihr Outlook-Passwort
```

#### Eigener SMTP-Server
```
SMTP Server: mail.ihre-domain.de
Port: 587 oder 465
Verschlüsselung: TLS oder SSL
Authentifizierung: Ja (in den meisten Fällen)
Benutzername: ihre-email@ihre-domain.de
Passwort: Ihr E-Mail-Passwort
```

### Code-Änderungen

#### `src/php/email_pdf.php`
- PHPMailer-Integration hinzugefügt
- `sendEmail()` und `sendEmailWithAttachments()` nutzen jetzt PHPMailer
- Unterstützung für SMTP-Konfiguration aus config.php
- Besseres Error-Handling

#### `src/php/api/email-settings.php`
- Test-E-Mail-Funktion aktualisiert
- Nutzt jetzt `EmailPDF::sendEmail()` statt direktem `mail()`
- Klarere Fehlermeldungen bei SMTP-Problemen

### Vorteile der neuen Lösung

✅ **Funktioniert überall**
- Keine lokalen Mail-Server erforderlich
- Docker-kompatibel
- Hosting-freundlich

✅ **Sicher**
- TLS/SSL-Verschlüsselung
- Authentifizierung
- Sichere Passwort-Speicherung in config.php

✅ **Zuverlässig**
- Verbindung zu professionellen Mail-Servern
- Bessere Zustellbarkeit
- Detaillierte Fehlerberichte

✅ **Flexibel**
- Unterstützt alle gängigen E-Mail-Provider
- Eigene SMTP-Server nutzbar
- Fallback auf `mail()` wenn gewünscht

---

## 🚀 Installation & Deployment

### Voraussetzungen

- PHP 7.4 oder höher
- Composer (für PHPMailer)
- Zugang zu einem SMTP-Server (oder bestehender Mail-Server)

### Schritt-für-Schritt

1. **Code aktualisieren**
   ```bash
   git pull origin main
   ```

2. **Abhängigkeiten installieren**
   ```bash
   composer install
   ```

3. **SMTP konfigurieren** (über Admin-Interface)
   - Login als Admin
   - Email Einstellungen öffnen
   - SMTP-Daten eingeben
   - Test-E-Mail senden

4. **Fertig!**
   - Karte sollte funktionieren
   - E-Mail-Versand sollte funktionieren

---

## 🔧 Troubleshooting

### Karte zeigt nichts an

**Mögliche Ursachen:**
1. Browser blockiert iframes
2. Content Security Policy (CSP) blockiert eingebettete Inhalte
3. Firewall blockiert openstreetmap.org

**Lösungen:**
1. Browser-Einstellungen prüfen
2. CSP-Header anpassen (falls vorhanden)
3. Firewall-Regeln prüfen
4. Browser-Konsole (F12) auf Fehler prüfen

### E-Mail-Versand schlägt fehl

**Mögliche Ursachen:**
1. SMTP-Server nicht erreichbar
2. Falsche Zugangsdaten
3. Port blockiert (587/465)
4. Firewall blockiert ausgehende SMTP-Verbindungen
5. TLS/SSL-Verschlüsselung falsch konfiguriert

**Lösungen:**
1. SMTP-Daten überprüfen
2. Test-E-Mail mit Debug-Modus senden (siehe unten)
3. Firewall-Regeln prüfen
4. In Docker: `--network host` oder Port-Freigaben prüfen
5. Bei Gmail: App-Passwort verwenden, nicht reguläres Passwort

### Debug-Modus aktivieren

Um detaillierte SMTP-Fehler zu sehen, in `src/php/email_pdf.php` auskommentieren:

```php
// Zeile 53 in email_pdf.php:
$mail->SMTPDebug = 2;  // Aktivieren für Debug-Ausgabe
```

Dies zeigt detaillierte SMTP-Kommunikation an.

---

## 📝 Weiterführende Dokumentation

- [PHPMailer GitHub](https://github.com/PHPMailer/PHPMailer)
- [PHPMailer Troubleshooting](https://github.com/PHPMailer/PHPMailer/wiki/Troubleshooting)
- [OpenStreetMap Embedding](https://wiki.openstreetmap.org/wiki/Export)
- [Google Maps API](https://developers.google.com/maps/documentation/urls/get-started)

---

## ⚠️ Wichtige Hinweise

### Sicherheit

- **Niemals** config.php in Git committen
- SMTP-Passwörter werden in config.php gespeichert - Datei-Berechtigungen sicherstellen
- Bei Gmail: Verwenden Sie App-Passwörter, nicht Ihr Haupt-Passwort
- TLS/SSL-Verschlüsselung sollte immer aktiviert sein

### Performance

- iframe-Einbettungen laden Inhalte von openstreetmap.org
- Internetverbindung erforderlich für Karten-Funktionalität
- E-Mail-Versand benötigt Verbindung zum SMTP-Server

### Datenschutz

- Karten-Anfragen gehen an OpenStreetMap (siehe [OSM Privacy Policy](https://wiki.osmfoundation.org/wiki/Privacy_Policy))
- E-Mails werden über konfigurierten SMTP-Server versendet
- Keine Tracking-Cookies oder Analytics

---

## 📅 Changelog

### Version 2.1 - Dezember 2025

**Neue Features:**
- ✨ iframe-basierte Karten-Implementierung
- ✨ SMTP-Unterstützung mit PHPMailer
- ✨ Drei Karten-Modi: Erkunden, Route, Suche

**Verbesserungen:**
- 🚀 Bessere Kompatibilität in Docker
- 🚀 Zuverlässigerer E-Mail-Versand
- 🚀 Keine externen JS-Abhängigkeiten mehr

**Entfernt:**
- 🗑️ MapLibre GL JS Integration
- 🗑️ Abhängigkeit von PHP `mail()` Funktion

---

## 🤝 Support

Bei Problemen:
1. Diagnose-Tool ausführen: `diagnose.php`
2. Browser-Konsole prüfen (F12 → Console)
3. SMTP-Debug-Modus aktivieren (siehe oben)
4. GitHub Issue erstellen mit:
   - Fehlerbeschreibung
   - Browser/PHP-Version
   - Debug-Ausgaben
   - Server-Umgebung (Docker, Apache, etc.)
