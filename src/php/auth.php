<?php
/**
 * Authentication and Session Management
 */

class Auth {
    private static $config;
    private static $dataDir;

    public static function init() {
        self::$config = require __DIR__ . '/../../config/config.php';
        self::$dataDir = self::$config['data_dir'];
        
        // Ensure data directory exists
        if (!file_exists(self::$dataDir)) {
            mkdir(self::$dataDir, 0700, true);
        }

        // Start session with secure settings
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
            ini_set('session.use_strict_mode', 1);
            session_start();
        }
    }

    /**
     * Login user
     */
    public static function login($username, $password) {
        self::init();
        
        $users = self::loadUsers();
        
        // Check credentials
        foreach ($users as $user) {
            if ($user['username'] === $username && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['last_activity'] = time();
                return true;
            }
        }
        
        return false;
    }

    /**
     * Logout user
     */
    public static function logout() {
        session_destroy();
        return true;
    }

    /**
     * Check if user is authenticated
     */
    public static function isAuthenticated() {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }

        // Check session timeout
        self::init();
        $timeout = self::$config['session_lifetime'];
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
            self::logout();
            return false;
        }

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
     * Get current user info
     */
    public static function getUser() {
        if (!self::isAuthenticated()) {
            return null;
        }

        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'role' => $_SESSION['role']
        ];
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
    public static function createUser($username, $password, $role = 'operator') {
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
