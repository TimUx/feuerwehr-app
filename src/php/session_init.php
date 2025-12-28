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
    
    // Clear stat cache to ensure we get current filesystem state
    clearstatcache(true, $sessionPath);
    
    if (!file_exists($sessionPath)) {
        // Use 0755 permissions for better compatibility with shared hosting environments
        if (!@mkdir($sessionPath, 0755, true)) {
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
    // Use secure cookies when on HTTPS to ensure browser sends the cookie
    session_set_cookie_params([
        'lifetime' => 0,  // Session cookie (expires when browser closes)
        'path' => '/',
        'domain' => '',
        'secure' => $isSecure,  // Set secure flag on HTTPS to ensure cookies work properly
        'httponly' => true,
        'samesite' => $isSecure ? 'Lax' : ''  // Use Lax on HTTPS for better security while maintaining functionality
    ]);
    
    // Set additional session settings before session_start()
    ini_set('session.use_cookies', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.gc_maxlifetime', '3600'); // 1 hour timeout
    
    // Start the session
    session_start();
}
