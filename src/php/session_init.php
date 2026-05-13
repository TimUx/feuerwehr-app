<?php
/**
 * Simplified Session Management
 * Clean implementation using proven PHP session patterns
 */

/**
 * Send HTTP security headers.
 * Call this at the very top of every page / API endpoint (before any output).
 */
function sendSecurityHeaders(): void {
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self' data:; connect-src 'self'");
    header("Permissions-Policy: geolocation=(), microphone=(), camera=()");
}

/**
 * Initialize a secure PHP session
 */
function initSecureSession() {
    // Skip session initialization in CLI context (command line scripts)
    if (php_sapi_name() === 'cli') {
        return;
    }
    
    // Only start session once
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    
    // Use PHP's default session save path from php.ini to avoid permission issues
    // on shared hosting and various server configurations
    
    // Detect HTTPS
    $isSecure = (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
        (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
        (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
    );
    
    // Set session name before any other session operations
    session_name('FWAPP_SESSION');
    
    // Configure session parameters BEFORE starting the session
    // Use secure cookies when on HTTPS to ensure browser sends the cookie
    session_set_cookie_params([
        'lifetime' => 0,  // Session cookie (expires when browser closes)
        'path' => '/',
        'domain' => '',
        'secure' => $isSecure,  // Set secure flag on HTTPS to ensure cookies work properly
        'httponly' => true,
        'samesite' => 'Lax'  // Use Lax for better security while maintaining functionality
    ]);
    
    // Set additional session settings before session_start()
    ini_set('session.use_cookies', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.gc_maxlifetime', '3600'); // 1 hour timeout
    
    // Start the session
    session_start();
}
