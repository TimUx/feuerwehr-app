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
        // Use $_SERVER['HTTP_HOST'] but fallback to empty string for local development
        $cookieDomain = '';
        if (isset($_SERVER['HTTP_HOST'])) {
            // Remove port if present
            $host = $_SERVER['HTTP_HOST'];
            $host = explode(':', $host)[0];
            // Only set domain if it's not localhost/IP address
            if (!in_array($host, ['localhost', '127.0.0.1', '::1']) && 
                !filter_var($host, FILTER_VALIDATE_IP)) {
                $cookieDomain = $host;
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
        
        // Disable strict mode temporarily to avoid issues with session regeneration
        // Strict mode can interfere with session_regenerate_id() in some PHP versions
        ini_set('session.use_strict_mode', 0);
        
        session_start();
    }
}
