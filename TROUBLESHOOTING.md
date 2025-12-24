# 🔧 Troubleshooting Guide - Login-Probleme nach dem Wizard

## Problem: "Ungültiger Benutzername oder Passwort" nach der Installation

Wenn Sie nach dem erfolgreichen Abschluss des Installations-Wizards die Fehlermeldung "Ungültiger Benutzername oder Passwort" erhalten, kann dies verschiedene Ursachen haben.

## Schnelle Diagnose

### Option 1: Diagnose im Wizard
Nach erfolgreicher Installation klicken Sie auf **"Diagnose-Tests durchführen"** auf der Erfolgsseite:
```
http://ihre-domain.de/install.php?step=4&diagnose=run
```

### Option 2: Standalone Diagnose-Tool
Rufen Sie das eigenständige Diagnose-Tool auf:
```
http://ihre-domain.de/diagnose.php
```

## Häufige Probleme und Lösungen

### 1. Session-Cookie-Parameter werden nicht korrekt gesetzt (PHP 8.5+)

**Symptom:** Login funktioniert nicht, Benutzer wird immer wieder zum Login zurückgeleitet

**Ursache:** 
- Session-Cookies werden mit `HttpOnly` und anderen Sicherheitsparametern nicht korrekt gesetzt
- Dies betrifft besonders PHP 8.5 und neuere Versionen

**Lösung:**
Dieses Problem wurde in Version (nach dem Fix) behoben. Die Anwendung verwendet jetzt `session_set_cookie_params()` statt `ini_set()`.

Wenn Sie eine ältere Version verwenden:
```bash
git pull origin main
```

Weitere Details finden Sie in `LOGIN_FIX.md`.

### 2. Config-Datei wurde nicht erstellt

**Symptom:** `config/config.php` existiert nicht

**Ursachen:**
- Fehlende Schreibrechte für das `config/` Verzeichnis
- PHP-FPM läuft mit falschem Benutzer
- Nginx blockiert Schreibzugriffe

**Lösung:**
```bash
# Berechtigungen setzen
sudo mkdir -p /pfad/zur/app/config
sudo chown www-data:www-data /pfad/zur/app/config
sudo chmod 755 /pfad/zur/app/config

# Installation erneut durchführen
# Besuchen Sie: http://ihre-domain.de/install.php
```

### 3. Session-Probleme (häufigste Ursache bei älteren Installationen)

**Symptom:** Login schlägt fehl, obwohl config.php und users.json existieren

**Ursachen:**
- Session-Verzeichnis ist nicht beschreibbar
- Session wird zwischen Wizard und Login-Seite nicht beibehalten
- Browser-Cookies werden blockiert

**Lösung für Nginx + PHP-FPM:**

```bash
# 1. Session-Verzeichnis prüfen
php -r "echo session_save_path();"

# 2. Berechtigungen setzen (typischer Pfad)
sudo chown www-data:www-data /var/lib/php/sessions/
sudo chmod 733 /var/lib/php/sessions/

# Alternativ für Debian/Ubuntu mit PHP 8.4:
sudo chown www-data:www-data /var/lib/php/sessions/
sudo chmod 1733 /var/lib/php/sessions/

# 3. PHP-FPM neu starten
sudo systemctl restart php8.4-fpm

# 4. Browser-Cookies löschen und erneut versuchen
```

**In php.ini überprüfen:**
```ini
session.save_path = "/var/lib/php/sessions"
session.gc_probability = 1
session.gc_divisor = 1000
```

### 4. Entschlüsselungsfehler

**Symptom:** users.json kann nicht entschlüsselt werden

**Ursachen:**
- Datei wurde beschädigt
- Falscher Verschlüsselungsschlüssel
- OpenSSL-Problem

**Lösung:**
```bash
# Installation komplett neu durchführen
rm config/config.php
rm -rf data/
# Besuchen Sie: http://ihre-domain.de/install.php
```

### 5. Falsche Dateiberechtigungen

**Symptom:** Verschiedene Fehler beim Zugriff auf Dateien

**Empfohlene Berechtigungen:**

```bash
# Für Nginx mit www-data User:
sudo chown -R www-data:www-data /pfad/zur/app/
sudo find /pfad/zur/app/config -type d -exec chmod 755 {} \;
sudo find /pfad/zur/app/config -type f -exec chmod 644 {} \;
sudo find /pfad/zur/app/data -type d -exec chmod 755 {} \;
sudo find /pfad/zur/app/data -type f -exec chmod 644 {} \;

# Installationsdatei ausführbar machen (optional)
sudo chmod 644 /pfad/zur/app/install.php
```

**Sicherere Variante (restriktiver):**
```bash
sudo chown -R www-data:www-data /pfad/zur/app/
sudo chmod 700 /pfad/zur/app/config
sudo chmod 600 /pfad/zur/app/config/config.php
sudo chmod 700 /pfad/zur/app/data
sudo chmod 600 /pfad/zur/app/data/*.json
```

### 5. Nginx-Konfiguration

**Symptom:** PHP-Dateien werden heruntergeladen statt ausgeführt oder 404-Fehler

**Nginx-Konfiguration überprüfen:**

```nginx
server {
    listen 80;
    server_name ihre-domain.de;
    root /pfad/zur/app;
    index index.php index.html;

    # PHP-FPM Konfiguration
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        
        # Wichtig: Diese Parameter müssen korrekt sein
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Zugriff auf sensitive Dateien verhindern
    location ~ ^/(config|data)/.*\.(php|json)$ {
        deny all;
    }
}
```

**Konfiguration testen und neu laden:**
```bash
sudo nginx -t
sudo systemctl reload nginx
```

### 6. PHP 8.4 spezifische Probleme

**Fehlende Extensions:**
```bash
# Alle benötigten Extensions installieren
sudo apt update
sudo apt install php8.4-fpm php8.4-mbstring php8.4-json php8.4-cli

# PHP-FPM Status prüfen
sudo systemctl status php8.4-fpm

# Falls nicht gestartet:
sudo systemctl start php8.4-fpm
sudo systemctl enable php8.4-fpm
```

**PHP-FPM Socket prüfen:**
```bash
# Socket sollte existieren
ls -la /run/php/php8.4-fpm.sock

# Falls nicht, PHP-FPM neu starten
sudo systemctl restart php8.4-fpm
```

## Debug-Befehle

### Umfassende Systemprüfung:
```bash
# PHP Version
php -v

# Geladene Extensions
php -m

# PHP-FPM Status
sudo systemctl status php8.4-fpm

# Nginx Status
sudo systemctl status nginx

# Session-Verzeichnis
ls -la $(php -r "echo session_save_path();")

# Dateiberechtigungen
ls -la /pfad/zur/app/config/
ls -la /pfad/zur/app/data/

# PHP-FPM Log
sudo tail -50 /var/log/php8.4-fpm.log

# Nginx Error Log
sudo tail -50 /var/log/nginx/error.log

# Nginx Access Log
sudo tail -50 /var/log/nginx/access.log
```

### PHP Info Seite erstellen (temporär):
```bash
# Erstellen Sie eine temporäre phpinfo.php
echo "<?php phpinfo(); ?>" | sudo tee /pfad/zur/app/phpinfo.php

# Besuchen Sie: http://ihre-domain.de/phpinfo.php
# Prüfen Sie:
# - session.save_path
# - Loaded Configuration File
# - Server API (sollte FPM/FastCGI sein)

# WICHTIG: Datei danach löschen!
sudo rm /pfad/zur/app/phpinfo.php
```

## Schritt-für-Schritt Problemlösung

### 1. Diagnose durchführen
```
http://ihre-domain.de/diagnose.php
```

### 2. Alle kritischen Fehler beheben
- Folgen Sie den Lösungsvorschlägen in der Diagnose
- Prüfen Sie jeden fehlgeschlagenen Test

### 3. Browser-Cache und Cookies löschen
- Öffnen Sie Developer Tools (F12)
- Löschen Sie alle Cookies für Ihre Domain
- Leeren Sie den Cache
- Schließen Sie den Browser komplett und öffnen Sie ihn neu

### 4. Erneut anmelden
- Gehen Sie zu: `http://ihre-domain.de/index.php`
- Verwenden Sie die Zugangsdaten aus dem Wizard

### 5. Falls immer noch Probleme bestehen

**Komplett-Neuinstallation:**
```bash
# 1. Backup erstellen (falls vorhanden)
sudo cp -r /pfad/zur/app/data /pfad/zur/app/data.backup

# 2. Config und Daten löschen
sudo rm /pfad/zur/app/config/config.php
sudo rm -rf /pfad/zur/app/data

# 3. Berechtigungen korrekt setzen
sudo chown -R www-data:www-data /pfad/zur/app/
sudo chmod 755 /pfad/zur/app/config
sudo chmod 755 /pfad/zur/app/data 2>/dev/null || true

# 4. PHP-FPM neu starten
sudo systemctl restart php8.4-fpm

# 5. Installation neu durchführen
# Besuchen Sie: http://ihre-domain.de/install.php

# 6. WICHTIG: Notieren Sie sich die Zugangsdaten!

# 7. Diagnose durchführen
# Besuchen Sie: http://ihre-domain.de/install.php?step=4&diagnose=run
```

## Typische Fehlerszenarien

### Szenario 1: Shared Hosting
**Problem:** Kein Root-Zugriff, Session-Probleme

**Lösung:**
```php
// In einer .htaccess oder .user.ini Datei:
session.save_path = "/home/IHR_USER/tmp"
```

Erstellen Sie das tmp-Verzeichnis:
```bash
mkdir ~/tmp
chmod 700 ~/tmp
```

### Szenario 2: Docker/Container
**Problem:** Berechtigungen zwischen Host und Container

**Lösung:**
```dockerfile
# Im Dockerfile oder docker-compose.yml
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html/config /var/www/html/data
```

### Szenario 3: SELinux aktiviert
**Problem:** SELinux blockiert Schreibzugriffe

**Lösung:**
```bash
# SELinux Context setzen
sudo semanage fcontext -a -t httpd_sys_rw_content_t "/pfad/zur/app/config(/.*)?"
sudo semanage fcontext -a -t httpd_sys_rw_content_t "/pfad/zur/app/data(/.*)?"
sudo restorecon -Rv /pfad/zur/app/config
sudo restorecon -Rv /pfad/zur/app/data

# Oder SELinux temporär deaktivieren (nicht empfohlen für Produktion)
sudo setenforce 0
```

## Support

Wenn Sie nach Durchführung aller Schritte immer noch Probleme haben:

1. **Führen Sie die Diagnose durch** und speichern Sie die Ergebnisse
2. **Erstellen Sie ein GitHub Issue** mit:
   - Diagnose-Ergebnissen
   - PHP Version (`php -v`)
   - Webserver (Nginx Version: `nginx -v`)
   - Betriebssystem
   - Relevante Log-Einträge

## Weitere Ressourcen

- [PHP Session Handling](https://www.php.net/manual/en/book.session.php)
- [Nginx + PHP-FPM Configuration](https://www.nginx.com/resources/wiki/start/topics/examples/phpfcgi/)
- [PHP 8.4 Migration Guide](https://www.php.net/manual/en/migration84.php)
