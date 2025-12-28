<?php
require_once __DIR__ . '/encryption.php';
require_once __DIR__ . '/session_init.php';

/**
 * Simplified Authentication System
 * Clean rebuild using proven PHP session patterns
 */

class Auth {
    private static $config;
    private static $dataDir;
    private static $initialized = false;

    public static function init() {
        // Initialize only once
        if (self::$initialized) {
            return;
        }
        
        // Start secure session FIRST (before loading config)
        initSecureSession();
        
        // Load config if not already loaded
        if (self::$config === null) {
            self::$config = require __DIR__ . '/../../config/config.php';
            self::$dataDir = self::$config['data_dir'];
            
            // Clear stat cache to ensure we get current filesystem state
            clearstatcache(true, self::$dataDir);
            
            // Ensure data directory exists and is a directory
            // Using is_dir() as primary check since it's more reliable for directories
            if (!is_dir(self::$dataDir)) {
                // Directory doesn't exist or is not a directory, try to create it
                if (!@mkdir(self::$dataDir, 0700, true)) {
                    // mkdir failed, but check again if directory now exists
                    // (could have been created by concurrent request, or might already exist)
                    clearstatcache(true, self::$dataDir);
                    
                    if (!is_dir(self::$dataDir)) {
                        // Directory truly doesn't exist and couldn't be created
                        error_log("Failed to create data directory: " . self::$dataDir . ". Please ensure the web server has write permissions to the parent directory.");
                        
                        // Redirect to diagnose.php for better error diagnostics
                        if (php_sapi_name() !== 'cli' && !headers_sent()) {
                            header('Location: diagnose.php?error=' . urlencode('data_dir_create_failed') . '&details=' . urlencode('Failed to create data directory. Check server error logs for details.'));
                            exit;
                        }
                        
                        die("Configuration Error: Unable to create data directory. Please contact your system administrator or check file permissions.<br><br><a href='diagnose.php'>Run System Diagnostics</a>");
                    }
                    // else: Directory exists now, continue normally
                }
            }
            
            // Verify it's writable with an actual write test
            // Note: is_writable() can return false negatives in some PHP-FPM configurations
            // so we perform an actual write test instead
            $testFile = self::$dataDir . '/.write_test_' . bin2hex(random_bytes(8));
            
            // Clear any previous errors
            error_clear_last();
            
            $writeResult = @file_put_contents($testFile, 'test');
            $writeTestSuccess = $writeResult !== false;
            
            if ($writeTestSuccess) {
                // Clean up test file
                @unlink($testFile);
            } else {
                // Write test failed - directory is not writable
                $lastError = error_get_last();
                $errorMsg = $lastError ? $lastError['message'] : 'Unknown error';
                error_log("Data directory exists but is not writable: " . self::$dataDir . ". Write test error: " . $errorMsg);
                
                // Redirect to diagnose.php for better error diagnostics
                if (php_sapi_name() !== 'cli' && !headers_sent()) {
                    header('Location: diagnose.php?error=' . urlencode('data_dir_not_writable') . '&details=' . urlencode('Data directory is not writable. Check server error logs for details.'));
                    exit;
                }
                
                die("Configuration Error: Data directory is not writable. Please contact your system administrator or check file permissions.<br><br><a href='diagnose.php'>Run System Diagnostics</a>");
            }
        }
        
        self::$initialized = true;
    }

    /**
     * Authenticate user with username and password
     */
    public static function login($username, $password, $rememberMe = false) {
        self::init();
        
        $users = self::loadUsers();
        
        // Check credentials
        foreach ($users as $user) {
            if ($user['username'] === $username && password_verify($password, $user['password'])) {
                // Set new session data FIRST
                $_SESSION['authenticated'] = true;
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['location_id'] = $user['location_id'] ?? null;
                $_SESSION['login_time'] = time();
                $_SESSION['last_activity'] = time();
                
                // Regenerate session ID (keep old session temporarily for safety)
                session_regenerate_id(false);
                
                // Handle "Remember Me" functionality
                if ($rememberMe) {
                    self::setRememberMeCookie($user['id']);
                }
                
                return true;
            }
        }
        
        return false;
    }

    /**
     * Logout user and destroy session
     */
    public static function logout() {
        self::init();
        
        // Clear remember me cookie if exists
        self::clearRememberMeCookie();
        
        // Clear all session variables
        $_SESSION = array();
        
        // Delete session cookie
        if (isset($_COOKIE[session_name()])) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 86400,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        
        // Destroy session
        session_destroy();
        
        return true;
    }

    /**
     * Check if user is authenticated
     */
    public static function isAuthenticated() {
        self::init();
        
        // Check if authenticated flag is set
        if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
            return false;
        }
        
        // Check if user_id exists
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        
        // Check session timeout (1 hour)
        $timeout = self::$config['session_lifetime'] ?? 3600;
        if (isset($_SESSION['last_activity'])) {
            $elapsed = time() - $_SESSION['last_activity'];
            if ($elapsed > $timeout) {
                self::logout();
                return false;
            }
        }
        
        // Update last activity time
        $_SESSION['last_activity'] = time();
        
        return true;
    }

    /**
     * Check if user has admin role
     */
    public static function isAdmin() {
        return self::isAuthenticated() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }

    /**
     * Check if user has operator role or higher
     */
    public static function isOperator() {
        return self::isAuthenticated() && isset($_SESSION['role']) && 
               in_array($_SESSION['role'], ['operator', 'admin']);
    }

    /**
     * Check if user is a global admin (admin without location restriction)
     * Global admins have full access to all settings including general and email settings
     */
    public static function isGlobalAdmin() {
        return self::isAdmin() && self::hasGlobalAccess();
    }

    /**
     * Require authentication
     */
    public static function requireAuth() {
        if (!self::isAuthenticated()) {
            http_response_code(401);
            header('Location: /index.php?page=login');
            exit;
        }
    }

    /**
     * Require admin role
     */
    public static function requireAdmin() {
        self::requireAuth();
        if (!self::isAdmin()) {
            http_response_code(403);
            die('Access denied. Admin privileges required.');
        }
    }

    /**
     * Require global admin role (admin without location restriction)
     * Used for sensitive settings like general settings and email configuration
     */
    public static function requireGlobalAdmin() {
        self::requireAuth();
        if (!self::isGlobalAdmin()) {
            http_response_code(403);
            die('Access denied. Global administrator privileges required.');
        }
    }

    /**
     * Require operator role or higher
     */
    public static function requireOperator() {
        self::requireAuth();
        if (!self::isOperator()) {
            http_response_code(403);
            die('Access denied. Operator privileges required.');
        }
    }

    /**
     * Get current user info
     */
    public static function getUser() {
        if (!self::isAuthenticated()) {
            return null;
        }

        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'role' => $_SESSION['role'],
            'location_id' => $_SESSION['location_id'] ?? null
        ];
    }

    /**
     * Check if user has global access (no location restriction)
     * This applies to all user types (admin, operator, regular users)
     * Returns true if the user is not assigned to a specific location
     */
    public static function hasGlobalAccess() {
        return self::isAuthenticated() && empty($_SESSION['location_id']);
    }

    /**
     * Get user's location ID (null if global)
     */
    public static function getUserLocationId() {
        return self::isAuthenticated() ? ($_SESSION['location_id'] ?? null) : null;
    }

    /**
     * Check if an admin user has location-based restrictions
     * Admins with a location_id set should only manage items for that location
     */
    public static function hasLocationRestriction() {
        return self::isAdmin() && !empty($_SESSION['location_id']);
    }

    /**
     * Check if the current admin has access to a resource based on location
     * Returns true if user has global access OR if the resource matches user's location
     */
    public static function canAccessLocation($resourceLocationId) {
        if (!self::isAdmin()) {
            return false;
        }
        
        $userLocationId = self::getUserLocationId();
        
        // Admins without location have global access
        if (empty($userLocationId)) {
            return true;
        }
        
        // Admins with location can only access their location's resources
        return $resourceLocationId === $userLocationId;
    }

    /**
     * Load users from encrypted storage
     */
    private static function loadUsers() {
        self::init();
        $usersFile = self::$dataDir . '/users.json';
        
        // Initialize with default admin if no users exist
        if (!file_exists($usersFile)) {
            $defaultAdmin = [
                'id' => uniqid('user_'),
                'username' => self::$config['default_admin']['username'],
                'password' => password_hash(self::$config['default_admin']['password'], PASSWORD_DEFAULT),
                'role' => 'admin',
                'created_at' => date('Y-m-d H:i:s')
            ];
            self::saveUsers([$defaultAdmin]);
            return [$defaultAdmin];
        }

        $encrypted = file_get_contents($usersFile);
        $decrypted = Encryption::decrypt($encrypted);
        return json_decode($decrypted, true) ?: [];
    }

    /**
     * Save users to encrypted storage
     */
    private static function saveUsers($users) {
        self::init();
        $usersFile = self::$dataDir . '/users.json';
        $json = json_encode($users, JSON_PRETTY_PRINT);
        $encrypted = Encryption::encrypt($json);
        file_put_contents($usersFile, $encrypted);
        chmod($usersFile, 0600);
    }

    /**
     * Create new user
     */
    public static function createUser($username, $password, $role = 'operator', $locationId = null, $email = null) {
        $users = self::loadUsers();
        
        // Check if username already exists
        foreach ($users as $user) {
            if ($user['username'] === $username) {
                return false;
            }
        }

        $newUser = [
            'id' => uniqid('user_'),
            'username' => $username,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'role' => $role,
            'location_id' => $locationId, // null means global access
            'email' => $email, // optional email for password reset
            'created_at' => date('Y-m-d H:i:s')
        ];

        $users[] = $newUser;
        self::saveUsers($users);
        return true;
    }

    /**
     * Update user
     */
    public static function updateUser($userId, $data) {
        $users = self::loadUsers();
        
        foreach ($users as &$user) {
            if ($user['id'] === $userId) {
                if (isset($data['username'])) {
                    $user['username'] = $data['username'];
                }
                if (isset($data['password']) && !empty($data['password'])) {
                    $user['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
                }
                if (isset($data['role'])) {
                    $user['role'] = $data['role'];
                }
                if (isset($data['location_id'])) {
                    $user['location_id'] = $data['location_id'];
                }
                if (isset($data['email'])) {
                    $user['email'] = $data['email'];
                }
                $user['updated_at'] = date('Y-m-d H:i:s');
                
                self::saveUsers($users);
                return true;
            }
        }
        
        return false;
    }

    /**
     * Delete user
     */
    public static function deleteUser($userId) {
        $users = self::loadUsers();
        $users = array_filter($users, function($user) use ($userId) {
            return $user['id'] !== $userId;
        });
        
        self::saveUsers(array_values($users));
        return true;
    }

    /**
     * List all users
     */
    public static function listUsers() {
        $users = self::loadUsers();
        
        // Remove password from response
        return array_map(function($user) {
            unset($user['password']);
            return $user;
        }, $users);
    }

    /**
     * Set remember me cookie with secure token
     */
    private static function setRememberMeCookie($userId) {
        self::init();
        
        // Generate a secure random token
        $token = bin2hex(random_bytes(32));
        $expiry = time() + (30 * 24 * 60 * 60); // 30 days
        
        // Store token in encrypted file
        $rememberTokensFile = self::$dataDir . '/remember_tokens.json';
        $tokens = [];
        
        if (file_exists($rememberTokensFile)) {
            $encrypted = file_get_contents($rememberTokensFile);
            $decrypted = Encryption::decrypt($encrypted);
            $tokens = json_decode($decrypted, true) ?: [];
        }
        
        // Clean up expired tokens
        $tokens = array_filter($tokens, function($item) {
            return $item['expiry'] > time();
        });
        
        // Add new token
        $tokens[] = [
            'token' => password_hash($token, PASSWORD_DEFAULT),
            'user_id' => $userId,
            'expiry' => $expiry,
            'created' => time()
        ];
        
        // Save tokens
        $json = json_encode($tokens, JSON_PRETTY_PRINT);
        $encrypted = Encryption::encrypt($json);
        file_put_contents($rememberTokensFile, $encrypted);
        chmod($rememberTokensFile, 0600);
        
        // Determine if HTTPS is enabled
        $isSecure = self::isHttps();
        
        // Set cookie with plain token
        setcookie(
            'remember_me',
            $token,
            $expiry,
            '/',
            '',
            $isSecure, // secure flag (only over HTTPS)
            true       // httponly flag
        );
    }

    /**
     * Clear remember me cookie and token
     */
    private static function clearRememberMeCookie() {
        if (isset($_COOKIE['remember_me'])) {
            // Determine if HTTPS is enabled
            $isSecure = self::isHttps();
            
            // Delete cookie
            setcookie('remember_me', '', time() - 3600, '/', '', $isSecure, true);
            
            // Remove token from storage
            self::init();
            $rememberTokensFile = self::$dataDir . '/remember_tokens.json';
            
            if (file_exists($rememberTokensFile)) {
                $encrypted = file_get_contents($rememberTokensFile);
                $decrypted = Encryption::decrypt($encrypted);
                $tokens = json_decode($decrypted, true) ?: [];
                
                // Remove tokens that match this cookie
                $token = $_COOKIE['remember_me'];
                $tokens = array_filter($tokens, function($item) use ($token) {
                    return !password_verify($token, $item['token']);
                });
                
                // Save remaining tokens
                $json = json_encode(array_values($tokens), JSON_PRETTY_PRINT);
                $encrypted = Encryption::encrypt($json);
                file_put_contents($rememberTokensFile, $encrypted);
            }
        }
    }

    /**
     * Check if connection is over HTTPS
     */
    private static function isHttps() {
        // Check standard HTTPS variable
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            return true;
        }
        
        // Check if using standard HTTPS port
        if (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) {
            return true;
        }
        
        // Check for proxy/load balancer forwarded protocol
        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            return true;
        }
        
        // Check alternative forwarded SSL header
        if (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on') {
            return true;
        }
        
        // Check for CloudFlare
        if (!empty($_SERVER['HTTP_CF_VISITOR'])) {
            $visitor = json_decode($_SERVER['HTTP_CF_VISITOR'], true);
            if (isset($visitor['scheme']) && $visitor['scheme'] === 'https') {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Try to auto-login using remember me cookie
     */
    public static function tryAutoLogin() {
        self::init();
        
        // Check if remember me cookie exists
        if (!isset($_COOKIE['remember_me'])) {
            return false;
        }
        
        $token = $_COOKIE['remember_me'];
        $rememberTokensFile = self::$dataDir . '/remember_tokens.json';
        
        if (!file_exists($rememberTokensFile)) {
            return false;
        }
        
        // Load and decrypt tokens
        $encrypted = file_get_contents($rememberTokensFile);
        $decrypted = Encryption::decrypt($encrypted);
        $tokens = json_decode($decrypted, true) ?: [];
        
        // Find matching token
        foreach ($tokens as $storedToken) {
            if (password_verify($token, $storedToken['token'])) {
                // Check if token is still valid
                if ($storedToken['expiry'] < time()) {
                    // Token expired - remove it
                    self::clearRememberMeCookie();
                    return false;
                }
                
                // Load user
                $users = self::loadUsers();
                foreach ($users as $user) {
                    if ($user['id'] === $storedToken['user_id']) {
                        // Set session data
                        $_SESSION['authenticated'] = true;
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['role'] = $user['role'];
                        $_SESSION['location_id'] = $user['location_id'] ?? null;
                        $_SESSION['login_time'] = time();
                        $_SESSION['last_activity'] = time();
                        $_SESSION['auto_login'] = true; // Mark as auto-login
                        
                        // Regenerate session ID
                        session_regenerate_id(false);
                        
                        return true;
                    }
                }
            }
        }
        
        return false;
    }

    /**
     * Generate password reset token for user
     */
    public static function generatePasswordResetToken($username) {
        self::init();
        $users = self::loadUsers();
        
        // Find user by username
        $user = null;
        foreach ($users as $u) {
            if ($u['username'] === $username) {
                $user = $u;
                break;
            }
        }
        
        if (!$user) {
            return false;
        }
        
        // Check if user has an email
        if (empty($user['email'])) {
            return ['error' => 'no_email'];
        }
        
        // Generate secure token
        $token = bin2hex(random_bytes(32));
        $expiry = time() + (60 * 60); // 1 hour expiry
        
        // Store token
        $resetTokensFile = self::$dataDir . '/password_reset_tokens.json';
        $tokens = [];
        
        if (file_exists($resetTokensFile)) {
            $encrypted = file_get_contents($resetTokensFile);
            $decrypted = Encryption::decrypt($encrypted);
            $tokens = json_decode($decrypted, true) ?: [];
        }
        
        // Clean up expired tokens
        $tokens = array_filter($tokens, function($item) {
            return $item['expiry'] > time();
        });
        
        // Add new token
        $tokens[] = [
            'token' => password_hash($token, PASSWORD_DEFAULT),
            'user_id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'expiry' => $expiry,
            'created' => time()
        ];
        
        // Save tokens
        $json = json_encode($tokens, JSON_PRETTY_PRINT);
        $encrypted = Encryption::encrypt($json);
        file_put_contents($resetTokensFile, $encrypted);
        chmod($resetTokensFile, 0600);
        
        return [
            'token' => $token,
            'email' => $user['email'],
            'username' => $user['username']
        ];
    }

    /**
     * Verify password reset token
     */
    public static function verifyPasswordResetToken($token) {
        self::init();
        $resetTokensFile = self::$dataDir . '/password_reset_tokens.json';
        
        if (!file_exists($resetTokensFile)) {
            return false;
        }
        
        $encrypted = file_get_contents($resetTokensFile);
        $decrypted = Encryption::decrypt($encrypted);
        $tokens = json_decode($decrypted, true) ?: [];
        
        // Find matching token
        foreach ($tokens as $storedToken) {
            if (password_verify($token, $storedToken['token'])) {
                // Check if token is still valid
                if ($storedToken['expiry'] < time()) {
                    return false;
                }
                
                return [
                    'user_id' => $storedToken['user_id'],
                    'username' => $storedToken['username']
                ];
            }
        }
        
        return false;
    }

    /**
     * Reset password with token
     */
    public static function resetPassword($token, $newPassword) {
        self::init();
        
        // Verify token
        $tokenData = self::verifyPasswordResetToken($token);
        if (!$tokenData) {
            return false;
        }
        
        // Update password
        $success = self::updateUser($tokenData['user_id'], [
            'password' => $newPassword
        ]);
        
        if ($success) {
            // Remove used token
            self::removePasswordResetToken($token);
        }
        
        return $success;
    }

    /**
     * Remove password reset token after use
     */
    private static function removePasswordResetToken($token) {
        self::init();
        $resetTokensFile = self::$dataDir . '/password_reset_tokens.json';
        
        if (!file_exists($resetTokensFile)) {
            return;
        }
        
        $encrypted = file_get_contents($resetTokensFile);
        $decrypted = Encryption::decrypt($encrypted);
        $tokens = json_decode($decrypted, true) ?: [];
        
        // Remove matching token
        $tokens = array_filter($tokens, function($storedToken) use ($token) {
            return !password_verify($token, $storedToken['token']);
        });
        
        // Save remaining tokens
        $json = json_encode(array_values($tokens), JSON_PRETTY_PRINT);
        $encrypted = Encryption::encrypt($json);
        file_put_contents($resetTokensFile, $encrypted);
    }

    /**
     * Get user by username (for password reset)
     * Returns user data without password for security
     * 
     * @param string $username Username to search for
     * @return array|null User data without password, or null if not found
     */
    public static function getUserByUsername($username) {
        $users = self::loadUsers();
        
        foreach ($users as $user) {
            if ($user['username'] === $username) {
                unset($user['password']);
                return $user;
            }
        }
        
        return null;
    }
}
