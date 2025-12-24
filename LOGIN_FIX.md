# Fix für Login-Session-Problem

## Problem

Die Anmeldung funktionierte nicht korrekt - Benutzer wurden nach dem Login-Versuch wieder zurück zum Login-Prompt geleitet. Die Session-Cookies wurden nicht korrekt gesetzt, wodurch die Authentifizierung nicht persistiert wurde.

## Ursache

Das Problem lag darin, dass `ini_set()` für Session-Cookie-Parameter in neueren PHP-Versionen (insbesondere PHP 8.5) nicht zuverlässig funktioniert. Die diagnostischen Tests zeigten:

```
Session cookie_httponly: 0 (deaktiviert)
```

Obwohl im Code `ini_set('session.cookie_httponly', 1)` verwendet wurde.

## Lösung

Die Lösung besteht darin, die korrekte PHP-Funktion `session_set_cookie_params()` zu verwenden, die BEVOR `session_start()` aufgerufen wird, die Cookie-Parameter setzen muss.

### Änderungen

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

### Vor der Änderung (nicht funktionierend):
```php
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', $isSecure ? 1 : 0);
session_start();
```

### Nach der Änderung (funktionierend):
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

## Kompatibilität

Diese Lösung funktioniert mit:
- PHP 7.4+
- PHP 8.0, 8.1, 8.2, 8.3
- PHP 8.5 (neueste Version)

Die Verwendung von `session_set_cookie_params()` mit Array-Syntax ist seit PHP 7.3 verfügbar und ist die empfohlene Methode für alle modernen PHP-Versionen.
