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
        if (!@mkdir($sessionPath, 0700, true)) {
            error_log("Failed to create session directory: " . $sessionPath . ". Please ensure the web server has write permissions to /tmp.");
            die("Configuration Error: Unable to create session directory. Please contact your system administrator or check file permissions.");
        }
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
    // NOTE: For production environments, consider:
    // - Setting 'secure' => true when using HTTPS
    // - Using 'samesite' => 'Lax' or 'Strict' with HTTPS
    // - Implementing CSRF tokens in forms
    session_set_cookie_params([
        'lifetime' => 0,  // Session cookie (expires when browser closes)
        'path' => '/',
        'domain' => '',
        'secure' => false,  // Allow both HTTP and HTTPS (set to true in production with HTTPS)
        'httponly' => true,
        'samesite' => ''  // No SameSite restriction for maximum compatibility (consider 'Lax' in production)
    ]);
    
    // Set additional session settings before session_start()
    ini_set('session.use_cookies', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.gc_maxlifetime', '3600'); // 1 hour timeout
    
    // Start the session
    session_start();
}
