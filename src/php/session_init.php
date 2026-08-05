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
    // Map page needs Leaflet CDN, OSM/Topo/Esri tiles, Nominatim, OSRM, and marker assets.
    // Geolocation is allowed for same-origin only (map "locate me").
    $csp = implode('; ', [
        "default-src 'self'",
        "script-src 'self' 'unsafe-inline' https://unpkg.com",
        "style-src 'self' 'unsafe-inline' https://unpkg.com",
        "img-src 'self' data: blob: https://*.tile.openstreetmap.org https://*.tile.opentopomap.org https://server.arcgisonline.com https://raw.githubusercontent.com https://cdnjs.cloudflare.com",
        "font-src 'self' data:",
        "connect-src 'self' https://nominatim.openstreetmap.org https://router.project-osrm.org",
        "worker-src 'self' blob:",
        "frame-ancestors 'none'",
    ]);
    header('Content-Security-Policy: ' . $csp);
    header('Permissions-Policy: geolocation=(self), microphone=(), camera=()');
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
