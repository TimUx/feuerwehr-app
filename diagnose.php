<?php
/**
 * Standalone Diagnostic Tool for Feuerwehr App
 * This script can be run independently to diagnose login and configuration issues
 */

// Prevent running if not accessed directly
if (php_sapi_name() === 'cli') {
    echo "This script should be run via web browser.\n";
    exit(1);
}

// Check if debug mode is enabled
$debugMode = isset($_GET['debug']) && $_GET['debug'] == '1';
$debugLog = [];

/**
 * Log debug message
 */
function debugLog($message, $level = 'INFO') {
    global $debugLog, $debugMode;
    if ($debugMode) {
        $debugLog[] = [
            'time' => microtime(true),
            'level' => $level,
            'message' => $message
        ];
    }
}

/**
 * Custom error handler for debug mode
 */
function debugErrorHandler($errno, $errstr, $errfile, $errline) {
    global $debugMode;
    if ($debugMode) {
        $errorType = match($errno) {
            E_ERROR => 'ERROR',
            E_WARNING => 'WARNING',
            E_NOTICE => 'NOTICE',
            E_USER_ERROR => 'USER_ERROR',
            E_USER_WARNING => 'USER_WARNING',
            E_USER_NOTICE => 'USER_NOTICE',
            E_DEPRECATED => 'DEPRECATED',
            default => 'UNKNOWN'
        };
        debugLog("PHP $errorType: $errstr in $errfile on line $errline", 'ERROR');
        // Return true to suppress PHP internal error handler in debug mode
        return true;
    }
    // Let PHP's internal error handler run when debug mode is off
    return false;
}

// Set custom error handler if debug mode is enabled
if ($debugMode) {
    // Enable error reporting and display
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    
    set_error_handler('debugErrorHandler');
    debugLog("Debug mode activated", 'INFO');
    debugLog("Error reporting enabled: E_ALL (" . error_reporting() . ")", 'INFO');
    debugLog("Display errors enabled: " . ini_get('display_errors'), 'INFO');
    debugLog("PHP Version: " . PHP_VERSION, 'INFO');
    debugLog("Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'), 'INFO');
    debugLog("SAPI: " . php_sapi_name(), 'INFO');
}

require_once __DIR__ . '/src/php/session_init.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    debugLog("Starting session...", 'INFO');
    initSecureSession();
    debugLog("Session started with ID: " . session_id(), 'INFO');
} else {
    debugLog("Session already active with ID: " . session_id(), 'INFO');
}

/**
 * Read last N lines from a file efficiently
 */
function readLastNLines($filePath, $n = 20) {
    if (!is_readable($filePath)) {
        return [];
    }
    
    $lines = [];
    try {
        $file = new SplFileObject($filePath);
        $file->seek(PHP_INT_MAX);
        $totalLines = $file->key();
        $startLine = max(0, $totalLines - $n);
        $file->seek($startLine);
        
        while (!$file->eof()) {
            $line = trim($file->fgets());
            if (!empty($line)) {
                $lines[] = $line;
            }
        }
    } catch (Exception $e) {
        debugLog("Error reading file $filePath: " . $e->getMessage(), 'ERROR');
        return [];
    }
    
    return $lines;
}

$configFile = __DIR__ . '/config/config.php';
$dataDir = __DIR__ . '/data';
$usersFile = $dataDir . '/users.json';

debugLog("Config file path: $configFile", 'INFO');
debugLog("Data directory: $dataDir", 'INFO');
debugLog("Users file path: $usersFile", 'INFO');

/**
 * Run all diagnostic tests
 */
function runAllTests() {
    global $configFile, $dataDir, $usersFile;
    
    debugLog("=== Starting diagnostic tests ===", 'INFO');
    
    $tests = [];
    $criticalFailures = 0;
    
    // Test 1: PHP Version
    debugLog("Test 1: Checking PHP version", 'INFO');
    $phpVersion = PHP_VERSION;
    $versionOk = version_compare($phpVersion, '7.4.0', '>=');
    debugLog("PHP version: $phpVersion, requirement: >= 7.4.0, ok: " . ($versionOk ? 'yes' : 'no'), $versionOk ? 'INFO' : 'ERROR');
    $tests[] = [
        'category' => 'System',
        'name' => 'PHP Version',
        'status' => $versionOk ? 'pass' : 'fail',
        'message' => "PHP $phpVersion" . (!$versionOk ? ' (mindestens 7.4.0 erforderlich)' : ''),
        'critical' => true
    ];
    if (!$versionOk) $criticalFailures++;
    
    // Test 2: Required PHP extensions
    debugLog("Test 2: Checking required PHP extensions", 'INFO');
    $requiredExtensions = ['openssl', 'mbstring', 'json', 'session'];
    foreach ($requiredExtensions as $ext) {
        $loaded = extension_loaded($ext);
        debugLog("Extension '$ext': " . ($loaded ? 'loaded' : 'NOT LOADED'), $loaded ? 'INFO' : 'ERROR');
        $tests[] = [
            'category' => 'System',
            'name' => "PHP Extension: $ext",
            'status' => $loaded ? 'pass' : 'fail',
            'message' => $loaded ? 'Geladen' : 'Nicht installiert',
            'critical' => true
        ];
        if (!$loaded) $criticalFailures++;
    }
    
    // Test 3: Config file
    debugLog("Test 3: Checking config file", 'INFO');
    $configExists = file_exists($configFile);
    $configReadable = $configExists && is_readable($configFile);
    debugLog("Config file exists: " . ($configExists ? 'yes' : 'no'), $configExists ? 'INFO' : 'ERROR');
    if ($configExists) {
        debugLog("Config file readable: " . ($configReadable ? 'yes' : 'no'), $configReadable ? 'INFO' : 'ERROR');
        debugLog("Config file permissions: " . substr(sprintf('%o', fileperms($configFile)), -4), 'INFO');
        debugLog("Config file owner: " . fileowner($configFile), 'INFO');
        debugLog("Current process user: " . get_current_user(), 'INFO');
    }
    $tests[] = [
        'category' => 'Konfiguration',
        'name' => 'config.php Datei',
        'status' => $configReadable ? 'pass' : 'fail',
        'message' => $configReadable ? 'Vorhanden und lesbar' : 
                     ($configExists ? 'Existiert aber nicht lesbar' : 'Datei nicht gefunden - Installation erforderlich'),
        'critical' => true,
        'fix' => !$configReadable ? 'Führen Sie install.php aus oder prüfen Sie Dateiberechtigungen' : null
    ];
    if (!$configReadable) $criticalFailures++;
    
    // Test 4: Load and validate config
    debugLog("Test 4: Loading and validating config", 'INFO');
    $config = null;
    $configValid = false;
    if ($configReadable) {
        try {
            debugLog("Attempting to load config file...", 'INFO');
            $config = require $configFile;
            debugLog("Config loaded successfully, type: " . gettype($config), 'INFO');
            
            if (is_array($config)) {
                debugLog("Config keys: " . implode(', ', array_keys($config)), 'INFO');
                debugLog("Has encryption_key: " . (isset($config['encryption_key']) ? 'yes' : 'no'), 'INFO');
                debugLog("Has data_dir: " . (isset($config['data_dir']) ? 'yes' : 'no'), 'INFO');
                debugLog("Has session_lifetime: " . (isset($config['session_lifetime']) ? 'yes' : 'no'), 'INFO');
            }
            
            $configValid = is_array($config) && 
                          isset($config['encryption_key']) && 
                          isset($config['data_dir']) &&
                          isset($config['session_lifetime']);
            
            debugLog("Config structure valid: " . ($configValid ? 'yes' : 'no'), $configValid ? 'INFO' : 'ERROR');
            
            $tests[] = [
                'category' => 'Konfiguration',
                'name' => 'Config-Struktur',
                'status' => $configValid ? 'pass' : 'fail',
                'message' => $configValid ? 'Alle erforderlichen Schlüssel vorhanden' : 'Ungültige Konfiguration',
                'critical' => true,
                'fix' => !$configValid ? 'Führen Sie install.php erneut aus' : null
            ];
            
            if ($configValid) {
                // Validate encryption key format
                debugLog("Validating encryption key format...", 'INFO');
                $keyLength = strlen($config['encryption_key']);
                $keyIsHex = ctype_xdigit($config['encryption_key']);
                debugLog("Encryption key length: $keyLength", 'INFO');
                debugLog("Encryption key is hex: " . ($keyIsHex ? 'yes' : 'no'), 'INFO');
                
                $keyValid = $keyIsHex && $keyLength === 64;
                debugLog("Encryption key valid: " . ($keyValid ? 'yes' : 'no'), $keyValid ? 'INFO' : 'ERROR');
                
                $tests[] = [
                    'category' => 'Konfiguration',
                    'name' => 'Verschlüsselungsschlüssel',
                    'status' => $keyValid ? 'pass' : 'fail',
                    'message' => $keyValid ? '64-Zeichen Hex-Schlüssel' : 'Ungültiges Format',
                    'critical' => true,
                    'fix' => !$keyValid ? 'Führen Sie install.php erneut aus' : null
                ];
                if (!$keyValid) $criticalFailures++;
            }
        } catch (Exception $e) {
            debugLog("Exception loading config: " . $e->getMessage(), 'ERROR');
            debugLog("Exception trace: " . $e->getTraceAsString(), 'ERROR');
            $tests[] = [
                'category' => 'Konfiguration',
                'name' => 'Config laden',
                'status' => 'fail',
                'message' => 'Fehler: ' . $e->getMessage(),
                'critical' => true,
                'fix' => 'Prüfen Sie die Syntax der config.php Datei'
            ];
            $criticalFailures++;
        }
    }
    if (!$configValid) $criticalFailures++;
    
    // Test 5: Data directory
    debugLog("Test 5: Checking data directory", 'INFO');
    $dataDirExists = file_exists($dataDir);
    $dataDirWritable = $dataDirExists && is_writable($dataDir);
    
    if ($dataDirExists) {
        debugLog("Data directory exists: $dataDir", 'INFO');
        debugLog("Data directory writable: " . ($dataDirWritable ? 'yes' : 'no'), $dataDirWritable ? 'INFO' : 'ERROR');
        if (function_exists('posix_getpwuid')) {
            $dirOwner = fileowner($dataDir);
            $dirGroup = filegroup($dataDir);
            $ownerInfo = @posix_getpwuid($dirOwner);
            $groupInfo = @posix_getgrgid($dirGroup);
            if ($ownerInfo !== false && $groupInfo !== false) {
                debugLog("Data directory owner: " . ($ownerInfo['name'] ?? $dirOwner) . " (UID: $dirOwner)", 'INFO');
                debugLog("Data directory group: " . ($groupInfo['name'] ?? $dirGroup) . " (GID: $dirGroup)", 'INFO');
            } else {
                debugLog("Data directory owner UID: $dirOwner, GID: $dirGroup (name lookup failed)", 'INFO');
            }
        }
        debugLog("Data directory permissions: " . substr(sprintf('%o', fileperms($dataDir)), -4), 'INFO');
    } else {
        debugLog("Data directory does NOT exist: $dataDir", 'ERROR');
    }
    
    $tests[] = [
        'category' => 'Dateisystem',
        'name' => 'data/ Verzeichnis',
        'status' => $dataDirWritable ? 'pass' : 'fail',
        'message' => $dataDirWritable ? 'Beschreibbar' : 
                     ($dataDirExists ? 'Existiert aber nicht beschreibbar' : 'Existiert nicht'),
        'critical' => true,
        'fix' => !$dataDirWritable ? 'Setzen Sie die Berechtigungen über FTP: Rechtsklick auf ' . basename($dataDir) . ' → Eigenschaften → Berechtigung auf 755 setzen' : null
    ];
    if (!$dataDirWritable) $criticalFailures++;
    
    // Test 5a: Detailed filesystem diagnostics for data directory
    debugLog("Test 5a: Running detailed filesystem diagnostics", 'INFO');
    
    // Check parent directory permissions
    $parentDir = dirname($dataDir);
    $parentDirExists = file_exists($parentDir);
    $parentDirWritable = $parentDirExists && is_writable($parentDir);
    
    if ($parentDirExists) {
        debugLog("Parent directory: $parentDir", 'INFO');
        debugLog("Parent directory writable: " . ($parentDirWritable ? 'yes' : 'no'), $parentDirWritable ? 'INFO' : 'WARN');
        debugLog("Parent directory permissions: " . substr(sprintf('%o', fileperms($parentDir)), -4), 'INFO');
        if (function_exists('posix_getpwuid')) {
            $parentOwner = fileowner($parentDir);
            $parentGroup = filegroup($parentDir);
            $ownerInfo = @posix_getpwuid($parentOwner);
            $groupInfo = @posix_getgrgid($parentGroup);
            if ($ownerInfo !== false && $groupInfo !== false) {
                debugLog("Parent directory owner: " . ($ownerInfo['name'] ?? $parentOwner) . " (UID: $parentOwner)", 'INFO');
                debugLog("Parent directory group: " . ($groupInfo['name'] ?? $parentGroup) . " (GID: $parentGroup)", 'INFO');
            } else {
                debugLog("Parent directory owner UID: $parentOwner, GID: $parentGroup (name lookup failed)", 'INFO');
            }
        }
    }
    
    $tests[] = [
        'category' => 'Dateisystem',
        'name' => 'Übergeordnetes Verzeichnis',
        'status' => $parentDirWritable ? 'pass' : 'warn',
        'message' => $parentDirWritable ? "Beschreibbar ($parentDir)" : 
                     ($parentDirExists ? "Nicht beschreibbar ($parentDir)" : "Existiert nicht ($parentDir)"),
        'critical' => false,
        'fix' => !$dataDirExists && !$parentDirWritable ? 
            "Das data/ Verzeichnis kann nicht erstellt werden. Setzen Sie über FTP die Berechtigung des übergeordneten Verzeichnisses auf 755" : null
    ];
    
    // Test 5b: Actual write test
    $writeTestSuccess = false;
    $writeTestError = '';
    if ($dataDirWritable) {
        $testFile = $dataDir . '/.write_test_' . uniqid();
        debugLog("Testing actual write operation to: $testFile", 'INFO');
        try {
            $writeResult = @file_put_contents($testFile, 'test');
            if ($writeResult !== false) {
                $writeTestSuccess = true;
                debugLog("Write test successful, wrote $writeResult bytes", 'INFO');
                @unlink($testFile);
                debugLog("Test file deleted successfully", 'INFO');
            } else {
                $writeTestError = error_get_last()['message'] ?? 'Unbekannter Fehler';
                debugLog("Write test failed: $writeTestError", 'ERROR');
            }
        } catch (Exception $e) {
            $writeTestError = $e->getMessage();
            debugLog("Write test exception: $writeTestError", 'ERROR');
        }
    } else {
        $writeTestError = 'Verzeichnis nicht beschreibbar';
        debugLog("Skipping write test because directory is not writable", 'WARN');
    }
    
    $tests[] = [
        'category' => 'Dateisystem',
        'name' => 'Schreibtest',
        'status' => $writeTestSuccess ? 'pass' : 'fail',
        'message' => $writeTestSuccess ? 'Erfolgreich Datei geschrieben und gelöscht' : 
                     "Schreibtest fehlgeschlagen: $writeTestError",
        'critical' => true,
        'fix' => !$writeTestSuccess && $dataDirExists ? 
            "Setzen Sie über FTP die Berechtigungen: Rechtsklick auf " . basename($dataDir) . " → Eigenschaften → Berechtigung auf 755 setzen (rekursiv)" : null
    ];
    if (!$writeTestSuccess) $criticalFailures++;
    
    // Test 5c: Disk space check
    debugLog("Test 5c: Checking disk space", 'INFO');
    $diskFreeBytes = @disk_free_space($parentDir);
    $diskTotalBytes = @disk_total_space($parentDir);
    
    // Minimum required free space in MB
    $minRequiredDiskSpaceMB = 100;
    
    if ($diskFreeBytes !== false && $diskTotalBytes !== false) {
        $diskFreeMB = round($diskFreeBytes / 1024 / 1024, 2);
        $diskTotalMB = round($diskTotalBytes / 1024 / 1024, 2);
        $diskUsedPercent = round((1 - $diskFreeBytes / $diskTotalBytes) * 100, 1);
        
        debugLog("Disk space: $diskFreeMB MB free of $diskTotalMB MB total ($diskUsedPercent% used)", 'INFO');
        
        $diskSpaceOk = $diskFreeMB > $minRequiredDiskSpaceMB;
        
        $tests[] = [
            'category' => 'Dateisystem',
            'name' => 'Festplattenspeicher',
            'status' => $diskSpaceOk ? 'pass' : 'warn',
            'message' => "$diskFreeMB MB frei von $diskTotalMB MB ($diskUsedPercent% belegt)",
            'critical' => false,
            'fix' => !$diskSpaceOk ? 'Wenig Speicherplatz verfügbar. Bereinigen Sie nicht benötigte Dateien.' : null
        ];
    } else {
        debugLog("Could not determine disk space", 'WARN');
        $tests[] = [
            'category' => 'Dateisystem',
            'name' => 'Festplattenspeicher',
            'status' => 'info',
            'message' => 'Konnte nicht ermittelt werden',
            'critical' => false
        ];
    }
    
    // Test 5d: Check umask
    debugLog("Test 5d: Checking umask settings", 'INFO');
    $originalUmask = umask();
    $umaskOctal = sprintf('%04o', $originalUmask);
    debugLog("Current umask: $umaskOctal", 'INFO');
    
    // Umask should typically be 0022 or less restrictive for web applications
    // 0027 allows owner full access, group read/execute, and others no access
    $maxRecommendedUmask = 0027;
    $umaskOk = $originalUmask <= $maxRecommendedUmask;
    
    $tests[] = [
        'category' => 'Dateisystem',
        'name' => 'Umask-Einstellung',
        'status' => $umaskOk ? 'pass' : 'warn',
        'message' => "Aktuell: $umaskOctal" . (!$umaskOk ? ' (möglicherweise zu restriktiv)' : ''),
        'critical' => false,
        'fix' => !$umaskOk ? 'Kontaktieren Sie Ihren Webhost-Support, um die Umask-Einstellung anzupassen' : null
    ];
    
    // Test 6: users.json
    $usersExists = file_exists($usersFile);
    $usersReadable = $usersExists && is_readable($usersFile);
    $tests[] = [
        'category' => 'Dateisystem',
        'name' => 'users.json Datei',
        'status' => $usersReadable ? 'pass' : 'fail',
        'message' => $usersReadable ? 'Vorhanden und lesbar' : 
                     ($usersExists ? 'Existiert aber nicht lesbar' : 'Datei nicht gefunden'),
        'critical' => true,
        'fix' => !$usersReadable ? 'Führen Sie install.php aus oder prüfen Sie Dateiberechtigungen' : null
    ];
    if (!$usersReadable) $criticalFailures++;
    
    // Test 7: Decrypt users.json
    debugLog("Test 7: Decrypting users.json", 'INFO');
    if ($configValid && $usersReadable) {
        try {
            // Load Encryption class to use the proper decrypt method
            $encryptionFile = __DIR__ . '/src/php/encryption.php';
            debugLog("Loading encryption class from: $encryptionFile", 'INFO');
            if (!file_exists($encryptionFile)) {
                throw new Exception('Encryption class file not found: ' . $encryptionFile);
            }
            require_once $encryptionFile;
            debugLog("Encryption class loaded successfully", 'INFO');
            
            $encryptedData = file_get_contents($usersFile);
            $dataLength = strlen($encryptedData);
            debugLog("Read encrypted data, length: $dataLength bytes", 'INFO');
            debugLog("First 100 chars of encrypted data: " . substr($encryptedData, 0, 100), 'INFO');
            debugLog("Data contains '::': " . (strpos($encryptedData, '::') !== false ? 'yes (position ' . strpos($encryptedData, '::') . ')' : 'no'), 'INFO');
            
            // Clear any existing OpenSSL errors (with safety limit)
            $errorClearCount = 0;
            $maxErrorClearAttempts = 100;
            while (openssl_error_string() !== false && $errorClearCount < $maxErrorClearAttempts) {
                $errorClearCount++;
            }
            if ($errorClearCount > 0) {
                debugLog("Cleared $errorClearCount OpenSSL error(s) before starting", 'INFO');
            }
            
            debugLog("Attempting decryption with Encryption::decrypt()...", 'INFO');
            
            // Use Encryption::decrypt() which handles both old and new formats
            $decrypted = Encryption::decrypt($encryptedData);
            
            // Check for OpenSSL errors (with safety limit)
            $errorCount = 0;
            $maxErrorCheck = 50;
            $opensslError = openssl_error_string();
            if ($opensslError !== false) {
                debugLog("OpenSSL error detected: $opensslError", 'ERROR');
                while (($err = openssl_error_string()) !== false && $errorCount < $maxErrorCheck) {
                    debugLog("Additional OpenSSL error: $err", 'ERROR');
                    $errorCount++;
                }
            }
            
            if ($decrypted !== false) {
                debugLog("Decryption successful, decrypted data length: " . strlen($decrypted) . " bytes", 'INFO');
                debugLog("First 200 chars of decrypted data: " . substr($decrypted, 0, 200), 'INFO');
                
                $users = json_decode($decrypted, true);
                $jsonError = json_last_error();
                if ($jsonError !== JSON_ERROR_NONE) {
                    debugLog("JSON decode error: " . json_last_error_msg() . " (code: $jsonError)", 'ERROR');
                }
                
                $decryptSuccess = is_array($users) && count($users) > 0;
                debugLog("JSON parsing " . ($decryptSuccess ? 'successful' : 'failed'), $decryptSuccess ? 'INFO' : 'ERROR');
                if ($decryptSuccess) {
                    debugLog("Found " . count($users) . " user(s)", 'INFO');
                    foreach ($users as $i => $user) {
                        debugLog("User $i: username=" . ($user['username'] ?? 'N/A') . ", role=" . ($user['role'] ?? 'N/A'), 'INFO');
                    }
                }
                
                $tests[] = [
                    'category' => 'Verschlüsselung',
                    'name' => 'users.json entschlüsseln',
                    'status' => $decryptSuccess ? 'pass' : 'fail',
                    'message' => $decryptSuccess ? count($users) . ' Benutzer gefunden' : 'JSON-Parsing fehlgeschlagen',
                    'critical' => true
                ];
                
                if ($decryptSuccess) {
                    // Check if admin user exists
                    $adminExists = false;
                    foreach ($users as $user) {
                        if ($user['role'] === 'admin') {
                            $adminExists = true;
                            debugLog("Admin user found: " . $user['username'], 'INFO');
                            break;
                        }
                    }
                    
                    if (!$adminExists) {
                        debugLog("WARNING: No admin user found in decrypted data", 'WARN');
                    }
                    
                    $tests[] = [
                        'category' => 'Benutzerverwaltung',
                        'name' => 'Administrator-Benutzer',
                        'status' => $adminExists ? 'pass' : 'warn',
                        'message' => $adminExists ? 'Gefunden' : 'Kein Admin-Benutzer vorhanden',
                        'critical' => false
                    ];
                } else {
                    $criticalFailures++;
                }
            } else {
                debugLog("Decryption returned false", 'ERROR');
                $tests[] = [
                    'category' => 'Verschlüsselung',
                    'name' => 'Entschlüsselung',
                    'status' => 'fail',
                    'message' => 'OpenSSL-Entschlüsselung fehlgeschlagen',
                    'critical' => true,
                    'fix' => 'Datei möglicherweise beschädigt - führen Sie install.php erneut aus'
                ];
                $criticalFailures++;
            }
        } catch (Exception $e) {
            debugLog("Exception during decryption: " . $e->getMessage(), 'ERROR');
            debugLog("Exception trace: " . $e->getTraceAsString(), 'ERROR');
            
            // Capture any OpenSSL errors (with safety limit)
            $errorCount = 0;
            $maxErrorCheck = 50;
            while (($opensslError = openssl_error_string()) !== false && $errorCount < $maxErrorCheck) {
                debugLog("OpenSSL error in exception handler: $opensslError", 'ERROR');
                $errorCount++;
            }
            
            $tests[] = [
                'category' => 'Verschlüsselung',
                'name' => 'Entschlüsselung',
                'status' => 'fail',
                'message' => 'Fehler: ' . $e->getMessage(),
                'critical' => true
            ];
            $criticalFailures++;
        }
    }
    
    // Test 8: Session functionality
    $sessionActive = session_status() === PHP_SESSION_ACTIVE;
    $_SESSION['test_' . time()] = 'test_value';
    $sessionWorks = $sessionActive && isset($_SESSION['test_' . time()]);
    
    $tests[] = [
        'category' => 'Sessions',
        'name' => 'Session-Funktionalität',
        'status' => $sessionWorks ? 'pass' : 'fail',
        'message' => $sessionWorks ? 'Session-ID: ' . substr(session_id(), 0, 10) . '...' : 'Session funktioniert nicht',
        'critical' => true,
        'fix' => !$sessionWorks ? 'Prüfen Sie session.save_path und Berechtigungen' : null
    ];
    if (!$sessionWorks) $criticalFailures++;
    
    // Test 9: Session save path
    $sessionPath = session_save_path();
    $sessionPathWritable = !empty($sessionPath) && is_writable($sessionPath);
    
    $tests[] = [
        'category' => 'Sessions',
        'name' => 'Session-Speicherpfad',
        'status' => $sessionPathWritable ? 'pass' : 'warn',
        'message' => $sessionPathWritable ? $sessionPath : (empty($sessionPath) ? 'Standard (tmp)' : $sessionPath . ' (nicht beschreibbar)'),
        'critical' => false,
        'fix' => !$sessionPathWritable && !empty($sessionPath) ? 'Kontaktieren Sie Ihren Webhost-Support bezüglich Session-Speicherpfad-Berechtigungen' : null
    ];
    
    // Test 9a: Check session cookie settings
    $cookieSecure = ini_get('session.cookie_secure');
    $cookieHttponly = ini_get('session.cookie_httponly');
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
               (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    
    debugLog("Session cookie_secure: $cookieSecure, HTTPS: " . ($isHttps ? 'yes' : 'no'), 'INFO');
    debugLog("Session cookie_httponly: $cookieHttponly", 'INFO');
    
    // Warn if secure cookie is enabled but not using HTTPS
    $cookieConfigOk = true;
    $cookieMessage = "HttpOnly: " . ($cookieHttponly ? 'aktiviert' : 'deaktiviert') . ", Secure: " . ($cookieSecure ? 'aktiviert' : 'deaktiviert');
    
    if ($cookieSecure && !$isHttps) {
        $cookieConfigOk = false;
        $cookieMessage .= " ⚠️ Warnung: Secure-Flag ohne HTTPS";
    }
    
    $tests[] = [
        'category' => 'Sessions',
        'name' => 'Session-Cookie-Konfiguration',
        'status' => $cookieConfigOk ? 'pass' : 'warn',
        'message' => $cookieMessage,
        'critical' => false,
        'fix' => !$cookieConfigOk ? 'Verwenden Sie HTTPS oder deaktivieren Sie session.cookie_secure' : null
    ];
    
    // Test 10: Webserver detection
    $serverSoftware = $_SERVER['SERVER_SOFTWARE'] ?? 'Unbekannt';
    $isNginx = stripos($serverSoftware, 'nginx') !== false;
    $isApache = stripos($serverSoftware, 'apache') !== false;
    
    $tests[] = [
        'category' => 'Webserver',
        'name' => 'Server-Software',
        'status' => 'info',
        'message' => $serverSoftware,
        'critical' => false
    ];
    
    // Detect SAPI type
    $sapi = php_sapi_name();
    $isFPM = $sapi === 'fpm-fcgi' || $sapi === 'cgi-fcgi';
    
    if ($isNginx) {
        // Nginx-specific tests
        $tests[] = [
            'category' => 'Webserver',
            'name' => 'PHP-FPM',
            'status' => $isFPM ? 'pass' : 'warn',
            'message' => "SAPI: $sapi" . ($isFPM ? ' (FPM aktiv)' : ''),
            'critical' => false,
            'fix' => !$isFPM ? 'Nginx sollte PHP-FPM verwenden' : null
        ];
    } elseif ($isApache) {
        // Apache-specific tests
        $isApacheModule = $sapi === 'apache2handler' || $sapi === 'apache' || $sapi === 'litespeed';
        $isCGI = $sapi === 'cgi' || $sapi === 'cgi-fcgi' || $sapi === 'fpm-fcgi';
        
        $tests[] = [
            'category' => 'Webserver',
            'name' => 'PHP Handler',
            'status' => ($isApacheModule || $isCGI) ? 'pass' : 'info',
            'message' => "SAPI: $sapi" . ($isApacheModule ? ' (Apache Module)' : ($isCGI ? ' (CGI/FPM)' : '')),
            'critical' => false
        ];
    }
    
    // Test 11: Auth class availability
    $authFile = __DIR__ . '/src/php/auth.php';
    $authExists = file_exists($authFile);
    
    $tests[] = [
        'category' => 'Anwendung',
        'name' => 'Auth-Klasse',
        'status' => $authExists ? 'pass' : 'fail',
        'message' => $authExists ? 'src/php/auth.php vorhanden' : 'Datei fehlt',
        'critical' => true,
        'fix' => !$authExists ? 'Installation ist unvollständig' : null
    ];
    if (!$authExists) $criticalFailures++;
    
    // Test 12: File permissions
    if ($configExists) {
        $configPerms = substr(sprintf('%o', fileperms($configFile)), -4);
        $configOwnedByWebserver = is_writable($configFile);
        
        $tests[] = [
            'category' => 'Dateisystem',
            'name' => 'config.php Berechtigungen',
            'status' => 'info',
            'message' => "Modus: $configPerms" . ($configOwnedByWebserver ? ' (beschreibbar)' : ''),
            'critical' => false
        ];
    }
    
    // Test 13: Try actual Auth login (if possible)
    if ($authExists && $configValid && $usersReadable) {
        // This test is skipped in standalone mode to avoid session conflicts
        $tests[] = [
            'category' => 'Anwendung',
            'name' => 'Login-Test',
            'status' => 'info',
            'message' => 'Nur über install.php?step=4&diagnose=run verfügbar',
            'critical' => false
        ];
    }
    
    // Test 14: Check nginx/Apache configuration
    debugLog("Test 14: Checking webserver configuration", 'INFO');
    $documentRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
    $scriptFilename = $_SERVER['SCRIPT_FILENAME'] ?? '';
    
    if ($isNginx) {
        // Check for common nginx issues
        $pathInfo = $_SERVER['PATH_INFO'] ?? '';
        $fastcgiScriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        
        debugLog("Document Root: $documentRoot", 'INFO');
        debugLog("Script Filename: $scriptFilename", 'INFO');
        debugLog("FastCGI Script Name: $fastcgiScriptName", 'INFO');
        debugLog("PATH_INFO: " . ($pathInfo ?: 'not set'), 'INFO');
        
        $tests[] = [
            'category' => 'Webserver',
            'name' => 'Nginx Document Root',
            'status' => 'info',
            'message' => $documentRoot ?: 'Nicht gesetzt',
            'critical' => false
        ];
    }
    
    // Test 15: Check PHP-FPM configuration
    if ($isFPM) {
        debugLog("Test 15: Checking PHP-FPM configuration", 'INFO');
        
        // Get PHP-FPM pool name if available
        $poolName = getenv('PHP_POOL_NAME') ?: 'Unknown';
        debugLog("PHP-FPM Pool: $poolName", 'INFO');
        
        // Check important PHP-FPM settings
        $maxExecutionTime = ini_get('max_execution_time');
        $memoryLimit = ini_get('memory_limit');
        $postMaxSize = ini_get('post_max_size');
        $uploadMaxFilesize = ini_get('upload_max_filesize');
        
        debugLog("max_execution_time: $maxExecutionTime", 'INFO');
        debugLog("memory_limit: $memoryLimit", 'INFO');
        debugLog("post_max_size: $postMaxSize", 'INFO');
        debugLog("upload_max_filesize: $uploadMaxFilesize", 'INFO');
        
        $tests[] = [
            'category' => 'PHP-FPM',
            'name' => 'PHP Limits',
            'status' => 'info',
            'message' => "Memory: $memoryLimit, Execution: {$maxExecutionTime}s",
            'critical' => false
        ];
    }
    
    // Test 16: Check error log locations and readability (webserver-specific)
    debugLog("Test 16: Checking error log locations", 'INFO');
    $phpErrorLog = ini_get('error_log');
    debugLog("PHP error_log setting: " . ($phpErrorLog ?: 'not set'), 'INFO');
    
    $webserverErrorLog = null;
    $phpProcessLog = null;
    
    // Check logs based on detected webserver
    if ($isNginx) {
        // Try common nginx error log locations
        debugLog("Checking for nginx error logs", 'INFO');
        $possibleNginxLogs = [
            '/var/log/nginx/error.log',
            '/var/log/nginx/' . ($_SERVER['SERVER_NAME'] ?? 'localhost') . '_error.log',
            '/usr/local/nginx/logs/error.log'
        ];
        
        foreach ($possibleNginxLogs as $logPath) {
            if (file_exists($logPath) && is_readable($logPath)) {
                $webserverErrorLog = $logPath;
                debugLog("Found readable nginx error log: $logPath", 'INFO');
                break;
            }
        }
        
        if (!$webserverErrorLog) {
            debugLog("No readable nginx error log found in common locations", 'WARN');
        }
        
        $tests[] = [
            'category' => 'Logs',
            'name' => 'Nginx Error Log',
            'status' => $webserverErrorLog ? 'pass' : 'warn',
            'message' => $webserverErrorLog ?: 'Nicht lesbar oder nicht gefunden',
            'critical' => false
        ];
        
        // For nginx, also check PHP-FPM logs
        debugLog("Checking for PHP-FPM logs", 'INFO');
        $possibleFpmLogs = [
            '/var/log/php-fpm/error.log',
            '/var/log/php8.5-fpm.log',
            '/var/log/php8.3-fpm.log',
            '/var/log/php8.2-fpm.log',
            '/var/log/php-fpm/www-error.log',
            '/var/log/php7.4-fpm.log'
        ];
        
        foreach ($possibleFpmLogs as $logPath) {
            if (file_exists($logPath) && is_readable($logPath)) {
                $phpProcessLog = $logPath;
                debugLog("Found readable PHP-FPM error log: $logPath", 'INFO');
                break;
            }
        }
        
        if (!$phpProcessLog && $phpErrorLog && file_exists($phpErrorLog)) {
            $phpProcessLog = $phpErrorLog;
            debugLog("Using PHP error_log: $phpErrorLog", 'INFO');
        }
        
        $tests[] = [
            'category' => 'Logs',
            'name' => 'PHP-FPM Error Log',
            'status' => $phpProcessLog ? 'pass' : 'warn',
            'message' => $phpProcessLog ?: 'Nicht lesbar oder nicht gefunden',
            'critical' => false
        ];
    } elseif ($isApache) {
        // Try common Apache error log locations
        debugLog("Checking for Apache error logs", 'INFO');
        $possibleApacheLogs = [
            '/var/log/apache2/error.log',
            '/var/log/httpd/error_log',
            '/var/log/apache2/' . ($_SERVER['SERVER_NAME'] ?? 'localhost') . '_error.log',
            '/usr/local/apache2/logs/error_log',
            '/var/log/httpd-error.log'
        ];
        
        foreach ($possibleApacheLogs as $logPath) {
            if (file_exists($logPath) && is_readable($logPath)) {
                $webserverErrorLog = $logPath;
                debugLog("Found readable Apache error log: $logPath", 'INFO');
                break;
            }
        }
        
        if (!$webserverErrorLog) {
            debugLog("No readable Apache error log found in common locations", 'WARN');
        }
        
        $tests[] = [
            'category' => 'Logs',
            'name' => 'Apache Error Log',
            'status' => $webserverErrorLog ? 'pass' : 'warn',
            'message' => $webserverErrorLog ?: 'Nicht lesbar oder nicht gefunden',
            'critical' => false
        ];
        
        // For Apache, check PHP error log
        if ($phpErrorLog && file_exists($phpErrorLog)) {
            $phpProcessLog = $phpErrorLog;
            debugLog("Found PHP error_log: $phpErrorLog", 'INFO');
        }
        
        $tests[] = [
            'category' => 'Logs',
            'name' => 'PHP Error Log',
            'status' => $phpProcessLog ? 'pass' : 'warn',
            'message' => $phpProcessLog ?: 'Nicht lesbar oder nicht gefunden',
            'critical' => false
        ];
    } else {
        // Unknown webserver - just check PHP error log
        debugLog("Unknown webserver - checking PHP error log only", 'INFO');
        if ($phpErrorLog && file_exists($phpErrorLog)) {
            $phpProcessLog = $phpErrorLog;
            debugLog("Found PHP error_log: $phpErrorLog", 'INFO');
        }
        
        $tests[] = [
            'category' => 'Logs',
            'name' => 'Webserver Error Log',
            'status' => 'info',
            'message' => 'Webserver nicht erkannt - Logs nicht verfügbar',
            'critical' => false
        ];
        
        $tests[] = [
            'category' => 'Logs',
            'name' => 'PHP Error Log',
            'status' => $phpProcessLog ? 'pass' : 'warn',
            'message' => $phpProcessLog ?: 'Nicht lesbar oder nicht gefunden',
            'critical' => false
        ];
    }
    
    // Test 17: Check file ownership and permissions
    debugLog("Test 17: Checking file ownership and permissions", 'INFO');
    $currentUser = get_current_user();
    $currentUid = getmyuid();
    $currentGid = getmygid();
    
    debugLog("Script runs as: $currentUser (UID: $currentUid, GID: $currentGid)", 'INFO');
    
    // Check web server user (common names)
    $webserverUsers = ['www-data', 'nginx', 'apache', 'httpd'];
    $detectedWebUser = null;
    $detectedWebUserInfo = null;
    
    if (function_exists('posix_getpwnam')) {
        foreach ($webserverUsers as $user) {
            $userInfo = posix_getpwnam($user);
            if ($userInfo !== false) {
                $detectedWebUser = $user;
                $detectedWebUserInfo = $userInfo;
                debugLog("Detected webserver user: $user (UID: {$userInfo['uid']})", 'INFO');
                break;
            }
        }
    } else {
        debugLog("posix_getpwnam() not available - cannot detect webserver user", 'WARN');
    }
    
    if ($configExists) {
        $configOwner = fileowner($configFile);
        $configGroup = filegroup($configFile);
        $configPerms = substr(sprintf('%o', fileperms($configFile)), -4);
        
        debugLog("config.php owner UID: $configOwner, GID: $configGroup, perms: $configPerms", 'INFO');
        
        $webUserUid = -1;
        if ($detectedWebUserInfo) {
            $webUserUid = $detectedWebUserInfo['uid'];
        }
        $ownerMatch = ($configOwner === $currentUid || $configOwner === $webUserUid);
        
        $tests[] = [
            'category' => 'Dateisystem',
            'name' => 'config.php Besitzer',
            'status' => $ownerMatch ? 'pass' : 'warn',
            'message' => "UID: $configOwner, Perms: $configPerms" . (!$ownerMatch ? ' (Besitzer stimmt nicht mit Web-User überein)' : ''),
            'critical' => false
        ];
    }
    
    debugLog("Test 18: Testing DNS resolution", 'INFO');
    $dnsTestHosts = [
        'tile.openstreetmap.org',
        'nominatim.openstreetmap.org',
        'router.project-osrm.org',
        'unpkg.com'
    ];
    
    $dnsResults = [];
    foreach ($dnsTestHosts as $host) {
        $resolved = @gethostbyname($host);
        $success = ($resolved !== $host && filter_var($resolved, FILTER_VALIDATE_IP));
        $dnsResults[$host] = ['success' => $success, 'ip' => $success ? $resolved : 'FAILED'];
        debugLog("DNS resolution for $host: " . ($success ? "OK ($resolved)" : "FAILED"), $success ? 'INFO' : 'ERROR');
    }
    
    $allDnsOk = array_reduce($dnsResults, function($carry, $item) {
        return $carry && $item['success'];
    }, true);
    
    $dnsMessage = $allDnsOk ? 'Alle Hosts erfolgreich aufgelöst' : 'Einige Hosts konnten nicht aufgelöst werden';
    if (!$allDnsOk) {
        $failedHosts = array_filter($dnsResults, function($item) { return !$item['success']; });
        $dnsMessage .= ': ' . implode(', ', array_keys($failedHosts));
    }
    
    $tests[] = [
        'category' => 'Netzwerk',
        'name' => 'DNS Auflösung',
        'status' => $allDnsOk ? 'pass' : 'fail',
        'message' => $dnsMessage,
        'critical' => false,
        'fix' => !$allDnsOk ? 'Kontaktieren Sie Ihren Webhost-Support, um die Netzwerk-Konfiguration oder Firewall-Einstellungen zu überprüfen' : null
    ];
    
    // Test 19: External API Connectivity (Map Dependencies)
    debugLog("Test 19: Testing external API connectivity for map features", 'INFO');
    
    $apiTests = [
        [
            'name' => 'OpenStreetMap Tiles',
            'url' => 'https://tile.openstreetmap.org/0/0/0.png',
            'description' => 'Kartenkacheln'
        ],
        [
            'name' => 'MapLibre GL JS',
            'url' => 'https://unpkg.com/maplibre-gl@3.6.2/dist/maplibre-gl.js',
            'description' => 'Karten-Bibliothek'
        ],
        [
            'name' => 'Nominatim Geocoding',
            'url' => 'https://nominatim.openstreetmap.org/search?format=json&q=Berlin&limit=1',
            'description' => 'Adresssuche'
        ],
        [
            'name' => 'OSRM Routing',
            'url' => 'https://router.project-osrm.org/route/v1/driving/13.388860,52.517037;13.397634,52.529407?overview=false',
            'description' => 'Routenberechnung'
        ]
    ];
    
    $apiConnectivityOk = true;
    foreach ($apiTests as $test) {
        $testUrl = $test['url'];
        $testName = $test['name'];
        $testDescription = $test['description'];
        
        debugLog("Testing connectivity to $testName ($testUrl)", 'INFO');
        
        // Check if curl is available
        if (!function_exists('curl_init')) {
            debugLog("curl extension not available - cannot test $testName", 'WARN');
            $tests[] = [
                'category' => 'Karten-Funktionalität',
                'name' => $testName,
                'status' => 'warn',
                'message' => 'curl-Extension nicht verfügbar - Test übersprungen',
                'critical' => false
            ];
            continue;
        }
        
        $ch = curl_init($testUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Feuerwehr-App-Diagnostics/1.0');
        // Use GET request instead of HEAD for better API compatibility (especially Nominatim)
        curl_setopt($ch, CURLOPT_NOBODY, false);
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        $curlErrno = curl_errno($ch);
        curl_close($ch);
        
        $success = ($httpCode >= 200 && $httpCode < 400);
        
        if ($success) {
            debugLog("$testName: OK (HTTP $httpCode)", 'INFO');
            $tests[] = [
                'category' => 'Karten-Funktionalität',
                'name' => $testName,
                'status' => 'pass',
                'message' => "$testDescription erreichbar (HTTP $httpCode)",
                'critical' => false
            ];
        } else {
            debugLog("$testName: FAILED (HTTP $httpCode, curl error: $curlErrno - $curlError)", 'ERROR');
            $apiConnectivityOk = false;
            
            $errorMsg = "Nicht erreichbar";
            if ($curlErrno) {
                $errorMsg .= " (curl error $curlErrno: $curlError)";
            } elseif ($httpCode) {
                $errorMsg .= " (HTTP $httpCode)";
            }
            
            $tests[] = [
                'category' => 'Karten-Funktionalität',
                'name' => $testName,
                'status' => 'fail',
                'message' => $errorMsg,
                'critical' => false,
                'fix' => 'Firewall oder Proxy des Webhosts blockiert möglicherweise externe Verbindungen. Kontaktieren Sie Ihren Webhost-Support.'
            ];
        }
    }
    
    // Test 20: JavaScript/MapLibre Loading Test
    debugLog("Test 20: Adding JavaScript library loading information", 'INFO');
    $tests[] = [
        'category' => 'Karten-Funktionalität',
        'name' => 'JavaScript Bibliotheken',
        'status' => 'info',
        'message' => 'MapLibre GL JS muss im Browser geladen werden. Öffnen Sie die Karten-Seite und prüfen Sie die Browser-Konsole auf Fehler (F12 → Console).',
        'critical' => false
    ];
    
    // Test 21: CSP/CORS Headers Check
    debugLog("Test 21: Checking for Content Security Policy", 'INFO');
    $cspHeader = null;
    if (function_exists('apache_response_headers')) {
        $headers = apache_response_headers();
        $cspHeader = $headers['Content-Security-Policy'] ?? null;
    }
    
    if ($cspHeader) {
        debugLog("CSP header found: $cspHeader", 'INFO');
        // Check if CSP allows external resources
        $allowsExternal = (
            strpos($cspHeader, 'unpkg.com') !== false ||
            strpos($cspHeader, 'openstreetmap.org') !== false ||
            strpos($cspHeader, '*') !== false
        );
        
        $tests[] = [
            'category' => 'Karten-Funktionalität',
            'name' => 'Content Security Policy',
            'status' => $allowsExternal ? 'pass' : 'warn',
            'message' => $allowsExternal ? 'CSP erlaubt externe Ressourcen' : 'CSP könnte externe Ressourcen blockieren',
            'critical' => false,
            'fix' => !$allowsExternal ? 'CSP Header anpassen um externe Map-Ressourcen zu erlauben' : null
        ];
    } else {
        debugLog("No CSP header found", 'INFO');
        $tests[] = [
            'category' => 'Karten-Funktionalität',
            'name' => 'Content Security Policy',
            'status' => 'pass',
            'message' => 'Kein CSP Header gesetzt (externe Ressourcen erlaubt)',
            'critical' => false
        ];
    }
    
    // Test 22: SMTP Configuration and Connectivity Tests
    debugLog("Test 22: Testing SMTP configuration and connectivity", 'INFO');
    
    // Load email configuration
    $emailConfig = $config['email'] ?? [];
    
    if (!empty($emailConfig['smtp_host'])) {
        $smtpHost = $emailConfig['smtp_host'];
        $smtpPort = $emailConfig['smtp_port'] ?? 587;
        $smtpSecure = $emailConfig['smtp_secure'] ?? '';
        $smtpAuth = $emailConfig['smtp_auth'] ?? false;
        $smtpUsername = $emailConfig['smtp_username'] ?? '';
        
        debugLog("SMTP Host: $smtpHost", 'INFO');
        debugLog("SMTP Port: $smtpPort", 'INFO');
        debugLog("SMTP Secure: " . ($smtpSecure ?: 'none'), 'INFO');
        debugLog("SMTP Auth: " . ($smtpAuth ? 'yes' : 'no'), 'INFO');
        debugLog("SMTP Username: " . ($smtpUsername ?: 'none'), 'INFO');
        
        // Display SMTP configuration
        $tests[] = [
            'category' => 'E-Mail / SMTP',
            'name' => 'SMTP Konfiguration',
            'status' => 'info',
            'message' => "Host: $smtpHost, Port: $smtpPort, Verschlüsselung: " . ($smtpSecure ?: 'keine') . ", Auth: " . ($smtpAuth ? 'Ja' : 'Nein'),
            'critical' => false
        ];
        
        // Test 22a: DNS resolution for SMTP host
        debugLog("Testing DNS resolution for SMTP host: $smtpHost", 'INFO');
        $smtpIp = @gethostbyname($smtpHost);
        $dnsSuccess = ($smtpIp !== $smtpHost && filter_var($smtpIp, FILTER_VALIDATE_IP));
        debugLog("DNS resolution: " . ($dnsSuccess ? "OK ($smtpIp)" : "FAILED"), $dnsSuccess ? 'INFO' : 'ERROR');
        
        $tests[] = [
            'category' => 'E-Mail / SMTP',
            'name' => 'SMTP DNS Auflösung',
            'status' => $dnsSuccess ? 'pass' : 'fail',
            'message' => $dnsSuccess ? "Host aufgelöst zu $smtpIp" : "Konnte $smtpHost nicht auflösen",
            'critical' => false,
            'fix' => !$dnsSuccess ? 'DNS-Konfiguration prüfen oder /etc/hosts anpassen' : null
        ];
        
        // Test 22b: Socket connection to SMTP server
        if ($dnsSuccess) {
            debugLog("Testing socket connection to $smtpHost:$smtpPort", 'INFO');
            $socketSuccess = false;
            $socketError = '';
            $connectTimeout = 10;
            
            try {
                $errno = 0;
                $errstr = '';
                
                // Test connection (use ssl:// wrapper for SSL on port 465)
                $protocol = ($smtpSecure === 'ssl' && $smtpPort == 465) ? 'ssl://' : 'tcp://';
                $socket = @stream_socket_client(
                    "{$protocol}{$smtpHost}:{$smtpPort}",
                    $errno,
                    $errstr,
                    $connectTimeout,
                    STREAM_CLIENT_CONNECT
                );
                
                if ($socket) {
                    $socketSuccess = true;
                    debugLog("Socket connection successful", 'INFO');
                    
                    // Try to read SMTP greeting
                    stream_set_timeout($socket, 5);
                    $greeting = fgets($socket, 512);
                    if ($greeting) {
                        debugLog("SMTP Greeting: " . trim($greeting), 'INFO');
                        $socketError = "Verbunden. Greeting: " . trim(substr($greeting, 0, 50));
                    } else {
                        $socketError = "Verbunden, aber kein SMTP Greeting empfangen";
                    }
                    fclose($socket);
                } else {
                    $socketError = "Verbindung fehlgeschlagen: $errstr (errno: $errno)";
                    debugLog("Socket connection failed: $errstr (errno: $errno)", 'ERROR');
                }
            } catch (Exception $e) {
                $socketError = "Exception: " . $e->getMessage();
                debugLog("Socket connection exception: " . $e->getMessage(), 'ERROR');
            }
            
            $tests[] = [
                'category' => 'E-Mail / SMTP',
                'name' => 'SMTP Server Verbindung',
                'status' => $socketSuccess ? 'pass' : 'fail',
                'message' => $socketError ?: 'Verbindung erfolgreich',
                'critical' => false,
                'fix' => !$socketSuccess ? 'Prüfen Sie Firewall, Server-Erreichbarkeit und Port' : null
            ];
            
            // Test 22c: TLS/SSL capability test
            if ($socketSuccess && !empty($smtpSecure)) {
                debugLog("Testing TLS/SSL capability", 'INFO');
                $tlsSuccess = false;
                $tlsError = '';
                
                try {
                    $errno = 0;
                    $errstr = '';
                    
                    if ($smtpSecure === 'ssl') {
                        // For SSL, connection should already be encrypted
                        $tlsSuccess = true;
                        $tlsError = "SSL Verbindung auf Port $smtpPort erfolgreich";
                    } else if ($smtpSecure === 'tls') {
                        // For TLS, we need to test STARTTLS
                        $socket = @stream_socket_client(
                            "tcp://{$smtpHost}:{$smtpPort}",
                            $errno,
                            $errstr,
                            $connectTimeout
                        );
                        
                        if ($socket) {
                            stream_set_timeout($socket, 5);
                            
                            // Read greeting
                            $greeting = fgets($socket, 512);
                            
                            // Send EHLO
                            fwrite($socket, "EHLO localhost\r\n");
                            $response = '';
                            while ($line = fgets($socket, 512)) {
                                $response .= $line;
                                if (preg_match('/^250 /', $line)) break;
                            }
                            
                            // Check if STARTTLS is supported
                            if (stripos($response, 'STARTTLS') !== false) {
                                // Send STARTTLS command
                                fwrite($socket, "STARTTLS\r\n");
                                $starttlsResponse = fgets($socket, 512);
                                
                                if (preg_match('/^220/', $starttlsResponse)) {
                                    // Try to enable crypto
                                    if (@stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                                        $tlsSuccess = true;
                                        $tlsError = "STARTTLS erfolgreich aktiviert";
                                        debugLog("STARTTLS successful", 'INFO');
                                    } else {
                                        $tlsError = "STARTTLS Handshake fehlgeschlagen";
                                        debugLog("STARTTLS handshake failed", 'ERROR');
                                    }
                                } else {
                                    $tlsError = "STARTTLS Befehl nicht akzeptiert: " . trim($starttlsResponse);
                                    debugLog("STARTTLS command not accepted: " . trim($starttlsResponse), 'ERROR');
                                }
                            } else {
                                $tlsError = "Server unterstützt STARTTLS nicht";
                                debugLog("Server does not support STARTTLS", 'ERROR');
                            }
                            
                            fclose($socket);
                        } else {
                            $tlsError = "Verbindung für TLS Test fehlgeschlagen: $errstr";
                            debugLog("Connection for TLS test failed: $errstr", 'ERROR');
                        }
                    }
                } catch (Exception $e) {
                    $tlsError = "Exception: " . $e->getMessage();
                    debugLog("TLS test exception: " . $e->getMessage(), 'ERROR');
                }
                
                $tests[] = [
                    'category' => 'E-Mail / SMTP',
                    'name' => 'SMTP ' . strtoupper($smtpSecure) . ' Verschlüsselung',
                    'status' => $tlsSuccess ? 'pass' : 'fail',
                    'message' => $tlsError ?: 'Verschlüsselung fehlgeschlagen',
                    'critical' => false,
                    'fix' => !$tlsSuccess ? 'Prüfen Sie die SSL/TLS-Konfiguration des Servers' : null
                ];
            }
            
            // Test 22d: SMTP Authentication test (if credentials provided)
            if ($socketSuccess && $smtpAuth && !empty($smtpUsername)) {
                debugLog("Testing SMTP authentication", 'INFO');
                $authSuccess = false;
                $authError = '';
                
                try {
                    // Load SMTP client for full authentication test
                    require_once __DIR__ . '/src/php/smtp_client.php';
                    
                    $smtpPassword = $emailConfig['smtp_password'] ?? '';
                    
                    // We can't do a full auth test without actually sending an email
                    // Just verify the configuration is complete
                    if (!empty($smtpPassword)) {
                        $authSuccess = true;
                        $authError = "Benutzername und Passwort konfiguriert (vollständige Auth-Prüfung nur beim E-Mail-Versand)";
                        debugLog("SMTP auth credentials configured", 'INFO');
                    } else {
                        $authError = "Passwort fehlt";
                        debugLog("SMTP password missing", 'ERROR');
                    }
                } catch (Exception $e) {
                    $authError = "Exception: " . $e->getMessage();
                    debugLog("Auth test exception: " . $e->getMessage(), 'ERROR');
                }
                
                $tests[] = [
                    'category' => 'E-Mail / SMTP',
                    'name' => 'SMTP Authentifizierung',
                    'status' => $authSuccess ? 'pass' : 'warn',
                    'message' => $authError,
                    'critical' => false,
                    'fix' => !$authSuccess ? 'Prüfen Sie Benutzername und Passwort' : null
                ];
            }
        }
        
        // Test 22e: Email addresses validation
        $fromAddress = $emailConfig['from_address'] ?? '';
        $toAddress = $emailConfig['to_address'] ?? '';
        
        $fromValid = !empty($fromAddress) && filter_var($fromAddress, FILTER_VALIDATE_EMAIL);
        $toValid = !empty($toAddress) && filter_var($toAddress, FILTER_VALIDATE_EMAIL);
        
        debugLog("From address: $fromAddress, valid: " . ($fromValid ? 'yes' : 'no'), $fromValid ? 'INFO' : 'ERROR');
        debugLog("To address: $toAddress, valid: " . ($toValid ? 'yes' : 'no'), $toValid ? 'INFO' : 'ERROR');
        
        $tests[] = [
            'category' => 'E-Mail / SMTP',
            'name' => 'E-Mail Adressen',
            'status' => ($fromValid && $toValid) ? 'pass' : 'fail',
            'message' => "Von: " . ($fromValid ? $fromAddress : 'UNGÜLTIG') . ", An: " . ($toValid ? $toAddress : 'UNGÜLTIG'),
            'critical' => false,
            'fix' => (!$fromValid || !$toValid) ? 'Prüfen Sie die E-Mail-Adressen in den Einstellungen' : null
        ];
        
        // Test 22f: Check if socket functions are available
        $socketFunctionsAvailable = function_exists('stream_socket_client') && function_exists('fsockopen');
        
        $tests[] = [
            'category' => 'E-Mail / SMTP',
            'name' => 'Socket Funktionen',
            'status' => $socketFunctionsAvailable ? 'pass' : 'fail',
            'message' => $socketFunctionsAvailable ? 'Verfügbar' : 'Nicht verfügbar (allow_url_fopen deaktiviert?)',
            'critical' => false,
            'fix' => !$socketFunctionsAvailable ? 'allow_url_fopen in php.ini aktivieren' : null
        ];
        
    } else {
        // No SMTP configuration
        $tests[] = [
            'category' => 'E-Mail / SMTP',
            'name' => 'SMTP Konfiguration',
            'status' => 'warn',
            'message' => 'Keine SMTP-Konfiguration vorhanden. E-Mail-Versand nutzt PHP mail() Funktion.',
            'critical' => false,
            'fix' => 'Konfigurieren Sie SMTP-Einstellungen für zuverlässigen E-Mail-Versand'
        ];
    }
    
    return [
        'tests' => $tests,
        'criticalFailures' => $criticalFailures,
        'totalTests' => count($tests),
        'timestamp' => date('Y-m-d H:i:s'),
        'phpVersion' => PHP_VERSION,
        'webserver' => $serverSoftware,
        'isNginx' => $isNginx,
        'isApache' => $isApache,
        'webserverErrorLog' => $webserverErrorLog ?? null,
        'phpProcessLog' => $phpProcessLog ?? null,
        'detectedWebUser' => $detectedWebUser ?? null,
        'apiConnectivityOk' => $apiConnectivityOk,
        'smtpConfigured' => !empty($emailConfig['smtp_host']) && !empty($emailConfig['smtp_port'])
    ];
}

$results = runAllTests();

debugLog("=== Diagnostic tests completed ===", 'INFO');
debugLog("Total tests: " . $results['totalTests'], 'INFO');
debugLog("Critical failures: " . $results['criticalFailures'], 'INFO');
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnose - Feuerwehr App</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #d32f2f 0%, #c62828 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 28px;
            font-weight: 500;
            margin-bottom: 10px;
        }
        
        .content {
            padding: 40px 30px;
        }
        
        .summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .summary-card {
            background: #f5f5f5;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        
        .summary-card .number {
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .summary-card .label {
            font-size: 14px;
            color: #666;
        }
        
        .summary-card.success .number { color: #4caf50; }
        .summary-card.error .number { color: #f44336; }
        .summary-card.info .number { color: #2196f3; }
        
        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
            border-left: 4px solid #4caf50;
        }
        
        .alert-error {
            background: #ffebee;
            color: #c62828;
            border-left: 4px solid #f44336;
        }
        
        .tests-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        .tests-table th,
        .tests-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .tests-table th {
            background: #f5f5f5;
            font-weight: 500;
            color: #333;
        }
        
        .tests-table tr:hover {
            background: #f9f9f9;
        }
        
        .category-header {
            background: #e3f2fd !important;
            font-weight: 700;
            color: #1565c0;
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-pass {
            background: #e8f5e9;
            color: #2e7d32;
        }
        
        .status-fail {
            background: #ffebee;
            color: #c62828;
        }
        
        .status-warn {
            background: #fff3e0;
            color: #e65100;
        }
        
        .status-info {
            background: #e3f2fd;
            color: #1565c0;
        }
        
        .fix-hint {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
            padding: 8px;
            background: #fff8e1;
            border-radius: 4px;
            display: inline-block;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
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
            justify-content: center;
        }
        
        code {
            background: #f5f5f5;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚒 Feuerwehr App - Diagnose</h1>
            <p>Systemprüfung für Login-Probleme</p>
            <p style="font-size: 12px; opacity: 0.9; margin-top: 10px;">
                PHP <?php echo htmlspecialchars($results['phpVersion']); ?> | 
                <?php echo htmlspecialchars($results['webserver']); ?>
            </p>
        </div>
        
        <div class="content">
            <div class="summary">
                <div class="summary-card info">
                    <div class="number"><?php echo $results['totalTests']; ?></div>
                    <div class="label">Tests durchgeführt</div>
                </div>
                <div class="summary-card <?php echo $results['criticalFailures'] === 0 ? 'success' : 'error'; ?>">
                    <div class="number"><?php echo $results['criticalFailures']; ?></div>
                    <div class="label">Kritische Fehler</div>
                </div>
                <div class="summary-card info">
                    <div class="number"><?php echo $results['timestamp']; ?></div>
                    <div class="label">Zeitpunkt</div>
                </div>
            </div>
            
            <?php if ($results['criticalFailures'] === 0): ?>
                <div class="alert alert-success">
                    <span class="material-icons">check_circle</span>
                    <div>
                        <strong>Alle kritischen Tests bestanden!</strong><br>
                        Das System sollte funktionsfähig sein. Wenn Login-Probleme bestehen, überprüfen Sie die Warnungen unten.
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-error">
                    <span class="material-icons">error</span>
                    <div>
                        <strong><?php echo $results['criticalFailures']; ?> kritische Fehler gefunden!</strong><br>
                        Beheben Sie die unten markierten Probleme, bevor Sie fortfahren.
                    </div>
                </div>
            <?php endif; ?>
            
            <?php 
            // Display redirect error information if present
            $redirectError = isset($_GET['error']) ? $_GET['error'] : null;
            $redirectDetails = isset($_GET['details']) ? $_GET['details'] : null;
            
            if ($redirectError): 
                $errorTitles = [
                    'data_dir_create_failed' => 'Datenverzeichnis konnte nicht erstellt werden',
                    'data_dir_not_directory' => 'Datenverzeichnis-Pfad ist kein Verzeichnis',
                    'data_dir_not_writable' => 'Datenverzeichnis ist nicht beschreibbar'
                ];
                $errorTitle = isset($errorTitles[$redirectError]) ? $errorTitles[$redirectError] : 'Unbekannter Fehler';
            ?>
                <div class="alert alert-error" style="margin-top: 20px;">
                    <span class="material-icons">report_problem</span>
                    <div>
                        <strong style="color: #d32f2f;">Grund für die Weiterleitung hierher:</strong><br>
                        <strong><?php echo htmlspecialchars($errorTitle); ?></strong>
                        <?php if ($redirectDetails): ?>
                            <div style="margin-top: 10px; padding: 10px; background: rgba(255,255,255,0.7); border-radius: 4px; font-family: monospace; font-size: 12px;">
                                <?php echo htmlspecialchars($redirectDetails); ?>
                            </div>
                        <?php endif; ?>
                        <p style="margin-top: 10px; font-size: 14px;">
                            Die Anwendung hat Sie automatisch zu dieser Diagnoseseite weitergeleitet, um das Problem zu identifizieren.
                            Bitte überprüfen Sie die Testergebnisse unten für weitere Details und Lösungsvorschläge.
                        </p>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (isset($results['apiConnectivityOk']) && !$results['apiConnectivityOk']): ?>
                <div class="alert" style="background: #fff3e0; border-left-color: #ff9800; color: #e65100; margin-top: 20px;">
                    <span class="material-icons">warning</span>
                    <div>
                        <strong>⚠️ Karten-Funktionalität beeinträchtigt</strong><br>
                        Einige externe APIs für die Karten-Funktion sind nicht erreichbar. Die Karte wird möglicherweise nicht korrekt angezeigt.
                        <ul style="margin: 10px 0 0 20px;">
                            <li><strong>Ursachen:</strong> Firewall des Webhosts, fehlender Internetzugang, DNS-Probleme</li>
                            <li><strong>Lösung:</strong> Kontaktieren Sie Ihren Webhost-Support bezüglich externer API-Zugriffe</li>
                            <li><strong>Browser-Test:</strong> Öffnen Sie die Browser-Konsole (F12) auf der Karten-Seite für detaillierte Fehler</li>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (isset($results['smtpConfigured']) && $results['smtpConfigured']): ?>
                <div class="alert" style="background: #e8f5e9; border-left-color: #4caf50; color: #1b5e20; margin-top: 20px;">
                    <span class="material-icons">email</span>
                    <div>
                        <strong>📧 SMTP Diagnose verfügbar</strong><br>
                        Die SMTP-Konfiguration wurde getestet. Prüfen Sie die Test-Ergebnisse unten in der Kategorie "E-Mail / SMTP".
                        <ul style="margin: 10px 0 0 20px;">
                            <li>DNS-Auflösung des SMTP-Servers</li>
                            <li>Socket-Verbindung zum SMTP-Server</li>
                            <li>TLS/SSL-Verschlüsselung</li>
                            <li>Authentifizierungs-Konfiguration</li>
                            <li>E-Mail-Adressen-Validierung</li>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>
            
            <h2 style="margin: 30px 0 15px 0; font-size: 20px;">Test-Ergebnisse</h2>
            
            <table class="tests-table">
                <thead>
                    <tr>
                        <th>Test</th>
                        <th>Status</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $currentCategory = '';
                    foreach ($results['tests'] as $test): 
                        if ($currentCategory !== $test['category']):
                            $currentCategory = $test['category'];
                    ?>
                        <tr>
                            <td colspan="3" class="category-header"><?php echo htmlspecialchars($test['category']); ?></td>
                        </tr>
                    <?php endif; ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($test['name']); ?></strong></td>
                            <td>
                                <span class="status-badge status-<?php echo $test['status']; ?>">
                                    <?php
                                    $icons = [
                                        'pass' => 'check_circle',
                                        'fail' => 'error',
                                        'warn' => 'warning',
                                        'info' => 'info'
                                    ];
                                    ?>
                                    <span class="material-icons" style="font-size: 16px;"><?php echo $icons[$test['status']]; ?></span>
                                    <?php echo strtoupper($test['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($test['message']); ?>
                                <?php if (isset($test['fix'])): ?>
                                    <div class="fix-hint">
                                        <strong>💡 Lösung:</strong> <?php echo htmlspecialchars($test['fix']); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <?php
            // Generate CSRF token if not exists
            if (!isset($_SESSION['csrf_token'])) {
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            }
            $csrfToken = $_SESSION['csrf_token'];
            
            // Handle PHP-based fixes
            $fixAttempted = false;
            $fixSuccess = false;
            $fixMessage = '';
            
            if (isset($_POST['fix_action'])) {
                // Verify CSRF token
                if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                    $fixAttempted = true;
                    $fixSuccess = false;
                    $fixMessage = 'Sicherheitsfehler: Ungültiges Token. Bitte versuchen Sie es erneut.';
                } else {
                    $fixAttempted = true;
                    $action = $_POST['fix_action'];
                    
                    if ($action === 'create_data_dir') {
                        $dataDir = __DIR__ . '/data';
                        clearstatcache(true, $dataDir);
                        
                        // Using is_dir() as primary check since it's more reliable for directories
                        if (!is_dir($dataDir)) {
                            $result = mkdir($dataDir, 0755, true);
                            if ($result) {
                                $fixSuccess = true;
                                $fixMessage = 'data/ Verzeichnis erfolgreich erstellt!';
                                error_log("diagnose.php: Successfully created data directory");
                            } else {
                                // mkdir failed, check if directory now exists (race condition)
                                clearstatcache(true, $dataDir);
                                if (is_dir($dataDir)) {
                                    $fixSuccess = true;
                                    $fixMessage = 'data/ Verzeichnis existiert bereits.';
                                } else {
                                    $lastError = error_get_last();
                                    $errorMsg = $lastError ? $lastError['message'] : 'Unknown error';
                                    error_log("diagnose.php: Failed to create data directory: " . $errorMsg);
                                    $fixMessage = 'Fehler beim Erstellen des data/ Verzeichnisses. Bitte setzen Sie die Berechtigungen manuell über FTP.';
                                }
                            }
                        } else {
                            $fixMessage = 'data/ Verzeichnis existiert bereits.';
                        }
                    } elseif ($action === 'fix_permissions') {
                        $dataDir = __DIR__ . '/data';
                        if (file_exists($dataDir)) {
                            $result = chmod($dataDir, 0755);
                            if ($result) {
                                $fixSuccess = true;
                                $fixMessage = 'Berechtigungen für data/ Verzeichnis erfolgreich gesetzt!';
                                error_log("diagnose.php: Successfully changed permissions for data directory");
                            } else {
                                $lastError = error_get_last();
                                $errorMsg = $lastError ? $lastError['message'] : 'Unknown error';
                                error_log("diagnose.php: Failed to change permissions for data directory: " . $errorMsg);
                                $fixMessage = 'Fehler beim Setzen der Berechtigungen. Bitte verwenden Sie FTP.';
                            }
                        } else {
                            $fixMessage = 'data/ Verzeichnis existiert nicht.';
                        }
                    }
                    
                    // Use session to pass message (more secure than GET parameter)
                    if ($fixAttempted) {
                        $_SESSION['fix_attempted'] = true;
                        $_SESSION['fix_success'] = $fixSuccess;
                        $_SESSION['fix_message'] = $fixMessage;
                        // Build proper absolute URL with validated host
                        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                        // Sanitize host to prevent header injection
                        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
                        $host = preg_replace('/[^a-zA-Z0-9.:-]/', '', $host);
                        $uri = strtok($_SERVER['REQUEST_URI'], '?');
                        header("Location: {$protocol}://{$host}{$uri}");
                        exit;
                    }
                }
            }
            
            // Retrieve fix result from session
            if (isset($_SESSION['fix_attempted'])) {
                $fixAttempted = $_SESSION['fix_attempted'];
                $fixSuccess = $_SESSION['fix_success'];
                $fixMessage = $_SESSION['fix_message'];
                // Clear session variables
                unset($_SESSION['fix_attempted'], $_SESSION['fix_success'], $_SESSION['fix_message']);
            }
            ?>
            
            <?php if ($fixAttempted): ?>
                <div class="alert <?php echo $fixSuccess ? 'alert-success' : 'alert-error'; ?>" style="margin: 20px 0;">
                    <span class="material-icons"><?php echo $fixSuccess ? 'check_circle' : 'error'; ?></span>
                    <div>
                        <strong><?php echo $fixSuccess ? 'Erfolg!' : 'Fehler'; ?></strong><br>
                        <?php echo htmlspecialchars($fixMessage); ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($results['criticalFailures'] > 0): ?>
                <div style="margin: 30px 0; padding: 20px; background: #fff3e0; border-radius: 8px; border-left: 4px solid #ff9800;">
                    <h3 style="margin: 0 0 15px 0; font-size: 18px; color: #e65100;">
                        <span class="material-icons" style="vertical-align: middle; font-size: 22px;">build_circle</span>
                        Automatische Fehlerbehebung
                    </h3>
                    
                    <p style="margin: 0 0 15px 0; color: #e65100;">
                        Versuchen Sie, häufige Probleme automatisch über PHP zu beheben:
                    </p>
                    
                    <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                        <?php if (!file_exists(__DIR__ . '/data')): ?>
                            <form method="POST" style="margin: 0;">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                <input type="hidden" name="fix_action" value="create_data_dir">
                                <button type="submit" class="btn btn-primary" style="cursor: pointer;">
                                    <span class="material-icons">create_new_folder</span>
                                    data/ Verzeichnis erstellen
                                </button>
                            </form>
                        <?php elseif (!is_writable(__DIR__ . '/data')): ?>
                            <form method="POST" style="margin: 0;">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                <input type="hidden" name="fix_action" value="fix_permissions">
                                <button type="submit" class="btn btn-primary" style="cursor: pointer;">
                                    <span class="material-icons">lock_open</span>
                                    Berechtigungen anpassen (via PHP)
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                    
                    <div style="margin-top: 15px; padding: 15px; background: white; border-radius: 6px;">
                        <strong style="color: #1976d2;">📁 Alternative: Manuelle Anpassung via FTP</strong><br>
                        <span style="color: #555;">
                            Verbinden Sie sich mit Ihrem FTP-Client (z.B. FileZilla) und setzen Sie die Berechtigungen:
                        </span>
                        <ol style="margin: 10px 0 0 20px; color: #555;">
                            <li>Rechtsklick auf <code>data/</code> Verzeichnis</li>
                            <li>Wählen Sie "Dateiberechtigungen" oder "Eigenschaften"</li>
                            <li>Setzen Sie den numerischen Wert auf <strong>755</strong></li>
                            <li>Aktivieren Sie "Rekursiv" (falls verfügbar)</li>
                            <li>Klicken Sie auf OK</li>
                        </ol>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="button-group">
                <a href="?" class="btn btn-secondary">
                    <span class="material-icons">refresh</span>
                    Tests wiederholen
                </a>
                <?php if ($results['criticalFailures'] === 0): ?>
                    <a href="index.php" class="btn btn-primary">
                        <span class="material-icons">login</span>
                        Zur Anmeldung
                    </a>
                <?php else: ?>
                    <a href="install.php" class="btn btn-primary">
                        <span class="material-icons">build</span>
                        Zum Installations-Wizard
                    </a>
                <?php endif; ?>
            </div>
            
            <?php if ($debugMode): ?>
                <!-- Debug Mode Output -->
                <div style="margin-top: 40px; padding-top: 30px; border-top: 3px solid #ff9800;">
                    <h2 style="margin: 0 0 15px 0; font-size: 20px; color: #ff9800;">
                        <span class="material-icons" style="vertical-align: middle; font-size: 24px;">bug_report</span>
                        Debug-Modus
                    </h2>
                    
                    <div class="alert" style="background: #fff3e0; border-left-color: #ff9800; color: #e65100;">
                        <span class="material-icons">info</span>
                        <div>
                            <strong>Debug-Informationen aktiviert</strong><br>
                            Hier sehen Sie detaillierte Informationen über alle Aktionen, Befehle und Meldungen während der Diagnose.
                            Diese Informationen sind nützlich für die Fehleranalyse.
                        </div>
                    </div>
                    
                    <div style="background: #263238; color: #aed581; padding: 20px; border-radius: 8px; font-family: 'Courier New', monospace; font-size: 12px; line-height: 1.6; max-height: 600px; overflow-y: auto; margin-top: 20px;">
                        <div style="margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #37474f; color: #80cbc4; font-weight: bold;">
                            📋 Debug-Protokoll (<?php echo count($debugLog); ?> Einträge)
                        </div>
                        <?php 
                        $startTime = !empty($debugLog) ? $debugLog[0]['time'] : microtime(true);
                        foreach ($debugLog as $entry): 
                            $elapsed = sprintf('%.4f', ($entry['time'] - $startTime));
                            $levelColors = [
                                'INFO' => '#aed581',
                                'WARN' => '#ffb74d',
                                'ERROR' => '#e57373'
                            ];
                            $color = $levelColors[$entry['level']] ?? '#90caf9';
                        ?>
                            <div style="margin-bottom: 8px; padding: 6px 0;">
                                <span style="color: #78909c;">[<?php echo $elapsed; ?>s]</span>
                                <span style="color: <?php echo $color; ?>; font-weight: bold;">[<?php echo htmlspecialchars($entry['level']); ?>]</span>
                                <span style="color: #cfd8dc;"><?php echo htmlspecialchars($entry['message']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div style="margin-top: 20px; padding: 15px; background: #e3f2fd; border-radius: 6px; border-left: 4px solid #2196f3;">
                        <strong style="color: #1565c0;">💡 Tipp:</strong>
                        <span style="color: #1976d2;">
                            Kopieren Sie diese Debug-Informationen, wenn Sie Hilfe vom Support benötigen.
                            Um den Debug-Modus zu deaktivieren, entfernen Sie <code style="background: #fff; padding: 2px 6px; border-radius: 3px; color: #d32f2f;">?debug=1</code> aus der URL.
                        </span>
                    </div>
                    
                    <div style="margin-top: 20px;">
                        <h3 style="font-size: 16px; margin-bottom: 10px; color: #666;">PHP-Informationen</h3>
                        <table class="tests-table">
                            <tr>
                                <td><strong>Session ID</strong></td>
                                <td><?php echo htmlspecialchars(session_id()); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Session Status</strong></td>
                                <td><?php echo session_status() === PHP_SESSION_ACTIVE ? 'Aktiv' : 'Inaktiv'; ?></td>
                            </tr>
                            <tr>
                                <td><strong>Session Save Path</strong></td>
                                <td><?php echo htmlspecialchars(session_save_path() ?: 'Standard (tmp)'); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Error Reporting</strong></td>
                                <td><?php echo error_reporting(); ?> (<?php echo ini_get('display_errors') ? 'anzeigen' : 'versteckt'; ?>)</td>
                            </tr>
                            <tr>
                                <td><strong>OpenSSL Version</strong></td>
                                <td><?php echo OPENSSL_VERSION_TEXT ?? 'Nicht verfügbar'; ?></td>
                            </tr>
                            <tr>
                                <td><strong>Document Root</strong></td>
                                <td><?php echo htmlspecialchars($_SERVER['DOCUMENT_ROOT'] ?? 'N/A'); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Script Filename</strong></td>
                                <td><?php echo htmlspecialchars($_SERVER['SCRIPT_FILENAME'] ?? 'N/A'); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Current User</strong></td>
                                <td><?php echo htmlspecialchars(get_current_user()); ?> (UID: <?php echo getmyuid(); ?>, GID: <?php echo getmygid(); ?>)</td>
                            </tr>
                            <tr>
                                <td><strong>Umask</strong></td>
                                <td><?php echo sprintf('%04o', umask()); ?></td>
                            </tr>
                        </table>
                    </div>
                    
                    <div style="margin-top: 20px;">
                        <h3 style="font-size: 16px; margin-bottom: 10px; color: #666;">Umgebungsvariablen (Auswahl)</h3>
                        <table class="tests-table">
                            <?php
                            $relevantVars = ['SERVER_SOFTWARE', 'SERVER_PROTOCOL', 'REQUEST_METHOD', 'QUERY_STRING', 
                                           'HTTP_USER_AGENT', 'REMOTE_ADDR', 'SERVER_ADDR', 'SERVER_PORT',
                                           'PHP_SELF', 'SCRIPT_NAME', 'REQUEST_URI'];
                            foreach ($relevantVars as $var):
                                if (isset($_SERVER[$var])):
                            ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($var); ?></strong></td>
                                    <td><?php echo htmlspecialchars($_SERVER[$var]); ?></td>
                                </tr>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </table>
                    </div>
                    
                    <?php if (!empty($results['webserverErrorLog']) || !empty($results['phpProcessLog'])): ?>
                    <div style="margin-top: 20px;">
                        <h3 style="font-size: 16px; margin-bottom: 10px; color: #666;">📄 Server-Logs (letzte 20 Zeilen)</h3>
                        
                        <?php if (!empty($results['webserverErrorLog'])): ?>
                        <div style="margin-bottom: 15px;">
                            <h4 style="font-size: 14px; margin-bottom: 5px; color: #444;">
                                <?php echo $results['isNginx'] ? 'Nginx' : ($results['isApache'] ? 'Apache' : 'Webserver'); ?> Error Log
                            </h4>
                            <div style="background: #263238; color: #cfd8dc; padding: 15px; border-radius: 6px; font-family: 'Courier New', monospace; font-size: 11px; line-height: 1.5; max-height: 300px; overflow-y: auto;">
                                <div style="color: #80cbc4; margin-bottom: 5px; font-weight: bold;">📂 <?php echo htmlspecialchars($results['webserverErrorLog']); ?></div>
                                <?php
                                $webserverLines = readLastNLines($results['webserverErrorLog'], 20);
                                
                                if (empty($webserverLines)) {
                                    echo '<div style="color: #90caf9;">Keine aktuellen Einträge oder Datei leer</div>';
                                } else {
                                    foreach ($webserverLines as $line) {
                                        $color = '#cfd8dc';
                                        if (stripos($line, 'error') !== false) $color = '#e57373';
                                        elseif (stripos($line, 'warn') !== false) $color = '#ffb74d';
                                        echo '<div style="color: ' . $color . '; margin-bottom: 3px;">' . htmlspecialchars($line) . '</div>';
                                    }
                                }
                                ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($results['phpProcessLog'])): ?>
                        <div style="margin-bottom: 15px;">
                            <h4 style="font-size: 14px; margin-bottom: 5px; color: #444;">
                                <?php echo $results['isNginx'] ? 'PHP-FPM' : 'PHP'; ?> Error Log
                            </h4>
                            <div style="background: #263238; color: #cfd8dc; padding: 15px; border-radius: 6px; font-family: 'Courier New', monospace; font-size: 11px; line-height: 1.5; max-height: 300px; overflow-y: auto;">
                                <div style="color: #80cbc4; margin-bottom: 5px; font-weight: bold;">📂 <?php echo htmlspecialchars($results['phpProcessLog']); ?></div>
                                <?php
                                $phpLines = readLastNLines($results['phpProcessLog'], 20);
                                
                                if (empty($phpLines)) {
                                    echo '<div style="color: #90caf9;">Keine aktuellen Einträge oder Datei leer</div>';
                                } else {
                                    foreach ($phpLines as $line) {
                                        $color = '#cfd8dc';
                                        if (stripos($line, 'error') !== false || stripos($line, 'fatal') !== false) $color = '#e57373';
                                        elseif (stripos($line, 'warn') !== false || stripos($line, 'notice') !== false) $color = '#ffb74d';
                                        echo '<div style="color: ' . $color . '; margin-bottom: 3px;">' . htmlspecialchars($line) . '</div>';
                                    }
                                }
                                ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div style="padding: 10px; background: #e3f2fd; border-radius: 6px; border-left: 4px solid #2196f3; font-size: 12px;">
                            <strong style="color: #1565c0;">💡 Hinweis:</strong>
                            <span style="color: #1976d2;">
                                Diese Logs zeigen die letzten Fehler vom Webserver und PHP. Suchen Sie nach Fehlern, die zeitlich mit Ihren Login-Versuchen übereinstimmen.
                            </span>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($results['detectedWebUser'])): ?>
                    <div style="margin-top: 20px;">
                        <h3 style="font-size: 16px; margin-bottom: 10px; color: #666;">👤 Webserver-Benutzer</h3>
                        <div style="padding: 15px; background: #f5f5f5; border-radius: 6px;">
                            <table class="tests-table">
                                <tr>
                                    <td><strong>Erkannter Web-User</strong></td>
                                    <td><?php echo htmlspecialchars($results['detectedWebUser']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>PHP läuft als</strong></td>
                                    <td><?php echo htmlspecialchars(get_current_user()); ?> (UID: <?php echo getmyuid(); ?>, GID: <?php echo getmygid(); ?>)</td>
                                </tr>
                                <tr>
                                    <td><strong>Empfehlung</strong></td>
                                    <td>Alle Dateien sollten dem User <code><?php echo htmlspecialchars($results['detectedWebUser']); ?></code> gehören oder von ihm lesbar sein.</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <!-- Debug Mode Hint -->
                <div style="margin-top: 30px; padding: 15px; background: #fff3e0; border-radius: 6px; border-left: 4px solid #ff9800;">
                    <strong style="color: #e65100;">🔧 Debug-Modus verfügbar</strong><br>
                    <span style="color: #f57c00;">
                        Für detaillierte Debug-Informationen fügen Sie <code style="background: #fff; padding: 2px 6px; border-radius: 3px; color: #d32f2f;">?debug=1</code> zur URL hinzu.
                        Dies zeigt alle PHP-Meldungen, OpenSSL-Fehler und Schritt-für-Schritt-Protokolle an.
                    </span>
                    <div style="margin-top: 10px;">
                        <a href="?debug=1" class="btn btn-secondary" style="font-size: 12px; padding: 8px 16px;">
                            <span class="material-icons" style="font-size: 16px;">bug_report</span>
                            Debug-Modus aktivieren
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
