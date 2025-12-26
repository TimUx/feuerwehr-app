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
            
            // Ensure data directory exists
            if (!file_exists(self::$dataDir)) {
                mkdir(self::$dataDir, 0700, true);
            }
        }
        
        self::$initialized = true;
    }

    /**
     * Authenticate user with username and password
     */
    public static function login($username, $password) {
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
     */
    public static function hasGlobalAccess() {
        return self::isAuthenticated() && (!isset($_SESSION['location_id']) || $_SESSION['location_id'] === null);
    }

    /**
     * Get user's location ID (null if global)
     */
    public static function getUserLocationId() {
        return self::isAuthenticated() ? ($_SESSION['location_id'] ?? null) : null;
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
    public static function createUser($username, $password, $role = 'operator', $locationId = null) {
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
}
