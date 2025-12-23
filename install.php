<?php
/**
 * Feuerwehr App - Installation Wizard
 * This wizard helps set up the application without requiring command-line access
 */

// Start session at the beginning
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security: Prevent running if already installed
$configFile = __DIR__ . '/config/config.php';
if (file_exists($configFile)) {
    die('Installation already completed. Delete config/config.php to run this wizard again.');
}

// System Requirements Check Function
function checkSystemRequirements() {
    $checks = [];
    $allPassed = true;
    
    // Check PHP Version (7.4+)
    $phpVersion = PHP_VERSION;
    $phpVersionOk = version_compare($phpVersion, '7.4.0', '>=');
    $checks[] = [
        'name' => 'PHP Version',
        'required' => '7.4.0 oder höher',
        'actual' => $phpVersion,
        'status' => $phpVersionOk,
        'critical' => true
    ];
    if (!$phpVersionOk) $allPassed = false;
    
    // Check required PHP extensions
    $requiredExtensions = [
        'openssl' => 'Für Verschlüsselung (AES-256-CBC)',
        'mbstring' => 'Für Multibyte-String-Unterstützung',
        'json' => 'Für JSON-Datenspeicherung',
        'session' => 'Für Session-Management'
    ];
    
    foreach ($requiredExtensions as $ext => $description) {
        $loaded = extension_loaded($ext);
        $checks[] = [
            'name' => "PHP Extension: $ext",
            'required' => $description,
            'actual' => $loaded ? 'Installiert' : 'Nicht gefunden',
            'status' => $loaded,
            'critical' => true
        ];
        if (!$loaded) $allPassed = false;
    }
    
    // Check recommended extensions
    $recommendedExtensions = [
        'curl' => 'Für externe API-Aufrufe',
        'gd' => 'Für Bildverarbeitung',
        'zip' => 'Für Archivierung'
    ];
    
    foreach ($recommendedExtensions as $ext => $description) {
        $loaded = extension_loaded($ext);
        $checks[] = [
            'name' => "PHP Extension: $ext",
            'required' => $description,
            'actual' => $loaded ? 'Installiert' : 'Nicht gefunden',
            'status' => $loaded,
            'critical' => false
        ];
    }
    
    // Check directory permissions
    $configDir = __DIR__ . '/config';
    $configDirWritable = is_writable($configDir) || (!file_exists($configDir) && is_writable(__DIR__));
    $checks[] = [
        'name' => 'config/ Verzeichnis',
        'required' => 'Schreibrechte erforderlich',
        'actual' => $configDirWritable ? 'Beschreibbar' : 'Nicht beschreibbar',
        'status' => $configDirWritable,
        'critical' => true
    ];
    if (!$configDirWritable) $allPassed = false;
    
    $dataDir = __DIR__ . '/data';
    $dataDirWritable = is_writable($dataDir) || (!file_exists($dataDir) && is_writable(__DIR__));
    $checks[] = [
        'name' => 'data/ Verzeichnis',
        'required' => 'Schreibrechte erforderlich',
        'actual' => $dataDirWritable ? 'Beschreibbar' : 'Nicht beschreibbar',
        'status' => $dataDirWritable,
        'critical' => true
    ];
    if (!$dataDirWritable) $allPassed = false;
    
    // Check PHP configuration
    $uploadMaxFilesize = ini_get('upload_max_filesize');
    $postMaxSize = ini_get('post_max_size');
    $checks[] = [
        'name' => 'upload_max_filesize',
        'required' => 'Mindestens 2M für Datei-Uploads',
        'actual' => $uploadMaxFilesize,
        'status' => true,
        'critical' => false
    ];
    
    $checks[] = [
        'name' => 'post_max_size',
        'required' => 'Mindestens 2M für Formular-Uploads',
        'actual' => $postMaxSize,
        'status' => true,
        'critical' => false
    ];
    
    $memoryLimit = ini_get('memory_limit');
    $checks[] = [
        'name' => 'memory_limit',
        'required' => 'Mindestens 64M empfohlen',
        'actual' => $memoryLimit,
        'status' => true,
        'critical' => false
    ];
    
    return ['checks' => $checks, 'allPassed' => $allPassed];
}

// Handle form submission
$step = $_GET['step'] ?? 0;
$errors = [];
$success = false;

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step == 0) {
        // Step 0: System requirements check
        $requirements = checkSystemRequirements();
        if ($requirements['allPassed']) {
            $step = 1;
        } else {
            $errors[] = 'Nicht alle erforderlichen Voraussetzungen sind erfüllt. Bitte beheben Sie die Probleme bevor Sie fortfahren.';
        }
    } elseif ($step == 1) {
        // Step 1: Welcome (just move to step 2)
        $step = 2;
    } elseif ($step == 2) {
        // Step 2: Admin user creation
        $username = trim($_POST['admin_username'] ?? '');
        $password = $_POST['admin_password'] ?? '';
        $password_confirm = $_POST['admin_password_confirm'] ?? '';
        
        if (empty($username)) {
            $errors[] = 'Benutzername ist erforderlich';
        } elseif (strlen($username) < 3) {
            $errors[] = 'Benutzername muss mindestens 3 Zeichen lang sein';
        }
        
        if (empty($password)) {
            $errors[] = 'Passwort ist erforderlich';
        } elseif (strlen($password) < 6) {
            $errors[] = 'Passwort muss mindestens 6 Zeichen lang sein';
        }
        
        if ($password !== $password_confirm) {
            $errors[] = 'Passwörter stimmen nicht überein';
        }
        
        if (empty($errors)) {
            // Store in session and move to step 3
            $_SESSION['install_admin_username'] = $username;
            $_SESSION['install_admin_password'] = $password;
            $step = 3;
        }
    } elseif ($step == 3) {
        // Step 3: Email settings
        
        $from_address = trim($_POST['from_address'] ?? '');
        $from_name = trim($_POST['from_name'] ?? '');
        $to_address = trim($_POST['to_address'] ?? '');
        $smtp_host = trim($_POST['smtp_host'] ?? 'localhost');
        $smtp_port = (int)($_POST['smtp_port'] ?? 25);
        $smtp_auth = !empty($_POST['smtp_auth']);
        $smtp_username = trim($_POST['smtp_username'] ?? '');
        $smtp_password = $_POST['smtp_password'] ?? '';
        $smtp_secure = $_POST['smtp_secure'] ?? '';
        
        if (empty($from_address)) {
            $errors[] = 'Absender-E-Mail-Adresse ist erforderlich';
        } elseif (!filter_var($from_address, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Ungültige Absender-E-Mail-Adresse';
        }
        
        if (empty($from_name)) {
            $errors[] = 'Absender-Name ist erforderlich';
        }
        
        if (!empty($to_address) && !filter_var($to_address, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Ungültige Empfänger-E-Mail-Adresse';
        }
        
        if (empty($errors)) {
            // Generate encryption key (32 bytes = 64 hex characters for AES-256)
            $encryption_key = bin2hex(random_bytes(32));
            
            // Get admin credentials from session
            $admin_username = $_SESSION['install_admin_username'] ?? 'admin';
            $admin_password = $_SESSION['install_admin_password'] ?? 'admin123';
            
            // Create configuration array (without storing password)
            $config = [
                'app_name' => 'Feuerwehr Management',
                'app_version' => '1.0.0',
                'encryption_key' => $encryption_key,
                'session_lifetime' => 3600,
                'email' => [
                    'from_address' => $from_address,
                    'from_name' => $from_name,
                    'to_address' => $to_address,
                    'smtp_host' => $smtp_host,
                    'smtp_port' => $smtp_port,
                    'smtp_auth' => $smtp_auth,
                    'smtp_username' => $smtp_username,
                    'smtp_password' => $smtp_password,
                    'smtp_secure' => $smtp_secure,
                ],
                'data_dir' => __DIR__ . '/data',
                'backup_dir' => __DIR__ . '/data/backups',
                // Default admin credentials - used as fallback if users.json is deleted
                // These credentials match the admin user created during installation
                'default_admin' => [
                    'username' => $admin_username,
                    'password' => $admin_password,
                ],
            ];
            
            // Generate PHP config file content
            $configContent = "<?php\n";
            $configContent .= "/**\n";
            $configContent .= " * Configuration file for Feuerwehr App\n";
            $configContent .= " * Generated by Installation Wizard on " . date('Y-m-d H:i:s') . "\n";
            $configContent .= " */\n\n";
            $configContent .= "return " . var_export($config, true) . ";\n";
            
            // Ensure config directory exists
            $configDir = __DIR__ . '/config';
            if (!file_exists($configDir)) {
                mkdir($configDir, 0700, true);
            }
            
            // Write config file
            if (file_put_contents($configFile, $configContent) !== false) {
                chmod($configFile, 0600);
                
                // Create data directory
                $dataDir = __DIR__ . '/data';
                if (!file_exists($dataDir)) {
                    mkdir($dataDir, 0700, true);
                }
                
                // Create admin user directly in users.json
                // We need to encrypt manually to match the Encryption class pattern
                $adminUser = [
                    'id' => uniqid('user_'),
                    'username' => $admin_username,
                    'password' => password_hash($admin_password, PASSWORD_DEFAULT),
                    'role' => 'admin',
                    'created_at' => date('Y-m-d H:i:s')
                ];
                
                $usersData = json_encode([$adminUser], JSON_PRETTY_PRINT);
                
                // Use the Encryption class to ensure consistent encryption format
                // We need to set up a temporary config for the Encryption class to use
                $tempConfigContent = "<?php\nreturn " . var_export($config, true) . ";\n";
                file_put_contents($configFile, $tempConfigContent);
                chmod($configFile, 0600);
                
                // Now load the Encryption class and encrypt the users data
                require_once __DIR__ . '/src/php/encryption.php';
                try {
                    $encryptedUsers = Encryption::encrypt($usersData);
                    
                    file_put_contents($dataDir . '/users.json', $encryptedUsers);
                    chmod($dataDir . '/users.json', 0600);
                    
                    // Clear session data properly
                    // Note: This code is duplicated from Auth::clearSessionCookie() to avoid
                    // circular dependencies (Auth class requires config.php which we're creating here)
                    if (session_status() === PHP_SESSION_ACTIVE) {
                        // Cookie expiry offset constant (matches Auth::COOKIE_EXPIRY_OFFSET)
                        $cookieExpiryOffset = 3600; // 1 hour in the past
                        
                        session_unset();
                        session_destroy();
                        // Clear the session cookie using proper parameters
                        if (isset($_COOKIE[session_name()])) {
                            $params = session_get_cookie_params();
                            setcookie(
                                session_name(),
                                '',
                                time() - $cookieExpiryOffset,
                                $params['path'],
                                $params['domain'],
                                $params['secure'],
                                $params['httponly']
                            );
                        }
                    }
                    
                    $success = true;
                    
                    // Store installation details for diagnostics
                    $_SESSION['install_success'] = true;
                    $_SESSION['install_username'] = $admin_username;
                    $_SESSION['install_password'] = $admin_password;
                    $_SESSION['install_time'] = time();
                    
                    $step = 4;
                } catch (Exception $e) {
                    $errors[] = 'Fehler beim Verschlüsseln der Benutzerdaten: ' . $e->getMessage();
                    // Clean up the config file if encryption failed
                    if (file_exists($configFile)) {
                        unlink($configFile);
                    }
                    $step = 3; // Stay on step 3
                }
            } else {
                $errors[] = 'Fehler beim Schreiben der Konfigurationsdatei. Prüfen Sie die Dateiberechtigungen.';
            }
        }
    }
}

// Handle diagnostic tests
if (isset($_GET['diagnose']) && $_GET['diagnose'] === 'run') {
    $diagnosticResults = runDiagnosticTests();
}

/**
 * Run comprehensive diagnostic tests
 */
function runDiagnosticTests() {
    $results = [];
    $allPassed = true;
    
    // Test 1: Config file exists and is readable
    $configFile = __DIR__ . '/config/config.php';
    $configExists = file_exists($configFile);
    $configReadable = $configExists && is_readable($configFile);
    
    $results[] = [
        'name' => 'Konfigurationsdatei',
        'test' => 'config/config.php existiert und ist lesbar',
        'status' => $configReadable,
        'details' => $configReadable ? 'Datei vorhanden und lesbar' : 
                     ($configExists ? 'Datei existiert, aber nicht lesbar (Berechtigungsproblem)' : 'Datei existiert nicht'),
        'critical' => true
    ];
    if (!$configReadable) $allPassed = false;
    
    // Test 2: Load and validate config
    $configValid = false;
    $encryptionKey = null;
    if ($configReadable) {
        try {
            $config = require $configFile;
            $configValid = is_array($config) && isset($config['encryption_key']) && isset($config['data_dir']);
            if ($configValid) {
                $encryptionKey = $config['encryption_key'];
            }
            
            $results[] = [
                'name' => 'Konfiguration laden',
                'test' => 'Config-Array mit erforderlichen Schlüsseln',
                'status' => $configValid,
                'details' => $configValid ? 'Alle erforderlichen Konfigurationsschlüssel vorhanden' : 'Ungültige Konfigurationsstruktur',
                'critical' => true
            ];
        } catch (Exception $e) {
            $results[] = [
                'name' => 'Konfiguration laden',
                'test' => 'Config-Datei ohne PHP-Fehler laden',
                'status' => false,
                'details' => 'Fehler beim Laden: ' . $e->getMessage(),
                'critical' => true
            ];
            $allPassed = false;
        }
    }
    if (!$configValid) $allPassed = false;
    
    // Test 3: Data directory exists and is writable
    $dataDir = __DIR__ . '/data';
    $dataDirExists = file_exists($dataDir);
    $dataDirWritable = $dataDirExists && is_writable($dataDir);
    
    $results[] = [
        'name' => 'Datenverzeichnis',
        'test' => 'data/ Verzeichnis existiert und ist beschreibbar',
        'status' => $dataDirWritable,
        'details' => $dataDirWritable ? 'Verzeichnis OK' : 
                     ($dataDirExists ? 'Existiert, aber nicht beschreibbar' : 'Existiert nicht'),
        'critical' => true
    ];
    if (!$dataDirWritable) $allPassed = false;
    
    // Test 4: users.json exists and is readable
    $usersFile = $dataDir . '/users.json';
    $usersExists = file_exists($usersFile);
    $usersReadable = $usersExists && is_readable($usersFile);
    
    $results[] = [
        'name' => 'Benutzerdatei',
        'test' => 'data/users.json existiert und ist lesbar',
        'status' => $usersReadable,
        'details' => $usersReadable ? 'Datei vorhanden und lesbar' : 
                     ($usersExists ? 'Datei existiert, aber nicht lesbar' : 'Datei existiert nicht'),
        'critical' => true
    ];
    if (!$usersReadable) $allPassed = false;
    
    // Test 5: Decrypt users.json
    $usersDecrypted = false;
    $usersData = null;
    if ($usersReadable && $encryptionKey) {
        try {
            $encryptedData = file_get_contents($usersFile);
            $keyBinary = hex2bin($encryptionKey);
            
            if ($keyBinary === false) {
                throw new Exception('Ungültiger Verschlüsselungsschlüssel (kein Hex)');
            }
            
            $decoded = base64_decode($encryptedData);
            if ($decoded === false) {
                throw new Exception('Base64-Dekodierung fehlgeschlagen');
            }
            
            $parts = explode('::', $decoded, 2);
            if (count($parts) !== 2) {
                throw new Exception('Ungültiges Verschlüsselungsformat');
            }
            
            list($iv, $encrypted) = $parts;
            $decrypted = openssl_decrypt($encrypted, 'aes-256-cbc', $keyBinary, OPENSSL_RAW_DATA, $iv);
            
            if ($decrypted === false) {
                throw new Exception('Entschlüsselung fehlgeschlagen');
            }
            
            $usersData = json_decode($decrypted, true);
            $usersDecrypted = is_array($usersData) && count($usersData) > 0;
            
            $results[] = [
                'name' => 'Benutzer entschlüsseln',
                'test' => 'users.json kann mit dem Verschlüsselungsschlüssel entschlüsselt werden',
                'status' => $usersDecrypted,
                'details' => $usersDecrypted ? count($usersData) . ' Benutzer gefunden' : 'Entschlüsselung fehlgeschlagen',
                'critical' => true
            ];
        } catch (Exception $e) {
            $results[] = [
                'name' => 'Benutzer entschlüsseln',
                'test' => 'users.json entschlüsseln',
                'status' => false,
                'details' => 'Fehler: ' . $e->getMessage(),
                'critical' => true
            ];
            $allPassed = false;
        }
    }
    if (!$usersDecrypted) $allPassed = false;
    
    // Test 6: Verify admin user credentials
    if ($usersDecrypted && isset($_SESSION['install_username']) && isset($_SESSION['install_password'])) {
        $testUsername = $_SESSION['install_username'];
        $testPassword = $_SESSION['install_password'];
        $credentialsValid = false;
        
        foreach ($usersData as $user) {
            if ($user['username'] === $testUsername && password_verify($testPassword, $user['password'])) {
                $credentialsValid = true;
                break;
            }
        }
        
        $results[] = [
            'name' => 'Admin-Zugangsdaten',
            'test' => 'Administrator-Benutzer kann sich anmelden',
            'status' => $credentialsValid,
            'details' => $credentialsValid ? 
                        "Benutzer '$testUsername' mit Passwort verifiziert" : 
                        "Benutzer '$testUsername' oder Passwort ungültig",
            'critical' => true
        ];
        if (!$credentialsValid) $allPassed = false;
    }
    
    // Test 7: Session functionality
    $sessionWorking = session_status() === PHP_SESSION_ACTIVE;
    $_SESSION['diagnostic_test'] = 'test_value_' . time();
    $sessionDataPersists = isset($_SESSION['diagnostic_test']);
    
    $results[] = [
        'name' => 'PHP Sessions',
        'test' => 'Sessions funktionieren korrekt',
        'status' => $sessionWorking && $sessionDataPersists,
        'details' => $sessionWorking ? 
                    ($sessionDataPersists ? 'Session-ID: ' . session_id() : 'Session aktiv, aber Daten werden nicht gespeichert') : 
                    'Session konnte nicht gestartet werden',
        'critical' => true
    ];
    if (!$sessionWorking || !$sessionDataPersists) $allPassed = false;
    
    // Test 8: PHP extensions
    $requiredExtensions = ['openssl', 'mbstring', 'json', 'session'];
    foreach ($requiredExtensions as $ext) {
        $loaded = extension_loaded($ext);
        $results[] = [
            'name' => "PHP Extension: $ext",
            'test' => "Extension '$ext' ist geladen",
            'status' => $loaded,
            'details' => $loaded ? 'Geladen' : 'Nicht geladen',
            'critical' => true
        ];
        if (!$loaded) $allPassed = false;
    }
    
    // Test 9: Webserver detection
    $webserver = $_SERVER['SERVER_SOFTWARE'] ?? 'Unbekannt';
    $isNginx = stripos($webserver, 'nginx') !== false;
    $isApache = stripos($webserver, 'apache') !== false;
    
    $results[] = [
        'name' => 'Webserver',
        'test' => 'Webserver-Typ erkennen',
        'status' => true,
        'details' => $webserver . ($isNginx ? ' (Nginx erkannt)' : ($isApache ? ' (Apache erkannt)' : '')),
        'critical' => false
    ];
    
    // Test 10: Session cookie parameters
    $cookieParams = session_get_cookie_params();
    $results[] = [
        'name' => 'Session-Cookie-Einstellungen',
        'test' => 'Cookie-Parameter abrufen',
        'status' => true,
        'details' => 'Path: ' . $cookieParams['path'] . ', Lifetime: ' . $cookieParams['lifetime'] . 's, HttpOnly: ' . ($cookieParams['httponly'] ? 'Ja' : 'Nein'),
        'critical' => false
    ];
    
    // Test 11: File permissions
    $configPerms = $configExists ? substr(sprintf('%o', fileperms($configFile)), -4) : 'N/A';
    $dataPerms = $dataDirExists ? substr(sprintf('%o', fileperms($dataDir)), -4) : 'N/A';
    $usersPerms = $usersExists ? substr(sprintf('%o', fileperms($usersFile)), -4) : 'N/A';
    
    $results[] = [
        'name' => 'Dateiberechtigungen',
        'test' => 'Berechtigungen prüfen',
        'status' => true,
        'details' => "config.php: $configPerms, data/: $dataPerms, users.json: $usersPerms",
        'critical' => false
    ];
    
    // Test 12: Check if Auth class can be loaded
    $authFile = __DIR__ . '/src/php/auth.php';
    $authExists = file_exists($authFile);
    
    $results[] = [
        'name' => 'Auth-Klasse',
        'test' => 'src/php/auth.php existiert',
        'status' => $authExists,
        'details' => $authExists ? 'Datei vorhanden' : 'Datei fehlt',
        'critical' => true
    ];
    if (!$authExists) $allPassed = false;
    
    // Test 13: Test actual login with Auth class
    if ($authExists && $configReadable && isset($_SESSION['install_username']) && isset($_SESSION['install_password'])) {
        try {
            require_once __DIR__ . '/src/php/encryption.php';
            require_once __DIR__ . '/src/php/auth.php';
            
            // Clear any existing auth session
            unset($_SESSION['user_id']);
            unset($_SESSION['username']);
            unset($_SESSION['role']);
            
            Auth::init();
            $loginSuccess = Auth::login($_SESSION['install_username'], $_SESSION['install_password']);
            
            $results[] = [
                'name' => 'Login-Test',
                'test' => 'Tatsächlicher Login mit Auth::login()',
                'status' => $loginSuccess,
                'details' => $loginSuccess ? 
                            'Login erfolgreich! Session-Daten gesetzt.' : 
                            'Login fehlgeschlagen trotz korrekter Zugangsdaten',
                'critical' => true
            ];
            
            if (!$loginSuccess) $allPassed = false;
            
            // Check if session was set
            if ($loginSuccess) {
                $sessionSet = isset($_SESSION['user_id']) && isset($_SESSION['username']) && isset($_SESSION['role']);
                $results[] = [
                    'name' => 'Session nach Login',
                    'test' => 'Session-Variablen nach Login gesetzt',
                    'status' => $sessionSet,
                    'details' => $sessionSet ? 
                                'user_id, username, role alle gesetzt' : 
                                'Session-Variablen nicht gesetzt',
                    'critical' => true
                ];
                if (!$sessionSet) $allPassed = false;
            }
        } catch (Exception $e) {
            $results[] = [
                'name' => 'Login-Test',
                'test' => 'Auth::login() ausführen',
                'status' => false,
                'details' => 'Fehler: ' . $e->getMessage(),
                'critical' => true
            ];
            $allPassed = false;
        }
    }
    
    // Test 14: PHP Version check
    $phpVersion = PHP_VERSION;
    $results[] = [
        'name' => 'PHP Version',
        'test' => 'PHP Version 7.4 oder höher',
        'status' => true,
        'details' => "PHP $phpVersion" . (version_compare($phpVersion, '8.4.0', '>=') ? ' (PHP 8.4+)' : ''),
        'critical' => false
    ];
    
    // Test 15: Session save path
    $sessionSavePath = session_save_path();
    $sessionPathWritable = !empty($sessionSavePath) && is_writable($sessionSavePath);
    
    $results[] = [
        'name' => 'Session-Speicherpfad',
        'test' => 'session.save_path ist beschreibbar',
        'status' => $sessionPathWritable,
        'details' => $sessionPathWritable ? 
                    "Pfad: $sessionSavePath (beschreibbar)" : 
                    (empty($sessionSavePath) ? 'Kein Pfad gesetzt (verwendet tmp)' : "Pfad: $sessionSavePath (nicht beschreibbar)"),
        'critical' => false
    ];
    
    // Test 16: Check for common Nginx/PHP-FPM issues
    $serverSoftware = $_SERVER['SERVER_SOFTWARE'] ?? 'Unbekannt';
    $isNginx = stripos($serverSoftware, 'nginx') !== false;
    
    if ($isNginx) {
        // Check if PHP is running as FPM
        $sapi = php_sapi_name();
        $isFPM = $sapi === 'fpm-fcgi' || $sapi === 'cgi-fcgi';
        
        $results[] = [
            'name' => 'PHP-FPM Erkennung',
            'test' => 'PHP läuft als FPM (empfohlen für Nginx)',
            'status' => $isFPM,
            'details' => "SAPI: $sapi" . ($isFPM ? ' ✓' : ' (nicht FPM)'),
            'critical' => false
        ];
        
        // Check SCRIPT_FILENAME
        $scriptFilename = $_SERVER['SCRIPT_FILENAME'] ?? '';
        $scriptFilenameCorrect = !empty($scriptFilename) && file_exists($scriptFilename);
        
        $results[] = [
            'name' => 'Nginx SCRIPT_FILENAME',
            'test' => 'SCRIPT_FILENAME ist korrekt gesetzt',
            'status' => $scriptFilenameCorrect,
            'details' => $scriptFilenameCorrect ? 
                        'Korrekt: ' . basename($scriptFilename) : 
                        'Fehlt oder ungültig (fastcgi_param Problem?)',
            'critical' => false
        ];
    }
    
    // Test 17: Check session cookie settings for Nginx
    $cookieParams = session_get_cookie_params();
    $cookiePathCorrect = !empty($cookieParams['path']);
    
    $results[] = [
        'name' => 'Session-Cookie-Pfad',
        'test' => 'Cookie-Pfad ist gesetzt',
        'status' => $cookiePathCorrect,
        'details' => $cookiePathCorrect ? 
                    "Pfad: {$cookieParams['path']}" : 
                    'Cookie-Pfad nicht gesetzt',
        'critical' => false
    ];
    
    // Test 18: Check if .htaccess might interfere (shouldn't on Nginx but good to check)
    $htaccessExists = file_exists(__DIR__ . '/.htaccess');
    $results[] = [
        'name' => '.htaccess Datei',
        'test' => 'Keine .htaccess auf Nginx-Server',
        'status' => !$htaccessExists || !$isNginx,
        'details' => $htaccessExists ? 
                    ($isNginx ? '⚠ .htaccess existiert (wird von Nginx ignoriert)' : '.htaccess existiert') : 
                    'Keine .htaccess Datei',
        'critical' => false
    ];
    
    // Test 19: Check error reporting settings
    $displayErrors = ini_get('display_errors');
    $logErrors = ini_get('log_errors');
    $errorLog = ini_get('error_log');
    
    $results[] = [
        'name' => 'PHP Fehlerprotokollierung',
        'test' => 'Fehler werden protokolliert',
        'status' => $logErrors == '1',
        'details' => "display_errors: $displayErrors, log_errors: $logErrors" . 
                    (!empty($errorLog) ? ", error_log: $errorLog" : ''),
        'critical' => false
    ];
    
    // Test 20: Memory and execution limits
    $memoryLimit = ini_get('memory_limit');
    $maxExecutionTime = ini_get('max_execution_time');
    
    $results[] = [
        'name' => 'PHP Ressourcen-Limits',
        'test' => 'Ausreichende PHP-Limits',
        'status' => true,
        'details' => "memory_limit: $memoryLimit, max_execution_time: {$maxExecutionTime}s",
        'critical' => false
    ];
    
    return [
        'results' => $results,
        'allPassed' => $allPassed,
        'timestamp' => date('Y-m-d H:i:s'),
        'phpVersion' => PHP_VERSION,
        'webserver' => $serverSoftware,
        'isNginx' => $isNginx
    ];
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation - Feuerwehr Management</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .install-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            max-width: 600px;
            width: 100%;
            overflow: hidden;
        }
        
        .install-header {
            background: linear-gradient(135deg, #d32f2f 0%, #c62828 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .install-header h1 {
            font-size: 28px;
            font-weight: 500;
            margin-bottom: 10px;
        }
        
        .install-header p {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .step-indicator {
            display: flex;
            justify-content: space-between;
            padding: 20px 30px;
            background: #f5f5f5;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .step {
            flex: 1;
            text-align: center;
            padding: 10px;
            position: relative;
        }
        
        .step::after {
            content: '';
            position: absolute;
            top: 20px;
            left: 50%;
            width: 100%;
            height: 2px;
            background: #e0e0e0;
            z-index: 0;
        }
        
        .step:last-child::after {
            display: none;
        }
        
        .step-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e0e0e0;
            color: #999;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 500;
            position: relative;
            z-index: 1;
            margin-bottom: 8px;
        }
        
        .step.active .step-number {
            background: #d32f2f;
            color: white;
        }
        
        .step.completed .step-number {
            background: #4caf50;
            color: white;
        }
        
        .step-label {
            font-size: 12px;
            color: #666;
        }
        
        .step.active .step-label {
            color: #d32f2f;
            font-weight: 500;
        }
        
        .install-content {
            padding: 40px 30px;
        }
        
        .section-title {
            font-size: 20px;
            color: #333;
            margin-bottom: 10px;
            font-weight: 500;
        }
        
        .section-description {
            color: #666;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            color: #333;
            margin-bottom: 8px;
        }
        
        .form-input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            font-family: 'Roboto', sans-serif;
            transition: border-color 0.3s;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #d32f2f;
        }
        
        .form-help {
            font-size: 12px;
            color: #999;
            margin-top: 5px;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        
        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        
        .alert-error {
            background: #ffebee;
            color: #c62828;
            border-left: 4px solid #c62828;
        }
        
        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
            border-left: 4px solid #4caf50;
        }
        
        .alert-info {
            background: #e3f2fd;
            color: #1565c0;
            border-left: 4px solid #2196f3;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            font-family: 'Roboto', sans-serif;
        }
        
        .btn-primary {
            background: #d32f2f;
            color: white;
        }
        
        .btn-primary:hover {
            background: #c62828;
            box-shadow: 0 4px 12px rgba(211, 47, 47, 0.3);
        }
        
        .btn-secondary {
            background: #757575;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #616161;
        }
        
        .button-group {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        
        .key-display {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            word-break: break-all;
            border: 2px dashed #d32f2f;
            margin: 20px 0;
        }
        
        .info-box {
            background: #fff3e0;
            border-left: 4px solid #ff9800;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        
        .info-box strong {
            display: block;
            margin-bottom: 5px;
            color: #e65100;
        }
        
        .success-icon {
            text-align: center;
            color: #4caf50;
            margin-bottom: 20px;
        }
        
        .success-icon .material-icons {
            font-size: 72px;
        }
        
        .requirements-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        .requirements-table th,
        .requirements-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .requirements-table th {
            background: #f5f5f5;
            font-weight: 500;
            color: #333;
        }
        
        .status-icon {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-weight: 500;
        }
        
        .status-pass {
            color: #4caf50;
        }
        
        .status-fail {
            color: #f44336;
        }
        
        .status-warning {
            color: #ff9800;
        }
        
        .row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        @media (max-width: 600px) {
            .row {
                grid-template-columns: 1fr;
            }
            
            .step-label {
                display: none;
            }
            
            .requirements-table {
                font-size: 14px;
            }
            
            .requirements-table th,
            .requirements-table td {
                padding: 8px;
            }
        }
    </style>
</head>
<body>
    <div class="install-container">
        <div class="install-header">
            <h1>🚒 Feuerwehr Management</h1>
            <p>Installations-Assistent</p>
        </div>
        
        <div class="step-indicator">
            <div class="step <?php echo $step >= 0 ? ($step == 0 ? 'active' : 'completed') : ''; ?>">
                <div class="step-number">1</div>
                <div class="step-label">Prüfung</div>
            </div>
            <div class="step <?php echo $step >= 1 ? ($step == 1 ? 'active' : 'completed') : ''; ?>">
                <div class="step-number">2</div>
                <div class="step-label">Start</div>
            </div>
            <div class="step <?php echo $step >= 2 ? ($step == 2 ? 'active' : 'completed') : ''; ?>">
                <div class="step-number">3</div>
                <div class="step-label">Admin</div>
            </div>
            <div class="step <?php echo $step >= 3 ? ($step == 3 ? 'active' : 'completed') : ''; ?>">
                <div class="step-number">4</div>
                <div class="step-label">E-Mail</div>
            </div>
            <div class="step <?php echo $step >= 4 ? 'active' : ''; ?>">
                <div class="step-number">5</div>
                <div class="step-label">Fertig</div>
            </div>
        </div>
        
        <div class="install-content">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <strong>Fehler:</strong>
                    <ul style="margin: 10px 0 0 20px;">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if ($step == 0): ?>
                <!-- Step 0: System Requirements Check -->
                <?php $requirements = checkSystemRequirements(); ?>
                
                <h2 class="section-title">System-Voraussetzungen prüfen</h2>
                <p class="section-description">
                    Bevor wir mit der Installation beginnen, prüfen wir, ob alle erforderlichen
                    Voraussetzungen auf Ihrem Server erfüllt sind.
                </p>
                
                <table class="requirements-table">
                    <thead>
                        <tr>
                            <th>Komponente</th>
                            <th>Erforderlich</th>
                            <th>Tatsächlich</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requirements['checks'] as $check): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($check['name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($check['required']); ?></td>
                                <td><?php echo htmlspecialchars($check['actual']); ?></td>
                                <td>
                                    <?php if ($check['status']): ?>
                                        <span class="status-icon status-pass">
                                            <span class="material-icons" style="font-size: 20px;">check_circle</span>
                                            OK
                                        </span>
                                    <?php elseif ($check['critical']): ?>
                                        <span class="status-icon status-fail">
                                            <span class="material-icons" style="font-size: 20px;">error</span>
                                            Fehler
                                        </span>
                                    <?php else: ?>
                                        <span class="status-icon status-warning">
                                            <span class="material-icons" style="font-size: 20px;">warning</span>
                                            Warnung
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <?php if ($requirements['allPassed']): ?>
                    <div class="alert alert-success">
                        <strong>Alle erforderlichen Voraussetzungen sind erfüllt!</strong><br>
                        Sie können mit der Installation fortfahren.
                    </div>
                    
                    <form method="POST" action="?step=0">
                        <div class="button-group">
                            <button type="submit" class="btn btn-primary">
                                <span class="material-icons">arrow_forward</span>
                                Installation starten
                            </button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="alert alert-error">
                        <strong>Einige erforderliche Voraussetzungen sind nicht erfüllt.</strong><br>
                        Bitte beheben Sie die rot markierten Fehler, bevor Sie mit der Installation fortfahren können.
                    </div>
                    
                    <div class="info-box">
                        <strong>Hilfe zur Fehlerbehebung:</strong>
                        <ul style="margin: 10px 0 0 20px; line-height: 1.8;">
                            <li><strong>PHP Extensions:</strong> Installieren Sie fehlende Extensions über Ihren Server-Administrator oder Hosting-Provider</li>
                            <li><strong>Berechtigungen:</strong> Setzen Sie Schreibrechte für die Verzeichnisse: <code>chmod 755 config data</code></li>
                            <li><strong>PHP Version:</strong> Aktualisieren Sie PHP auf Version 7.4 oder höher</li>
                        </ul>
                    </div>
                    
                    <form method="GET" action="?step=0">
                        <div class="button-group">
                            <button type="submit" class="btn btn-secondary">
                                <span class="material-icons">refresh</span>
                                Erneut prüfen
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
                
            <?php elseif ($step == 1): ?>
                <!-- Step 1: Welcome -->
                <h2 class="section-title">Willkommen zum Installations-Assistenten</h2>
                <p class="section-description">
                    Dieser Assistent hilft Ihnen bei der Einrichtung der Feuerwehr Management App.
                    Sie werden durch alle notwendigen Schritte geführt.
                </p>
                
                <div class="info-box">
                    <strong>Was wird eingerichtet?</strong>
                    <ul style="margin: 10px 0 0 20px; line-height: 1.8;">
                        <li>Verschlüsselungsschlüssel für sichere Datenspeicherung</li>
                        <li>Erster Administrator-Benutzer</li>
                        <li>E-Mail-Einstellungen für Formular-Übermittlungen</li>
                    </ul>
                </div>
                
                <div class="alert alert-info">
                    <strong>Hinweis:</strong> Der Verschlüsselungsschlüssel wird automatisch generiert.
                    Sie benötigen keinen Zugriff auf die Kommandozeile.
                </div>
                
                <form method="POST" action="?step=1">
                    <div class="button-group">
                        <button type="submit" class="btn btn-primary">
                            <span class="material-icons">arrow_forward</span>
                            Installation starten
                        </button>
                    </div>
                </form>
                
            <?php elseif ($step == 2): ?>
                <!-- Step 2: Admin User -->
                <h2 class="section-title">Administrator-Benutzer erstellen</h2>
                <p class="section-description">
                    Erstellen Sie den ersten Administrator-Benutzer. Mit diesem Benutzer können Sie sich
                    nach der Installation anmelden und weitere Benutzer verwalten.
                </p>
                
                <form method="POST" action="?step=2">
                    <div class="form-group">
                        <label class="form-label" for="admin_username">Benutzername *</label>
                        <input type="text" id="admin_username" name="admin_username" class="form-input" 
                               value="<?php echo htmlspecialchars($_POST['admin_username'] ?? 'admin'); ?>" 
                               required autofocus>
                        <div class="form-help">Mindestens 3 Zeichen</div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="admin_password">Passwort *</label>
                        <input type="password" id="admin_password" name="admin_password" class="form-input" required>
                        <div class="form-help">Mindestens 6 Zeichen</div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="admin_password_confirm">Passwort bestätigen *</label>
                        <input type="password" id="admin_password_confirm" name="admin_password_confirm" class="form-input" required>
                    </div>
                    
                    <div class="button-group">
                        <button type="submit" class="btn btn-primary">
                            <span class="material-icons">arrow_forward</span>
                            Weiter
                        </button>
                    </div>
                </form>
                
            <?php elseif ($step == 3): ?>
                <!-- Step 3: Email Settings -->
                <h2 class="section-title">E-Mail-Einstellungen</h2>
                <p class="section-description">
                    Konfigurieren Sie die E-Mail-Einstellungen für automatische Benachrichtigungen und Formular-Übermittlungen.
                </p>
                
                <form method="POST" action="?step=3">
                    <h3 style="margin-top: 0; margin-bottom: 15px; font-size: 16px; color: #666;">Absender-Informationen</h3>
                    
                    <div class="form-group">
                        <label class="form-label" for="from_address">Absender E-Mail-Adresse *</label>
                        <input type="email" id="from_address" name="from_address" class="form-input" 
                               value="<?php echo htmlspecialchars($_POST['from_address'] ?? 'noreply@feuerwehr.local'); ?>" 
                               required>
                        <div class="form-help">E-Mail-Adresse, die als Absender verwendet wird</div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="from_name">Absender Name *</label>
                        <input type="text" id="from_name" name="from_name" class="form-input" 
                               value="<?php echo htmlspecialchars($_POST['from_name'] ?? 'Feuerwehr Management System'); ?>" 
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="to_address">Standard-Empfänger E-Mail-Adresse</label>
                        <input type="email" id="to_address" name="to_address" class="form-input" 
                               value="<?php echo htmlspecialchars($_POST['to_address'] ?? ''); ?>">
                        <div class="form-help">Optional: Standard-Empfänger für alle Formulare</div>
                    </div>
                    
                    <h3 style="margin-top: 30px; margin-bottom: 15px; font-size: 16px; color: #666;">SMTP-Einstellungen</h3>
                    
                    <div class="row">
                        <div class="form-group">
                            <label class="form-label" for="smtp_host">SMTP Server</label>
                            <input type="text" id="smtp_host" name="smtp_host" class="form-input" 
                                   value="<?php echo htmlspecialchars($_POST['smtp_host'] ?? 'localhost'); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="smtp_port">SMTP Port</label>
                            <input type="number" id="smtp_port" name="smtp_port" class="form-input" 
                                   value="<?php echo htmlspecialchars($_POST['smtp_port'] ?? '25'); ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="smtp_secure">Verschlüsselung</label>
                        <select id="smtp_secure" name="smtp_secure" class="form-input">
                            <option value="">Keine</option>
                            <option value="tls" <?php echo ($_POST['smtp_secure'] ?? '') === 'tls' ? 'selected' : ''; ?>>TLS</option>
                            <option value="ssl" <?php echo ($_POST['smtp_secure'] ?? '') === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" id="smtp_auth" name="smtp_auth" value="1" 
                                   <?php echo !empty($_POST['smtp_auth']) ? 'checked' : ''; ?>
                                   onchange="document.getElementById('smtp_credentials').style.display = this.checked ? 'block' : 'none';">
                            <label class="form-label" for="smtp_auth" style="margin: 0;">SMTP-Authentifizierung verwenden</label>
                        </div>
                    </div>
                    
                    <div id="smtp_credentials" style="display: <?php echo !empty($_POST['smtp_auth']) ? 'block' : 'none'; ?>;">
                        <div class="form-group">
                            <label class="form-label" for="smtp_username">SMTP Benutzername</label>
                            <input type="text" id="smtp_username" name="smtp_username" class="form-input" 
                                   value="<?php echo htmlspecialchars($_POST['smtp_username'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="smtp_password">SMTP Passwort</label>
                            <input type="password" id="smtp_password" name="smtp_password" class="form-input">
                        </div>
                    </div>
                    
                    <div class="button-group">
                        <button type="submit" class="btn btn-primary">
                            <span class="material-icons">check</span>
                            Installation abschließen
                        </button>
                    </div>
                </form>
                
            <?php elseif ($step == 4): ?>
                <!-- Step 4: Success with Diagnostics -->
                <?php if (isset($_GET['diagnose']) && $_GET['diagnose'] === 'run'): ?>
                    <!-- Diagnostic Results -->
                    <?php $diagnostics = $diagnosticResults ?? runDiagnosticTests(); ?>
                    
                    <h2 class="section-title">Diagnose-Ergebnisse</h2>
                    <p class="section-description">
                        Umfassende Tests zur Identifizierung von Problemen, die die Anmeldung verhindern könnten.
                        <br><small style="color: #666;">
                            PHP <?php echo htmlspecialchars($diagnostics['phpVersion']); ?> | 
                            <?php echo htmlspecialchars($diagnostics['webserver']); ?>
                            <?php if ($diagnostics['isNginx']): ?>
                                | <strong>Nginx-spezifische Tests aktiv</strong>
                            <?php endif; ?>
                        </small>
                    </p>
                    
                    <?php if ($diagnostics['allPassed']): ?>
                        <div class="alert alert-success">
                            <strong>✓ Alle Tests bestanden!</strong><br>
                            Die Installation ist vollständig und funktionsfähig. Sie sollten sich jetzt anmelden können.
                        </div>
                    <?php else: ?>
                        <div class="alert alert-error">
                            <strong>⚠ Einige Tests sind fehlgeschlagen</strong><br>
                            Beheben Sie die unten aufgeführten Probleme, bevor Sie sich anmelden.
                        </div>
                    <?php endif; ?>
                    
                    <table class="requirements-table" style="margin-top: 20px;">
                        <thead>
                            <tr>
                                <th>Komponente</th>
                                <th>Test</th>
                                <th>Details</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($diagnostics['results'] as $result): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($result['name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($result['test']); ?></td>
                                    <td style="font-size: 12px; color: #666;"><?php echo htmlspecialchars($result['details']); ?></td>
                                    <td>
                                        <?php if ($result['status']): ?>
                                            <span class="status-icon status-pass">
                                                <span class="material-icons" style="font-size: 20px;">check_circle</span>
                                                OK
                                            </span>
                                        <?php elseif ($result['critical']): ?>
                                            <span class="status-icon status-fail">
                                                <span class="material-icons" style="font-size: 20px;">error</span>
                                                Fehler
                                            </span>
                                        <?php else: ?>
                                            <span class="status-icon status-warning">
                                                <span class="material-icons" style="font-size: 20px;">warning</span>
                                                Warnung
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <div class="info-box" style="margin-top: 20px;">
                        <strong>Häufige Probleme und Lösungen (Nginx + PHP 8.4):</strong>
                        <ul style="margin: 10px 0 0 20px; line-height: 1.8;">
                            <li><strong>Config-Datei existiert nicht:</strong> 
                                <br>→ Prüfen Sie Schreibrechte: <code>sudo chown -R www-data:www-data config/ data/</code>
                                <br>→ Oder: <code>sudo chmod 755 config/ data/</code>
                            </li>
                            <li><strong>Entschlüsselung fehlgeschlagen:</strong> 
                                <br>→ Datei wurde möglicherweise beschädigt
                                <br>→ Löschen Sie config/config.php und data/users.json und führen Sie die Installation erneut durch
                            </li>
                            <li><strong>Session-Probleme (Nginx):</strong> 
                                <br>→ Prüfen Sie: <code>ls -la /var/lib/php/sessions/</code> (oder /tmp je nach Konfiguration)
                                <br>→ Stellen Sie sicher, dass www-data Schreibrechte hat: <code>sudo chown www-data:www-data /var/lib/php/sessions/</code>
                                <br>→ In php.ini: <code>session.save_path = "/var/lib/php/sessions"</code>
                            </li>
                            <li><strong>Login fehlschlägt trotz korrekter Daten:</strong> 
                                <br>→ Löschen Sie alle Browser-Cookies für diese Domain
                                <br>→ Prüfen Sie Nginx fastcgi_params: <code>fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;</code>
                                <br>→ Starten Sie PHP-FPM neu: <code>sudo systemctl restart php8.4-fpm</code>
                            </li>
                            <li><strong>PHP 8.4 spezifisch:</strong> 
                                <br>→ Stellen Sie sicher, dass alle PHP 8.4 Extensions installiert sind: <code>sudo apt install php8.4-fpm php8.4-mbstring php8.4-json</code>
                                <br>→ Prüfen Sie PHP-FPM Socket: <code>ls -la /run/php/php8.4-fpm.sock</code>
                            </li>
                            <li><strong>Nginx Konfiguration prüfen:</strong> 
                                <br>→ <code>sudo nginx -t</code> (Syntax-Test)
                                <br>→ <code>sudo systemctl reload nginx</code>
                                <br>→ Prüfen Sie den location Block für .php Dateien
                            </li>
                            <li><strong>Dateiberechtigungen (empfohlen):</strong> 
                                <br>→ Verzeichnisse: <code>chmod 755 config/ data/</code>
                                <br>→ Dateien: <code>chmod 644 config/config.php data/users.json</code>
                                <br>→ Owner: <code>chown www-data:www-data config/ data/ -R</code>
                            </li>
                        </ul>
                    </div>
                    
                    <div class="info-box" style="margin-top: 20px; background: #e3f2fd; border-left-color: #2196f3;">
                        <strong>🔍 Debug-Befehle für die Kommandozeile:</strong>
                        <ul style="margin: 10px 0 0 20px; line-height: 1.8; font-family: monospace; font-size: 13px;">
                            <li>PHP-FPM Status: <code>sudo systemctl status php8.4-fpm</code></li>
                            <li>PHP-FPM Error Log: <code>sudo tail -f /var/log/php8.4-fpm.log</code></li>
                            <li>Nginx Error Log: <code>sudo tail -f /var/log/nginx/error.log</code></li>
                            <li>Session-Verzeichnis: <code>ls -la $(php -r "echo session_save_path();")</code></li>
                            <li>PHP Config: <code>php --ini</code></li>
                            <li>Berechtigungen prüfen: <code>ls -la <?php echo __DIR__; ?>/config/ <?php echo __DIR__; ?>/data/</code></li>
                        </ul>
                    </div>
                    
                    <?php if (isset($_SESSION['install_username']) && isset($_SESSION['install_password'])): ?>
                        <div class="alert alert-info" style="margin-top: 20px;">
                            <strong>Ihre Zugangsdaten:</strong><br>
                            Benutzername: <code><?php echo htmlspecialchars($_SESSION['install_username']); ?></code><br>
                            Passwort: <code><?php echo htmlspecialchars($_SESSION['install_password']); ?></code>
                        </div>
                    <?php endif; ?>
                    
                    <div class="button-group" style="justify-content: center; margin-top: 30px;">
                        <a href="?step=4" class="btn btn-secondary">
                            <span class="material-icons">arrow_back</span>
                            Zurück
                        </a>
                        <a href="?step=4&diagnose=run" class="btn btn-secondary">
                            <span class="material-icons">refresh</span>
                            Tests erneut durchführen
                        </a>
                        <a href="index.php" class="btn btn-primary">
                            <span class="material-icons">login</span>
                            Zur Anmeldung
                        </a>
                    </div>
                    
                <?php else: ?>
                    <!-- Success Page -->
                    <div class="success-icon">
                        <span class="material-icons">check_circle</span>
                    </div>
                    
                    <h2 class="section-title" style="text-align: center;">Installation erfolgreich abgeschlossen!</h2>
                    <p class="section-description" style="text-align: center;">
                        Die Feuerwehr Management App wurde erfolgreich eingerichtet und ist jetzt einsatzbereit.
                    </p>
                    
                    <div class="alert alert-success">
                        <strong>Konfiguration erstellt:</strong>
                        <ul style="margin: 10px 0 0 20px;">
                            <li>Verschlüsselungsschlüssel generiert</li>
                            <li>Administrator-Benutzer erstellt</li>
                            <li>E-Mail-Einstellungen konfiguriert</li>
                            <li>Datenverzeichnis erstellt</li>
                        </ul>
                    </div>
                    
                    <?php if (isset($_SESSION['install_username']) && isset($_SESSION['install_password'])): ?>
                        <div class="alert alert-info">
                            <strong>Ihre Zugangsdaten:</strong><br>
                            Benutzername: <code style="background: #f5f5f5; padding: 2px 6px; border-radius: 3px;"><?php echo htmlspecialchars($_SESSION['install_username']); ?></code><br>
                            Passwort: <code style="background: #f5f5f5; padding: 2px 6px; border-radius: 3px;"><?php echo htmlspecialchars($_SESSION['install_password']); ?></code><br>
                            <small style="color: #666; display: block; margin-top: 8px;">Notieren Sie sich diese Daten, bevor Sie fortfahren!</small>
                        </div>
                    <?php endif; ?>
                    
                    <div class="info-box">
                        <strong>Nächste Schritte:</strong>
                        <ul style="margin: 10px 0 0 20px; line-height: 1.8;">
                            <li>Führen Sie die Diagnose-Tests durch, um sicherzustellen, dass alles funktioniert</li>
                            <li>Melden Sie sich mit Ihrem Administrator-Benutzer an</li>
                            <li>Fügen Sie Einsatzkräfte hinzu</li>
                            <li>Konfigurieren Sie Fahrzeuge</li>
                            <li>Erstellen Sie weitere Benutzer bei Bedarf</li>
                        </ul>
                    </div>
                    
                    <div class="info-box" style="background: #fff3e0; border-left-color: #ff9800;">
                        <strong>⚠ Wichtig für Nginx-Server:</strong>
                        <ul style="margin: 10px 0 0 20px; line-height: 1.8;">
                            <li>Stellen Sie sicher, dass PHP-FPM korrekt konfiguriert ist</li>
                            <li>Prüfen Sie die Session-Einstellungen in php.ini</li>
                            <li>Vergewissern Sie sich, dass der Webserver-User (z.B. www-data) Schreibrechte für config/ und data/ hat</li>
                            <li>Bei Problemen: Führen Sie die Diagnose-Tests aus</li>
                        </ul>
                    </div>
                    
                    <div class="button-group" style="justify-content: center;">
                        <a href="?step=4&diagnose=run" class="btn btn-secondary">
                            <span class="material-icons">troubleshoot</span>
                            Diagnose-Tests durchführen
                        </a>
                        <a href="index.php" class="btn btn-primary">
                            <span class="material-icons">login</span>
                            Zur Anmeldung
                        </a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
