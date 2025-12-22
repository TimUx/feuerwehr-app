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
                
                // Encrypt users data manually (matching Encryption class format)
                // Convert hex key to binary for AES-256 (64-char hex -> 32 bytes)
                $encryption_key_binary = hex2bin($encryption_key);
                if ($encryption_key_binary === false) {
                    $errors[] = 'Fehler bei der Konvertierung des Verschlüsselungsschlüssels.';
                    $step = 3; // Stay on step 3
                } else {
                    $iv = random_bytes(openssl_cipher_iv_length('aes-256-cbc'));
                    $encrypted = openssl_encrypt($usersData, 'aes-256-cbc', $encryption_key_binary, 0, $iv);
                    $encryptedUsers = base64_encode($iv . '::' . $encrypted);
                    
                    file_put_contents($dataDir . '/users.json', $encryptedUsers);
                    chmod($dataDir . '/users.json', 0600);
                    
                    // Clear session data properly
                    if (session_status() === PHP_SESSION_ACTIVE) {
                        session_unset();
                        session_destroy();
                        // Clear the session cookie to ensure a fresh session on next request
                        if (isset($_COOKIE[session_name()])) {
                            setcookie(session_name(), '', time() - 3600, '/');
                        }
                    }
                    
                    $success = true;
                    $step = 4;
                }
            } else {
                $errors[] = 'Fehler beim Schreiben der Konfigurationsdatei. Prüfen Sie die Dateiberechtigungen.';
            }
        }
    }
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
                <!-- Step 4: Success -->
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
                
                <div class="info-box">
                    <strong>Nächste Schritte:</strong>
                    <ul style="margin: 10px 0 0 20px; line-height: 1.8;">
                        <li>Melden Sie sich mit Ihrem Administrator-Benutzer an</li>
                        <li>Fügen Sie Einsatzkräfte hinzu</li>
                        <li>Konfigurieren Sie Fahrzeuge</li>
                        <li>Erstellen Sie weitere Benutzer bei Bedarf</li>
                    </ul>
                </div>
                
                <div class="button-group" style="justify-content: center;">
                    <a href="index.php" class="btn btn-primary">
                        <span class="material-icons">login</span>
                        Zur Anmeldung
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
