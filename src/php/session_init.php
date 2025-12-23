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
        
        // Set secure session cookie parameters before starting session
        session_set_cookie_params([
            'lifetime' => 0,  // Session cookie (expires when browser closes)
            'path' => '/',
            'domain' => '',
            'secure' => $isSecure,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        
        // Enable strict mode for additional security
        ini_set('session.use_strict_mode', 1);
        
        session_start();
    }
}
