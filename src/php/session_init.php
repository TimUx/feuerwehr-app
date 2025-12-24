<?php
/**
 * Simplified Session Management
 * Clean implementation using proven PHP session patterns
 */

/**
 * Initialize a secure PHP session
 */
function initSecureSession() {
    // Configure session save path BEFORE checking status
    $sessionPath = '/tmp/php_sessions';
    if (!file_exists($sessionPath)) {
        mkdir($sessionPath, 0700, true);
    }
    
    // Only start session once
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    
    // Set session save path before any other session operations
    session_save_path($sessionPath);
    
    // Detect HTTPS
    $isSecure = (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
        (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
        (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
    );
    
    // Set session name before any other session operations
    session_name('FWAPP_SESSION');
    
    // Configure session parameters BEFORE starting the session
    session_set_cookie_params([
        'lifetime' => 0,  // Session cookie (expires when browser closes)
        'path' => '/',
        'domain' => '',
        'secure' => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    
    // Set additional session settings before session_start()
    ini_set('session.use_cookies', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.gc_maxlifetime', '3600'); // 1 hour timeout
    
    // Start the session
    session_start();
}
