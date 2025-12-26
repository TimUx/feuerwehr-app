# Native PHP 8 SMTP Implementation

## Übersicht

Ab dieser Version verwendet die Feuerwehr-App eine **native PHP 8 SMTP-Implementierung** ohne externe Abhängigkeiten. PHPMailer ist weiterhin als optionale Fallback-Lösung verfügbar.

## 🎯 Warum Native PHP 8 SMTP?

### Vorteile der nativen Implementierung:

✅ **Keine externen Dependencies**
- Kein Composer oder externe Bibliotheken erforderlich
- Funktioniert out-of-the-box mit PHP 8+
- Kleinerer Footprint

✅ **Verwendet PHP 8 Built-in Features**
- `stream_socket_client()` für Socket-Verbindungen
- `stream_socket_enable_crypto()` für TLS/SSL
- Native OpenSSL-Unterstützung
- Standard PHP Sockets

✅ **Vollständige SMTP-Unterstützung**
- SMTP mit STARTTLS (Port 587)
- SMTP mit SSL (Port 465)
- SMTP-Authentifizierung (LOGIN)
- HTML-E-Mails
- Mehrere Anhänge

✅ **Einfacher und wartbar**
- ~200 Zeilen reiner PHP-Code
- Keine Versionskonflikte
- Einfach zu debuggen

## 📁 Dateien

### Neue Dateien:
- `src/php/smtp_client.php` - Native SMTP-Client-Klasse
- `test_smtp.php` - Test-Script zur Überprüfung

### Geänderte Dateien:
- `src/php/email_pdf.php` - Verwendet jetzt native SMTP als Standard

## 🔧 Verwendung

### Standard (Native PHP 8 SMTP)

Die App verwendet automatisch die native SMTP-Implementierung:

```php
// In config.php
$config['email'] = [
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 587,
    'smtp_secure' => 'tls',
    'smtp_auth' => true,
    'smtp_username' => 'ihre-email@gmail.com',
    'smtp_password' => 'app-passwort',
    'from_address' => 'ihre-email@gmail.com',
    'from_name' => 'Feuerwehr Willingshausen',
    'to_address' => 'empfaenger@example.com'
];
```

### Optional: PHPMailer verwenden

Falls Sie PHPMailer bevorzugen, können Sie es aktivieren:

```php
// In config.php
$config['email'] = [
    'use_phpmailer' => true,  // PHPMailer aktivieren
    'smtp_host' => 'smtp.gmail.com',
    // ... restliche Konfiguration
];
```

**Hinweis:** PHPMailer muss via Composer installiert sein: `composer install`

## 🚀 Installation & Setup

### 1. Ohne externe Dependencies (Empfohlen)

```bash
# Nichts zu installieren - funktioniert out-of-the-box!
git pull origin main
```

Die native SMTP-Implementierung ist sofort einsatzbereit.

### 2. Mit PHPMailer (Optional)

```bash
git pull origin main
composer install  # Installiert PHPMailer als Fallback
```

### 3. SMTP konfigurieren

1. Als Admin einloggen
2. **Email Einstellungen** öffnen
3. SMTP-Daten eingeben
4. **Test-E-Mail senden**

## 📋 Unterstützte Konfigurationen

### Gmail
```
SMTP Server: smtp.gmail.com
Port: 587
Verschlüsselung: TLS
Authentifizierung: Ja
Benutzername: ihre-email@gmail.com
Passwort: [App-Passwort]
```

### Outlook / Microsoft 365
```
SMTP Server: smtp.office365.com
Port: 587
Verschlüsselung: TLS
Authentifizierung: Ja
Benutzername: ihre-email@outlook.com
Passwort: [Outlook-Passwort]
```

### Eigener SMTP-Server
```
SMTP Server: mail.ihre-domain.de
Port: 587 (TLS) oder 465 (SSL)
Verschlüsselung: TLS oder SSL
Authentifizierung: Ja
Benutzername: ihre-email@ihre-domain.de
Passwort: [E-Mail-Passwort]
```

## 🔍 Testen

### Test-Script ausführen:

```bash
php test_smtp.php
```

Ausgabe:
```
=== Native PHP 8 SMTP Client Test ===

Test 1: Basic SMTP client instantiation
✓ SMTPClient created successfully

Test 2: Required PHP extensions
✓ openssl: loaded
✓ sockets: loaded

Test 3: Socket functions availability
✓ stream_socket_client(): available
...
```

### Test-Email über Admin-Panel:

1. **Email Einstellungen** → SMTP konfigurieren
2. **Test-E-Mail senden** klicken
3. Postfach prüfen

## 🔧 Technische Details

### Implementierung

Die `SMTPClient`-Klasse implementiert das SMTP-Protokoll nach RFC 5321:

**Unterstützte Befehle:**
- EHLO - Extended Hello
- STARTTLS - TLS-Verschlüsselung aktivieren
- AUTH LOGIN - Authentifizierung
- MAIL FROM - Absender
- RCPT TO - Empfänger
- DATA - E-Mail-Inhalt
- QUIT - Verbindung beenden

**Verschlüsselung:**
- STARTTLS für Port 587 (empfohlen)
- SSL/TLS-Wrapper für Port 465
- TLS 1.2+ via OpenSSL

**Features:**
- MIME-Multipart für Anhänge
- Base64-Encoding für Inhalte
- UTF-8-Unterstützung
- Automatisches Chunking

### Vergleich: Native vs PHPMailer

| Feature | Native PHP 8 | PHPMailer |
|---------|--------------|-----------|
| Dependencies | Keine | composer |
| Dateigröße | ~9 KB | ~200+ KB |
| PHP-Version | 8.0+ | 5.5+ |
| SMTP Support | ✓ | ✓ |
| TLS/SSL | ✓ | ✓ |
| Attachments | ✓ | ✓ |
| HTML Emails | ✓ | ✓ |
| OAuth2 | ✗ | ✓ |
| Advanced Features | Basic | Full |

**Empfehlung:** Für die meisten Anwendungsfälle reicht die native Implementierung vollkommen aus.

## 🐛 Troubleshooting

### E-Mail wird nicht gesendet

**1. SMTP-Server nicht erreichbar**
```bash
# Test connectivity
telnet smtp.gmail.com 587
```

**2. TLS/SSL-Fehler**
- OpenSSL-Extension prüfen: `php -m | grep openssl`
- PHP-Version: Mindestens PHP 8.0

**3. Authentifizierung fehlgeschlagen**
- Bei Gmail: App-Passwort verwenden
- Benutzername/Passwort prüfen

**4. Port blockiert**
- Firewall prüfen (587, 465)
- Bei Docker: `--network host` oder Port-Mapping

### Debug-Modus

Fehler werden automatisch in PHP Error Log geschrieben:

```php
// Letzten SMTP-Response anzeigen
$smtp = new SMTPClient(...);
echo $smtp->getLastResponse();
```

## 🔒 Sicherheit

### Implementierte Sicherheitsmaßnahmen:

✅ **TLS/SSL-Verschlüsselung**
- STARTTLS für sichere Verbindungen
- SSL-Wrapper für Port 465
- Certificate Verification

✅ **E-Mail-Validierung**
- `filter_var(FILTER_VALIDATE_EMAIL)`
- Verhindert Header-Injection

✅ **Sichere Passwort-Speicherung**
- Passwörter in config.php (nicht in Git)
- Keine Klartext-Übertragung

✅ **Error Handling**
- Fehler werden geloggt, nicht angezeigt
- Keine sensiblen Daten in Fehlermeldungen

## 📚 Code-Beispiel

### Einfacher Versand:

```php
require_once 'src/php/smtp_client.php';

$smtp = new SMTPClient(
    'smtp.gmail.com',  // Host
    587,               // Port
    'tls',            // Encryption
    'user@gmail.com', // Username
    'app-password'    // Password
);

$smtp->sendEmail(
    'from@example.com',
    'Absender Name',
    'to@example.com',
    'Test Subject',
    '<h1>Hello World</h1>',
    true,  // HTML
    []     // Attachments
);
```

### Mit Anhängen:

```php
$attachments = [
    'dokument.pdf' => file_get_contents('path/to/file.pdf'),
    'bild.jpg' => file_get_contents('path/to/image.jpg')
];

$smtp->sendEmail(
    'from@example.com',
    'Absender',
    'to@example.com',
    'Bericht',
    '<p>Siehe Anhang</p>',
    true,
    $attachments
);
```

## 🎉 Zusammenfassung

### Was ist neu:

1. ✨ **Native PHP 8 SMTP-Client** ohne Dependencies
2. ✨ **PHPMailer optional** (nicht mehr erforderlich)
3. ✨ **Test-Script** zur Überprüfung
4. ✨ **Automatische Fallbacks** (Native → PHPMailer → mail())

### Migration:

**Keine Änderungen erforderlich!**

Die App verwendet automatisch die native Implementierung. PHPMailer ist weiterhin verfügbar als Fallback oder kann explizit aktiviert werden.

### Empfehlung:

- **Standard-Setup:** Native PHP 8 SMTP (keine Installation nötig)
- **Bei Problemen:** PHPMailer aktivieren via `use_phpmailer` in config
- **Ohne SMTP-Server:** Automatischer Fallback auf PHP `mail()`

---

**Weitere Informationen:**
- Technische Details: `MAP_AND_EMAIL_UPDATE.md`
- Schnellstart: `CHANGES_SUMMARY.md`
- Test: `php test_smtp.php`
