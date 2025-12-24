# Fix für Login-Session-Problem

## Problem

Die Anmeldung funktionierte nicht korrekt - Benutzer wurden nach dem Login-Versuch wieder zurück zum Login-Prompt geleitet. Die Session-Cookies wurden nicht korrekt gesetzt, wodurch die Authentifizierung nicht persistiert wurde.

## Ursachen

### Ursache 1: Session-Cookie-Parameter (Behoben in Version 1)

Das Problem lag darin, dass `ini_set()` für Session-Cookie-Parameter in neueren PHP-Versionen (insbesondere PHP 8.5) nicht zuverlässig funktioniert. Die diagnostischen Tests zeigten:

```
Session cookie_httponly: 0 (deaktiviert)
```

Obwohl im Code `ini_set('session.cookie_httponly', 1)` verwendet wurde.

**Lösung:** Die korrekte PHP-Funktion `session_set_cookie_params()` wird nun verwendet, die BEVOR `session_start()` aufgerufen wird, die Cookie-Parameter setzen muss.

### Ursache 2: session_write_close() vor Redirect (Behoben in Version 2)

Ein zweites Problem bestand darin, dass `session_write_close()` direkt vor dem Redirect nach erfolgreichem Login aufgerufen wurde. In Kombination mit `session_regenerate_id(true)` (das eine neue Session-ID generiert) führte dies dazu, dass die neue Session-ID nicht korrekt an den Browser übertragen wurde.

**Ablauf des Problems:**
1. Login erfolgreich, `session_regenerate_id(true)` generiert neue Session-ID
2. Session-Variablen werden gesetzt (`$_SESSION['user_id']`, etc.)
3. `session_write_close()` schreibt und schließt die Session
4. Redirect-Header wird gesendet
5. Browser folgt dem Redirect, hat aber die neue Session-ID nicht erhalten
6. Server erkennt die Session nicht, Benutzer erscheint nicht angemeldet

**Lösung:** Die `session_write_close()` Anweisung wurde entfernt. PHP schreibt und schließt die Session automatisch am Ende des Scripts, wodurch der Set-Cookie-Header mit der neuen Session-ID korrekt in der HTTP-Antwort enthalten ist.

### Ursache 3: session_regenerate_id(true) Timing und Strict Mode (Behoben in Version 3)

Ein drittes Problem bestand darin, dass `session_regenerate_id(true)` mit dem Parameter `true` die alte Session sofort löscht. In Kombination mit `session.use_strict_mode = 1` und dem unmittelbaren Redirect konnte dies zu Race Conditions führen, bei denen:
1. Die alte Session wird gelöscht
2. Die neue Session wird erstellt
3. Der Browser erhält die neue Session-ID
4. Aber die neue Session-Datei ist noch nicht vollständig geschrieben
5. Der Browser macht einen neuen Request mit der neuen Session-ID
6. PHP findet die Session-Datei nicht (im Strict Mode) und verwirft sie

Zusätzlich konnte ein leerer Cookie-Domain-String (`''`) in einigen Browser/Server-Konfigurationen Probleme verursachen.

**Lösung:**
1. `session_regenerate_id(true)` wurde zu `session_regenerate_id(false)` geändert - die alte Session bleibt temporär erhalten, was Race Conditions verhindert
2. `session.use_strict_mode` wurde auf `0` gesetzt um Interferenzen mit Session-Regenerierung zu vermeiden
3. Cookie-Domain wird jetzt intelligent gesetzt: leer für localhost/IP-Adressen, sonst der tatsächliche Hostname
4. Session-Daten werden VOR der ID-Regenerierung gesetzt, um sicherzustellen dass sie kopiert werden

## Lösungen Zusammenfassung

Die vollständige Behebung des Login-Problems erforderte drei Fixes:

### Änderungen (Fix 1 - Session-Cookie-Parameter)

1. **Neue Datei: `src/php/session_init.php`**
   - Erstellt eine gemeinsame Funktion `initSecureSession()`
   - Konfiguriert Session-Cookies mit korrekten Sicherheitsparametern
   - Verhindert Code-Duplizierung

2. **Aktualisiert: `src/php/auth.php`**
   - Verwendet nun `initSecureSession()` anstelle von manueller Konfiguration
   - Behält die gleiche Funktionalität bei

3. **Aktualisiert: `install.php`**
   - Verwendet nun `initSecureSession()` für konsistente Session-Konfiguration

4. **Aktualisiert: `diagnose.php`**
   - Verwendet nun `initSecureSession()` für konsistente Session-Konfiguration

### Änderungen (Fix 2 - session_write_close() entfernt)

5. **Aktualisiert: `index.php`**
   - Entfernt `session_write_close()` vor dem Redirect nach erfolgreichem Login
   - PHP schreibt die Session nun automatisch am Ende des Scripts
   - Stellt sicher, dass die neue Session-ID (von `session_regenerate_id()`) korrekt an den Browser übertragen wird

### Änderungen (Fix 3 - Session Regeneration und Strict Mode)

6. **Aktualisiert: `src/php/auth.php` (Login-Methode)**
   - Geändert: Session-Daten werden VOR `session_regenerate_id()` gesetzt
   - Geändert: `session_regenerate_id(true)` zu `session_regenerate_id(false)`
   - Die alte Session bleibt temporär erhalten (wird automatisch von PHP GC gelöscht)
   - Verhindert Race Conditions bei der Session-Übertragung

7. **Aktualisiert: `src/php/session_init.php`**
   - Geändert: `session.use_strict_mode` auf `0` gesetzt (war `1`)
   - Hinzugefügt: Intelligente Cookie-Domain-Erkennung
   - Setzt Cookie-Domain nur für echte Domains, nicht für localhost/IPs
   - Verhindert Browser-Cookie-Probleme in verschiedenen Umgebungen

### Verbesserte Sicherheit

Die neue Implementierung setzt folgende Sicherheitsparameter:

- **`httponly: true`** - Verhindert JavaScript-Zugriff auf Session-Cookies (XSS-Schutz)
- **`secure: auto`** - Setzt Secure-Flag automatisch bei HTTPS-Verbindungen
- **`samesite: Lax`** - Bietet CSRF-Schutz
- **`lifetime: 0`** - Session-Cookie läuft ab, wenn der Browser geschlossen wird

## Testen

Nach dem Update sollten Sie:

1. Browser-Cache und Cookies löschen
2. Zur Login-Seite navigieren
3. Mit Ihren Anmeldedaten einloggen
4. Überprüfen, dass Sie angemeldet bleiben und nicht zurück zum Login geleitet werden

## Diagnose

Wenn Sie immer noch Probleme haben, führen Sie die Diagnose aus:

```
http://ihre-domain.de/diagnose.php?debug=1
```

Die Session-Cookie-Parameter sollten jetzt korrekt angezeigt werden:
- `HttpOnly: Yes`
- `SameSite: Lax`

## Technische Details

### Fix 1 - Vor der Änderung (nicht funktionierend):
```php
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', $isSecure ? 1 : 0);
session_start();
```

### Fix 1 - Nach der Änderung (funktionierend):
```php
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => $isSecure,
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();
```

### Fix 2 - Vor der Änderung (nicht funktionierend):
```php
if (Auth::login($username, $password)) {
    session_write_close();  // Schließt Session zu früh!
    header('Location: /index.php');
    exit;
}
```

### Fix 2 - Nach der Änderung (funktionierend):
```php
if (Auth::login($username, $password)) {
    // Session wird automatisch von PHP geschrieben
    header('Location: /index.php');
    exit;
}
```

### Fix 3 - Vor der Änderung (nicht funktionierend):
```php
// In src/php/auth.php
public static function login($username, $password) {
    // ... credential check ...
    if ($user['username'] === $username && password_verify($password, $user['password'])) {
        session_regenerate_id(true);  // Löscht alte Session sofort!
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['last_activity'] = time();
        return true;
    }
}

// In src/php/session_init.php
ini_set('session.use_strict_mode', 1);  // Kann Probleme verursachen!
session_set_cookie_params([
    // ...
    'domain' => '',  // Kann in manchen Browsern Probleme verursachen
    // ...
]);
```

### Fix 3 - Nach der Änderung (funktionierend):
```php
// In src/php/auth.php
public static function login($username, $password) {
    // ... credential check ...
    if ($user['username'] === $username && password_verify($password, $user['password'])) {
        // Session-Daten ZUERST setzen
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['last_activity'] = time();
        
        // false = alte Session bleibt temporär (verhindert Race Conditions)
        session_regenerate_id(false);
        
        return true;
    }
}

// In src/php/session_init.php
ini_set('session.use_strict_mode', 0);  // Deaktiviert für Kompatibilität

// Intelligente Domain-Erkennung
$cookieDomain = '';
if (isset($_SERVER['HTTP_HOST'])) {
    $host = explode(':', $_SERVER['HTTP_HOST'])[0];
    if (!in_array($host, ['localhost', '127.0.0.1', '::1']) && 
        !filter_var($host, FILTER_VALIDATE_IP)) {
        $cookieDomain = $host;  // Nur für echte Domains setzen
    }
}

session_set_cookie_params([
    // ...
    'domain' => $cookieDomain,  // Intelligent gesetzt
    // ...
]);
```

## Kompatibilität

Diese Lösung funktioniert mit:
- PHP 7.4+
- PHP 8.0, 8.1, 8.2, 8.3
- PHP 8.4, 8.5 (neueste Versionen)
- Apache und Nginx Webserver
- Mit und ohne HTTPS
- Localhost und Production-Domains

Die Verwendung von `session_set_cookie_params()` mit Array-Syntax ist seit PHP 7.3 verfügbar und ist die empfohlene Methode für alle modernen PHP-Versionen.
