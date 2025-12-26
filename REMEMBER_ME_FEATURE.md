# "Angemeldet bleiben" Feature

## 🔐 Automatische Anmeldung

Die Feuerwehr-App bietet jetzt eine **"Angemeldet bleiben"** Funktion, die es Benutzern ermöglicht, automatisch angemeldet zu bleiben, ohne sich bei jedem Besuch neu anzumelden.

---

## ✨ Features

### Für Benutzer

- ✅ **Checkbox beim Login:** "Angemeldet bleiben" aktivieren
- ✅ **30 Tage Gültigkeit:** Automatische Anmeldung für 30 Tage
- ✅ **Sichere Tokens:** Verschlüsselte Token-basierte Authentifizierung
- ✅ **Automatische Abmeldung:** Bei manueller Abmeldung wird Token gelöscht
- ✅ **Multi-Device Support:** Funktioniert auf allen Geräten separat

### Technische Details

- 🔒 **Sichere Token-Generierung:** 32 Byte kryptografisch sichere Zufallszahlen
- 🔒 **Token-Hashing:** Tokens werden mit bcrypt gehashed gespeichert
- 🔒 **Verschlüsselung:** Token-Datei ist AES-256-CBC verschlüsselt
- 🔒 **HttpOnly Cookie:** Schutz vor XSS-Angriffen
- 🔒 **Secure Cookie:** Über HTTPS (wenn verfügbar)
- 🔒 **Automatische Bereinigung:** Abgelaufene Tokens werden entfernt

---

## 📖 Benutzer-Anleitung

### Anmeldung mit "Angemeldet bleiben"

1. Öffnen Sie die Login-Seite
2. Geben Sie Benutzername und Passwort ein
3. ✅ Aktivieren Sie **"Angemeldet bleiben"**
4. Klicken Sie auf **"Anmelden"**

```
┌─────────────────────────────┐
│  Benutzername: [admin    ]  │
│  Passwort:     [********  ]  │
│                              │
│  ☑ Angemeldet bleiben        │ ← Hier aktivieren!
│                              │
│  [      Anmelden      ]      │
└─────────────────────────────┘
```

### Was passiert nach der Anmeldung?

- ✅ Sie sind sofort angemeldet
- ✅ Ein sicherer Token wird auf dem Server gespeichert
- ✅ Ein Cookie wird in Ihrem Browser gesetzt
- ✅ Bei zukünftigen Besuchen werden Sie automatisch angemeldet

### Wie lange bleibe ich angemeldet?

**30 Tage** ab dem letzten Login mit "Angemeldet bleiben".

Nach 30 Tagen:
- Token läuft ab
- Sie müssen sich erneut anmelden
- Einfach wieder Checkbox aktivieren für weitere 30 Tage

### Manuelle Abmeldung

Wenn Sie sich manuell abmelden:
- Token wird vom Server gelöscht
- Cookie wird aus dem Browser entfernt
- Sie müssen sich beim nächsten Besuch neu anmelden

**So melden Sie sich ab:**
1. Klicken Sie auf das **Logout-Symbol** (↗) in der oberen rechten Ecke
2. Oder navigieren Sie zu: `index.php?action=logout`

---

## 🔒 Sicherheit

### Ist "Angemeldet bleiben" sicher?

✅ **Ja, aber mit Einschränkungen:**

**Sicher auf:**
- Persönlichen Geräten (eigenes Smartphone, Laptop)
- Privaten Netzwerken
- Geräten mit Bildschirmsperre

**NICHT sicher auf:**
- Öffentlichen Computern (z.B. Internet-Café)
- Gemeinsam genutzten Tablets
- Geräten ohne Bildschirmsperre

### Best Practices

#### ✅ Empfohlen

```
Privates iPhone/iPad
├─ ✅ "Angemeldet bleiben" aktivieren
├─ ✅ Bildschirmsperre aktivieren (Face ID / Touch ID)
└─ ✅ Gerät nicht mit anderen teilen
```

#### ⚠️ Vorsichtig

```
Feuerwehr-Tablet (gemeinsam genutzt)
├─ ⚠️ Nur bei Bedarf aktivieren
├─ ⚠️ Nach Nutzung immer abmelden
└─ ⚠️ Tablet mit PIN sichern
```

#### ❌ Nicht empfohlen

```
Öffentlicher Computer
├─ ❌ "Angemeldet bleiben" NICHT aktivieren
├─ ❌ Nach Nutzung immer abmelden
└─ ❌ Browser-Cache löschen
```

---

## 🛠️ Technische Implementierung

### Token-Generierung

```php
// 32 Byte kryptografisch sicherer Zufallsstring
$token = bin2hex(random_bytes(32)); // 64 Zeichen Hex

// Token wird mit bcrypt gehashed
$hashedToken = password_hash($token, PASSWORD_DEFAULT);
```

### Token-Speicherung

Tokens werden in `data/remember_tokens.json` gespeichert:
```json
[
  {
    "token": "$2y$10$...",  // bcrypt Hash
    "user_id": "user_abc123",
    "expiry": 1709308800,   // Unix Timestamp
    "created": 1706716800
  }
]
```

Die Datei ist **AES-256-CBC verschlüsselt**.

### Cookie-Einstellungen

```php
setcookie(
    'remember_me',          // Cookie Name
    $token,                 // Plain Token (64 Zeichen Hex)
    $expiry,                // 30 Tage
    '/',                    // Pfad
    '',                     // Domain
    $isSecure,              // Secure Flag (nur HTTPS)
    true                    // HttpOnly Flag
);
```

### Auto-Login Ablauf

```
1. Benutzer öffnet App
   ↓
2. Session existiert?
   ├─ Ja → Benutzer ist angemeldet ✅
   └─ Nein → Prüfe Remember-Me Cookie
              ↓
3. Cookie vorhanden?
   ├─ Nein → Zeige Login-Seite
   └─ Ja → Validiere Token
           ↓
4. Token gültig?
   ├─ Nein → Lösche Cookie, zeige Login
   └─ Ja → Erstelle Session, anmelden ✅
```

---

## 🧹 Wartung & Verwaltung

### Alte Tokens bereinigen

Tokens werden automatisch bereinigt:
- Bei jedem neuen Login
- Bei Validierung eines Tokens
- Beim Speichern eines neuen Tokens

Manuelle Bereinigung (als Admin):
```bash
# Alle Remember-Me Tokens löschen
rm data/remember_tokens.json

# Benutzer müssen sich neu anmelden
```

### Token-Datei überwachen

```bash
# Token-Datei Größe prüfen
ls -lh data/remember_tokens.json

# Anzahl aktiver Tokens (ungefähr)
# Jeder Token-Eintrag ist ~200-300 Bytes
# Dateigröße / 250 = ungefähre Anzahl
```

### Sicherheitsaudit

Regelmäßig prüfen:
1. Sind viele abgelaufene Tokens vorhanden?
2. Gibt es verdächtige Token-Aktivitäten?
3. Werden Tokens auf unsicheren Geräten verwendet?

---

## ❓ Häufig gestellte Fragen

### Kann ich mehrere Geräte gleichzeitig verwenden?

✅ **Ja!** Jedes Gerät erhält einen eigenen Token. Sie können auf allen Geräten gleichzeitig angemeldet bleiben.

**Beispiel:**
- iPhone: Token A (30 Tage)
- Laptop: Token B (30 Tage)
- iPad: Token C (30 Tage)

### Was passiert bei Passwort-Änderung?

❌ **Token wird NICHT automatisch ungültig.**

**Sicherheitsempfehlung:**
1. Admin sollte alle Tokens löschen nach Passwort-Änderung
2. Oder: Logout-Funktion in Passwort-Änderung einbauen

```bash
# Als Admin: Nach Passwort-Änderung
rm data/remember_tokens.json
```

### Wird der Token bei jedem Besuch erneuert?

❌ **Nein.** Der Token bleibt für 30 Tage gültig ab Erstellung.

**Hinweis:** Eine zukünftige Version könnte "Token Rotation" implementieren (Token wird bei jedem Besuch erneuert).

### Kann ich die Gültigkeit ändern?

✅ **Ja!** In `src/php/auth.php`:

```php
private static function setRememberMeCookie($userId) {
    // Ändere diese Zeile:
    $expiry = time() + (30 * 24 * 60 * 60); // 30 Tage
    
    // Beispiele:
    // 7 Tage:  $expiry = time() + (7 * 24 * 60 * 60);
    // 90 Tage: $expiry = time() + (90 * 24 * 60 * 60);
    // 1 Jahr:  $expiry = time() + (365 * 24 * 60 * 60);
}
```

### Funktioniert es im privaten Modus?

⚠️ **Teilweise.**

- Anmeldung funktioniert
- Token wird gesetzt
- **ABER:** Browser löscht Cookie beim Schließen
- → Nicht wirklich "angemeldet bleiben"

### Was passiert bei gleichzeitiger Nutzung?

✅ **Kein Problem!** Tokens sind unabhängig voneinander.

**Beispiel:**
1. Benutzer meldet sich auf Gerät A an (Token A)
2. Benutzer meldet sich auf Gerät B an (Token B)
3. Beide Geräte funktionieren parallel
4. Abmeldung auf Gerät A löscht nur Token A
5. Gerät B bleibt angemeldet

---

## 🔐 Sicherheits-Checkliste

### Für Benutzer

- [ ] "Angemeldet bleiben" nur auf persönlichen Geräten
- [ ] Bildschirmsperre aktivieren
- [ ] Bei Verlust des Geräts → Admin kontaktieren
- [ ] Bei öffentlichen Geräten immer abmelden
- [ ] Browser-Cache regelmäßig löschen

### Für Administratoren

- [ ] HTTPS aktivieren (Secure Cookie)
- [ ] Token-Datei regelmäßig überwachen
- [ ] Bei Sicherheitsvorfällen: Alle Tokens löschen
- [ ] Benutzer über sichere Nutzung informieren
- [ ] Dateirechte prüfen (`chmod 600 data/remember_tokens.json`)

---

## 📊 Monitoring

### Token-Aktivität überwachen

```bash
# Im diagnose.php könnte man hinzufügen:
# - Anzahl aktiver Tokens
# - Anzahl abgelaufener Tokens
# - Ältester Token
# - Neuester Token
```

### Beispiel-Statistik

```
Remember-Me Token Statistik:
├─ Aktive Tokens:     23
├─ Abgelaufene:       5 (werden bei nächstem Login entfernt)
├─ Ältester Token:    vor 28 Tagen
└─ Durchschn. Alter:  12 Tage
```

---

## 🚀 Zukünftige Verbesserungen

Mögliche Erweiterungen:

1. **Token Rotation**
   - Token wird bei jedem Besuch erneuert
   - Höhere Sicherheit

2. **Device Fingerprinting**
   - Token an Gerät binden
   - Schutz vor Token-Diebstahl

3. **Multi-Factor Authentication**
   - Zusätzliche Sicherheit
   - Optional aktivierbar

4. **Token-Management UI**
   - Benutzer sieht eigene aktive Tokens
   - Kann einzelne Tokens widerrufen
   - "Alle Geräte abmelden" Funktion

---

## 📞 Support

Bei Fragen oder Problemen:
- GitHub Issue erstellen
- Sicherheitslücken per E-Mail melden
- Dokumentation durchlesen

---

**Entwickelt für die Freiwillige Feuerwehr Willingshausen** 🚒

Made with ❤️ in Germany
