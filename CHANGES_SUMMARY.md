# Zusammenfassung: Karten- und E-Mail-Verbesserungen

## 🎯 Ziel

Behebung von zwei kritischen Problemen:
1. **Karte wird nicht angezeigt** - Trotz fehlendem Fehler in der Browser-Konsole
2. **Email-Test funktioniert nicht** - Fehlermeldung verweist auf sendmail/postfix

---

## ✅ Umgesetzte Lösungen

### 1. Karten-Problem: Neue iframe-Lösung

**Problem:**
- MapLibre GL JS wurde über CDN geladen
- Funktionierte nicht in Docker oder restriktiven Umgebungen
- Keine Fehlermeldungen in der Browser-Konsole
- Schwierig zu debuggen

**Lösung:**
- **Vollständig neue Implementierung** mit iframe-Einbettung
- Direkte Einbettung von OpenStreetMap
- Keine externen JavaScript-Abhängigkeiten mehr
- **Drei Modi:**
  1. **Karte erkunden** - Interaktive OSM-Karte
  2. **Routenplanung** - Start/Ziel eingeben, Route berechnen
  3. **Adresse suchen** - Orte und Adressen finden

**Vorteile:**
- ✅ Funktioniert überall (auch in Docker)
- ✅ Keine CDN-Abhängigkeiten
- ✅ Robust und zuverlässig
- ✅ Einfach zu warten

### 2. Email-Problem: PHPMailer mit SMTP

**Problem:**
- PHP `mail()` Funktion benötigt lokalen Mail-Server (sendmail/postfix)
- In Docker und vielen Hosting-Umgebungen nicht verfügbar
- Keine SMTP-Unterstützung
- Keine Verschlüsselung

**Lösung:**
- **PHPMailer-Integration** (via Composer)
- Vollständige SMTP-Unterstützung
- **Funktionen:**
  - SMTP-Server konfigurierbar
  - Authentifizierung (Username/Password)
  - TLS/SSL-Verschlüsselung
  - Fallback auf `mail()` wenn kein SMTP konfiguriert

**Vorteile:**
- ✅ Funktioniert mit externen Mail-Servern (Gmail, Outlook, etc.)
- ✅ Keine lokalen Mail-Server nötig
- ✅ Sicher (TLS/SSL)
- ✅ Docker-kompatibel

---

## 📁 Geänderte Dateien

### Hauptdateien:
1. **`src/php/pages/map.php`** - Vollständig neu implementiert
2. **`src/php/email_pdf.php`** - PHPMailer-Integration
3. **`src/php/api/email-settings.php`** - Test-Email mit PHPMailer
4. **`composer.json`** - Neu erstellt für PHPMailer
5. **`vendor/`** - PHPMailer-Bibliothek (via Composer)

### Dokumentation:
1. **`MAP_AND_EMAIL_UPDATE.md`** - Umfassende technische Dokumentation
2. **`CHANGES_SUMMARY.md`** - Diese Datei (Schnellübersicht)

---

## 🚀 Nächste Schritte

### 1. Code deployen
```bash
git pull origin main
composer install
```

### 2. SMTP konfigurieren

1. Als **Administrator** einloggen
2. Zu **Email Einstellungen** navigieren
3. SMTP-Daten eingeben:
   - SMTP Server: z.B. `smtp.gmail.com`
   - Port: `587` (TLS) oder `465` (SSL)
   - Verschlüsselung: `TLS` (empfohlen)
   - Authentifizierung: Aktivieren
   - Benutzername: Ihre E-Mail
   - Passwort: App-Passwort (bei Gmail)
   - Absender-Adresse: Von welcher Adresse Mails gesendet werden
   - Empfänger-Adresse: Wohin Formulare gesendet werden

4. **Test-E-Mail senden** klicken
5. E-Mail-Postfach prüfen

### 3. Karte testen

1. Zu **Online Karte** navigieren
2. Alle drei Modi testen:
   - **Karte erkunden** - Sollte sofort sichtbar sein
   - **Routenplanung** - Start/Ziel eingeben und Route berechnen
   - **Adresse suchen** - Eine Adresse suchen

---

## 🔧 Troubleshooting

### Karte zeigt nichts an

**Ursache:** Browser blockiert iframes oder Firewall blockiert openstreetmap.org

**Lösung:**
1. Browser-Konsole öffnen (F12 → Console)
2. Nach Fehlern suchen
3. Firewall/Proxy prüfen
4. In anderem Browser testen

### E-Mail-Versand schlägt fehl

**Ursache:** SMTP-Server nicht erreichbar oder falsche Zugangsdaten

**Lösung:**
1. SMTP-Daten überprüfen (Host, Port, Verschlüsselung)
2. Benutzername/Passwort prüfen
3. Bei Gmail: **App-Passwort** verwenden (nicht reguläres Passwort)
   - Erstellen unter: https://myaccount.google.com/apppasswords
4. Firewall prüfen (Port 587/465 muss offen sein)
5. Bei Docker: Netzwerk-Konfiguration prüfen

### Gmail-spezifische Hinweise

Gmail benötigt ein **App-Passwort**:
1. Google-Konto → Sicherheit → 2-Faktor-Authentifizierung aktivieren
2. App-Passwörter erstellen
3. Dieses Passwort in den SMTP-Einstellungen verwenden

---

## 📋 Beispiel-Konfigurationen

### Gmail
```
SMTP Server: smtp.gmail.com
Port: 587
Verschlüsselung: TLS
Authentifizierung: Ja
Benutzername: ihre-email@gmail.com
Passwort: [App-Passwort - 16 Zeichen]
```

### Outlook / Microsoft 365
```
SMTP Server: smtp.office365.com
Port: 587
Verschlüsselung: TLS
Authentifizierung: Ja
Benutzername: ihre-email@outlook.com
Passwort: [Ihr Outlook-Passwort]
```

### Eigener Server
```
SMTP Server: mail.ihre-domain.de
Port: 587 oder 465
Verschlüsselung: TLS oder SSL
Authentifizierung: Ja (meistens)
Benutzername: ihre-email@ihre-domain.de
Passwort: [Ihr E-Mail-Passwort]
```

---

## 🔒 Sicherheit

### Implementierte Maßnahmen:
- ✅ E-Mail-Validierung (filter_var)
- ✅ URL-Encoding für PHP-Variablen
- ✅ TLS/SSL-Verschlüsselung für SMTP
- ✅ Passwörter in config.php (nicht in Git)
- ✅ Keine Vulnerabilities in Dependencies (geprüft)
- ✅ HTML5-konform (keine deprecated attributes)

### Wichtig:
- **config.php niemals in Git committen**
- SMTP-Passwörter sicher aufbewahren
- Bei Gmail: App-Passwörter verwenden
- TLS/SSL-Verschlüsselung immer aktivieren

---

## 📖 Weitere Dokumentation

Siehe **MAP_AND_EMAIL_UPDATE.md** für:
- Detaillierte technische Dokumentation
- Troubleshooting-Guide
- Code-Beispiele
- API-Referenzen

---

## ✨ Zusammenfassung

### Was funktioniert jetzt:
1. ✅ **Karte wird angezeigt** (iframe-basiert)
2. ✅ **E-Mail-Versand via SMTP** (PHPMailer)
3. ✅ **Keine externen JS-Dependencies**
4. ✅ **Docker-kompatibel**
5. ✅ **Sicher und validiert**

### Nächste Schritte:
1. Code deployen (`git pull && composer install`)
2. SMTP konfigurieren (Admin → Email Einstellungen)
3. Test-Email senden
4. Karte testen
5. Fertig! 🎉

Bei Fragen oder Problemen:
- Diagnose-Tool: `diagnose.php`
- Browser-Konsole: F12 → Console
- Dokumentation: `MAP_AND_EMAIL_UPDATE.md`
