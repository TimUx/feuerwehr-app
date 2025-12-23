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

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$configFile = __DIR__ . '/config/config.php';
$dataDir = __DIR__ . '/data';
$usersFile = $dataDir . '/users.json';

/**
 * Run all diagnostic tests
 */
function runAllTests() {
    global $configFile, $dataDir, $usersFile;
    
    $tests = [];
    $criticalFailures = 0;
    
    // Test 1: PHP Version
    $phpVersion = PHP_VERSION;
    $versionOk = version_compare($phpVersion, '7.4.0', '>=');
    $tests[] = [
        'category' => 'System',
        'name' => 'PHP Version',
        'status' => $versionOk ? 'pass' : 'fail',
        'message' => "PHP $phpVersion" . (!$versionOk ? ' (mindestens 7.4.0 erforderlich)' : ''),
        'critical' => true
    ];
    if (!$versionOk) $criticalFailures++;
    
    // Test 2: Required PHP extensions
    $requiredExtensions = ['openssl', 'mbstring', 'json', 'session'];
    foreach ($requiredExtensions as $ext) {
        $loaded = extension_loaded($ext);
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
    $configExists = file_exists($configFile);
    $configReadable = $configExists && is_readable($configFile);
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
    $config = null;
    $configValid = false;
    if ($configReadable) {
        try {
            $config = require $configFile;
            $configValid = is_array($config) && 
                          isset($config['encryption_key']) && 
                          isset($config['data_dir']) &&
                          isset($config['session_lifetime']);
            
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
                $keyValid = ctype_xdigit($config['encryption_key']) && strlen($config['encryption_key']) === 64;
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
    $dataDirExists = file_exists($dataDir);
    $dataDirWritable = $dataDirExists && is_writable($dataDir);
    $tests[] = [
        'category' => 'Dateisystem',
        'name' => 'data/ Verzeichnis',
        'status' => $dataDirWritable ? 'pass' : 'fail',
        'message' => $dataDirWritable ? 'Beschreibbar' : 
                     ($dataDirExists ? 'Existiert aber nicht beschreibbar' : 'Existiert nicht'),
        'critical' => true,
        'fix' => !$dataDirWritable ? 'chmod 755 ' . $dataDir . ' && chown www-data:www-data ' . $dataDir : null
    ];
    if (!$dataDirWritable) $criticalFailures++;
    
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
    if ($configValid && $usersReadable) {
        try {
            $encryptedData = file_get_contents($usersFile);
            $keyBinary = hex2bin($config['encryption_key']);
            $decoded = base64_decode($encryptedData);
            $parts = explode('::', $decoded, 2);
            
            if (count($parts) === 2) {
                list($iv, $encrypted) = $parts;
                $decrypted = openssl_decrypt($encrypted, 'aes-256-cbc', $keyBinary, OPENSSL_RAW_DATA, $iv);
                
                if ($decrypted !== false) {
                    $users = json_decode($decrypted, true);
                    $decryptSuccess = is_array($users) && count($users) > 0;
                    
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
                                break;
                            }
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
            } else {
                $tests[] = [
                    'category' => 'Verschlüsselung',
                    'name' => 'Entschlüsselung',
                    'status' => 'fail',
                    'message' => 'Ungültiges Verschlüsselungsformat',
                    'critical' => true,
                    'fix' => 'Datei beschädigt - führen Sie install.php erneut aus'
                ];
                $criticalFailures++;
            }
        } catch (Exception $e) {
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
        'fix' => !$sessionPathWritable && !empty($sessionPath) ? 'chown www-data:www-data ' . $sessionPath : null
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
    
    if ($isNginx) {
        // Nginx-specific tests
        $sapi = php_sapi_name();
        $isFPM = $sapi === 'fpm-fcgi' || $sapi === 'cgi-fcgi';
        
        $tests[] = [
            'category' => 'Webserver',
            'name' => 'PHP-FPM',
            'status' => $isFPM ? 'pass' : 'warn',
            'message' => "SAPI: $sapi" . ($isFPM ? ' (FPM aktiv)' : ''),
            'critical' => false,
            'fix' => !$isFPM ? 'Nginx sollte PHP-FPM verwenden' : null
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
    
    return [
        'tests' => $tests,
        'criticalFailures' => $criticalFailures,
        'totalTests' => count($tests),
        'timestamp' => date('Y-m-d H:i:s'),
        'phpVersion' => PHP_VERSION,
        'webserver' => $serverSoftware
    ];
}

$results = runAllTests();
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
        </div>
    </div>
</body>
</html>
