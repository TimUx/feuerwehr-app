# Docker & Karten-Probleme Behebung

## 🗺️ Karte wird nicht angezeigt

Die Karten-Funktion in der Feuerwehr-App benötigt Zugriff auf mehrere externe Dienste. Wenn die Karte nicht angezeigt wird, kann dies verschiedene Ursachen haben.

### Diagnose durchführen

**Schritt 1: Automatische Diagnose**

Führen Sie die erweiterte Diagnose durch:
```
https://ihre-domain.de/diagnose.php
```

Die Diagnose testet automatisch:
- ✅ Docker Container Erkennung
- ✅ DNS Auflösung (OpenStreetMap, OSRM, Nominatim, unpkg.com)
- ✅ Externe API Erreichbarkeit (Kartenkacheln, JavaScript-Bibliotheken, Routing)
- ✅ CSP/CORS Header Konfiguration

**Schritt 2: Browser-Konsole prüfen**

1. Öffnen Sie die Karten-Seite in der App
2. Drücken Sie `F12` um die Entwickler-Tools zu öffnen
3. Wechseln Sie zum Tab "Console" (Konsole)
4. Suchen Sie nach Fehlermeldungen (rot markiert)

Häufige Fehler:
- `Failed to load resource: net::ERR_NAME_NOT_RESOLVED` → DNS Problem
- `Mixed Content` → HTTP/HTTPS Konflikt
- `CORS policy` → Cross-Origin Problem
- `Failed to fetch` → Netzwerk blockiert

---

## 🐳 Docker Container Spezifische Probleme

### Problem 1: Keine externe Verbindung

**Symptome:**
- Karte wird nicht geladen
- Diagnose zeigt "DNS Auflösung fehlgeschlagen"
- API-Tests schlagen fehl

**Lösung:**

#### Option A: DNS Server festlegen
```bash
docker run -d \
  --name feuerwehr-app \
  --dns 8.8.8.8 \
  --dns 8.8.4.4 \
  -p 80:80 \
  ihre-image
```

#### Option B: Docker Compose
```yaml
version: '3.8'
services:
  web:
    image: ihre-image
    ports:
      - "80:80"
    dns:
      - 8.8.8.8
      - 8.8.4.4
    networks:
      - frontend
    
networks:
  frontend:
    driver: bridge
```

#### Option C: Docker Daemon Konfiguration

Erstellen/Bearbeiten Sie `/etc/docker/daemon.json`:
```json
{
  "dns": ["8.8.8.8", "8.8.4.4"]
}
```

Dann Docker neu starten:
```bash
sudo systemctl restart docker
```

### Problem 2: Firewall blockiert ausgehende Verbindungen

**Symptome:**
- DNS funktioniert
- API-Tests schlagen trotzdem fehl
- Timeout-Fehler

**Lösung:**

#### Firewall-Regeln prüfen
```bash
# Auf dem Host-System
sudo iptables -L OUTPUT -v -n

# Wenn Docker verwendet:
sudo iptables -L DOCKER-USER -v -n
```

#### Docker User Chain für ausgehende Verbindungen
```bash
# Erlauben Sie ausgehende HTTPS Verbindungen
sudo iptables -I DOCKER-USER -o eth0 -p tcp --dport 443 -j ACCEPT
sudo iptables -I DOCKER-USER -o eth0 -p tcp --dport 80 -j ACCEPT
```

### Problem 3: Docker Network Mode

**Symptome:**
- Container hat keine Netzwerk-Verbindung
- DNS funktioniert nicht

**Lösung:**

Verwenden Sie den Standard-Bridge-Modus (nicht `host` oder `none`):
```bash
docker run -d \
  --name feuerwehr-app \
  --network bridge \
  -p 80:80 \
  ihre-image
```

### Problem 4: Proxy in Docker Umgebung

**Symptome:**
- Externe Verbindungen werden blockiert
- Unternehmensnetzwerk mit Proxy

**Lösung:**

Proxy-Einstellungen für Container:
```bash
docker run -d \
  --name feuerwehr-app \
  -e HTTP_PROXY=http://proxy.firma.de:8080 \
  -e HTTPS_PROXY=http://proxy.firma.de:8080 \
  -e NO_PROXY=localhost,127.0.0.1 \
  -p 80:80 \
  ihre-image
```

Oder in Docker Compose:
```yaml
version: '3.8'
services:
  web:
    image: ihre-image
    environment:
      - HTTP_PROXY=http://proxy.firma.de:8080
      - HTTPS_PROXY=http://proxy.firma.de:8080
      - NO_PROXY=localhost,127.0.0.1
```

---

## 🔧 Allgemeine Netzwerk-Probleme

### Problem: Verbindung zu externen APIs

Die Karte benötigt Verbindung zu folgenden Diensten:

1. **OpenStreetMap Tiles** (Kartenkacheln)
   - `tile.openstreetmap.org` (Port 443)
   
2. **MapLibre GL JS** (JavaScript-Bibliothek)
   - `unpkg.com` (Port 443)
   
3. **Nominatim** (Geocoding/Adresssuche)
   - `nominatim.openstreetmap.org` (Port 443)
   
4. **OSRM** (Routing/Routenberechnung)
   - `router.project-osrm.org` (Port 443)

**Test der Verbindungen:**

Innerhalb des Docker Containers:
```bash
# Shell im Container öffnen
docker exec -it feuerwehr-app bash

# DNS testen
nslookup tile.openstreetmap.org

# HTTPS Verbindung testen
curl -I https://tile.openstreetmap.org/0/0/0.png
curl -I https://unpkg.com/maplibre-gl@3.6.2/dist/maplibre-gl.js
curl -I https://nominatim.openstreetmap.org/
curl -I https://router.project-osrm.org/
```

### Problem: Mixed Content (HTTP/HTTPS)

**Symptome:**
- Browser blockiert HTTP-Inhalte auf HTTPS-Seite
- Fehler in der Konsole: "Mixed Content"

**Lösung:**

1. Stellen Sie sicher, dass die App über HTTPS erreichbar ist
2. Oder verwenden Sie HTTP für die gesamte App (nur in Testumgebungen!)

**HTTPS mit Let's Encrypt (empfohlen):**
```bash
# Certbot installieren
sudo apt-get update
sudo apt-get install certbot python3-certbot-nginx

# Zertifikat erstellen
sudo certbot --nginx -d ihre-domain.de
```

### Problem: CSP (Content Security Policy)

**Symptome:**
- Browser blockiert externe Ressourcen
- Fehler: "Refused to load ... because it violates the following Content Security Policy directive"

**Lösung:**

Prüfen Sie Ihre Webserver-Konfiguration:

**Nginx:**
```nginx
# /etc/nginx/sites-available/feuerwehr-app
server {
    # ... andere Einstellungen ...
    
    # CSP Header anpassen
    add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://unpkg.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://unpkg.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: https: blob:; connect-src 'self' https://tile.openstreetmap.org https://nominatim.openstreetmap.org https://router.project-osrm.org; worker-src 'self' blob:;";
}
```

**Apache:**
```apache
# .htaccess oder VirtualHost Konfiguration
Header set Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://unpkg.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://unpkg.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: https: blob:; connect-src 'self' https://tile.openstreetmap.org https://nominatim.openstreetmap.org https://router.project-osrm.org; worker-src 'self' blob:;"
```

---

## 🧪 Erweiterte Diagnose

### Container-Netzwerk inspizieren

```bash
# Alle Netzwerke anzeigen
docker network ls

# Details zu einem Netzwerk
docker network inspect bridge

# Container-Netzwerk-Einstellungen
docker inspect feuerwehr-app | grep -A 20 NetworkSettings
```

### Logs analysieren

```bash
# Container Logs
docker logs feuerwehr-app

# Webserver-Logs (im Container)
docker exec -it feuerwehr-app tail -f /var/log/nginx/error.log
docker exec -it feuerwehr-app tail -f /var/log/apache2/error.log

# PHP-FPM Logs
docker exec -it feuerwehr-app tail -f /var/log/php-fpm/error.log
```

### Tcpdump für Netzwerk-Analyse

```bash
# Im Container
docker exec -it feuerwehr-app apt-get update && apt-get install -y tcpdump

# Ausgehende HTTPS-Verbindungen überwachen
docker exec -it feuerwehr-app tcpdump -i eth0 port 443 -n

# DNS-Anfragen überwachen
docker exec -it feuerwehr-app tcpdump -i eth0 port 53 -n
```

---

## 📋 Checkliste zur Fehlersuche

- [ ] **Diagnose-Tool ausführen** (`diagnose.php`)
- [ ] **Browser-Konsole prüfen** (F12 → Console)
- [ ] **Docker Container erkannt?** (im Diagnose-Tool)
- [ ] **DNS funktioniert?** (`nslookup tile.openstreetmap.org`)
- [ ] **Externe APIs erreichbar?** (alle 4 Services grün in Diagnose)
- [ ] **Firewall-Regeln prüfen** (iptables)
- [ ] **Proxy-Konfiguration** (falls vorhanden)
- [ ] **HTTPS aktiviert?** (für Mixed Content)
- [ ] **CSP Header korrekt?** (externe Ressourcen erlaubt)
- [ ] **Container-Logs prüfen** (`docker logs`)

---

## 🆘 Häufige Fehlermeldungen

### "ERR_NAME_NOT_RESOLVED"
→ **DNS Problem:** DNS Server im Docker Container konfigurieren (`--dns 8.8.8.8`)

### "net::ERR_CONNECTION_REFUSED"
→ **Verbindung blockiert:** Firewall oder Netzwerk-Konfiguration prüfen

### "Failed to load MapLibre GL JS"
→ **unpkg.com nicht erreichbar:** Netzwerk-Verbindung oder CSP prüfen

### "No route found"
→ **OSRM nicht erreichbar:** `router.project-osrm.org` muss erreichbar sein

### "Geocoding failed"
→ **Nominatim nicht erreichbar:** `nominatim.openstreetmap.org` prüfen

### "Tiles not loading"
→ **OpenStreetMap nicht erreichbar:** `tile.openstreetmap.org` prüfen

---

## 📞 Support

Wenn Sie diese Anleitung durchgearbeitet haben und das Problem weiterhin besteht:

1. Führen Sie `diagnose.php?debug=1` aus
2. Kopieren Sie die Debug-Ausgabe
3. Erstellen Sie ein GitHub Issue mit:
   - Debug-Ausgabe
   - Browser-Konsolen-Fehler (Screenshot)
   - Docker/System-Konfiguration
   - Schritte, die Sie bereits unternommen haben

---

**Entwickelt für die Freiwillige Feuerwehr Willingshausen** 🚒
