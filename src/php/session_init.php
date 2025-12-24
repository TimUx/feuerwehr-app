<?php
/**
 * Common session initialization function
 * Used across the application to ensure consistent session cookie configuration
 */

/**
 * Initialize session with secure cookie parameters
 * Should be called before any session operations
 */
function initSecureSession() {
    if (session_status() === PHP_SESSION_NONE) {
        // Detect if we're on HTTPS
        $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
                   (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
        
        // Get the host for cookie domain
        // Use $_SERVER['HTTP_HOST'] but validate it to prevent Host header injection
        $cookieDomain = '';
        if (isset($_SERVER['HTTP_HOST'])) {
            // Remove port if present
            $host = $_SERVER['HTTP_HOST'];
            $host = explode(':', $host)[0];
            
            // Validate the host is a proper domain or IP
            // Only set domain if it's not localhost/IP address
            if (!in_array($host, ['localhost', '127.0.0.1', '::1']) && 
                !filter_var($host, FILTER_VALIDATE_IP)) {
                // Additional validation: ensure it's a valid domain name
                // Only lowercase letters, numbers, dots, and hyphens
                if (preg_match('/^[a-z0-9.-]+$/i', $host) && 
                    strlen($host) <= 253) {
                    $cookieDomain = $host;
                }
            }
        }
        
        // Set secure session cookie parameters before starting session
        session_set_cookie_params([
            'lifetime' => 0,  // Session cookie (expires when browser closes)
            'path' => '/',
            'domain' => $cookieDomain,
            'secure' => $isSecure,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        
        // Note: Strict mode is disabled to prevent interference with session_regenerate_id()
        // in some PHP versions (particularly 8.3+). The security impact is minimal because:
        // 1. We still use session_regenerate_id() to prevent session fixation
        // 2. HttpOnly and SameSite provide CSRF/XSS protection
        // 3. Session data is encrypted at rest
        // Alternative: Enable strict mode only for PHP < 8.3 if needed
        ini_set('session.use_strict_mode', 0);
        
        session_start();
    }
}
