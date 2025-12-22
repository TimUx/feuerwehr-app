<?php
/**
 * Email Settings API - Save and test SMTP configuration
 */

require_once __DIR__ . '/../auth.php';

header('Content-Type: application/json');

// Check authentication and admin role
if (!Auth::isAuthenticated() || !Auth::isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$configFile = __DIR__ . '/../../../config/config.php';

// Handle test email
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'test') {
    try {
        // Load current config
        $config = file_exists($configFile) ? require $configFile : [];
        $emailConfig = $config['email'] ?? [];
        
        if (empty($emailConfig['to_address'])) {
            throw new Exception('Keine Empfänger-Adresse konfiguriert');
        }
        
        // Prepare test email
        $to = $emailConfig['to_address'];
        $subject = 'Test-E-Mail - Feuerwehr Management System';
        $message = "Dies ist eine Test-E-Mail.\n\n";
        $message .= "Die E-Mail-Konfiguration funktioniert korrekt.\n\n";
        $message .= "Gesendet am: " . date('d.m.Y H:i:s') . "\n";
        $message .= "Von: " . ($_SERVER['SERVER_NAME'] ?? 'Feuerwehr Management System');
        
        $headers = [];
        $headers[] = 'From: ' . ($emailConfig['from_name'] ?? 'Feuerwehr Management') . ' <' . ($emailConfig['from_address'] ?? 'noreply@feuerwehr.local') . '>';
        $headers[] = 'X-Mailer: PHP/' . phpversion();
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: text/plain; charset=UTF-8';
        
        // Configure SMTP if settings available
        if (!empty($emailConfig['smtp_host'])) {
            ini_set('SMTP', $emailConfig['smtp_host']);
            ini_set('smtp_port', $emailConfig['smtp_port'] ?? 25);
        }
        
        $result = mail($to, $subject, $message, implode("\r\n", $headers));
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Test-E-Mail erfolgreich versendet']);
        } else {
            throw new Exception('mail() Funktion hat false zurückgegeben');
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Handle save settings
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            throw new Exception('Invalid JSON input');
        }
        
        // Load current config
        $config = file_exists($configFile) ? require $configFile : [];
        
        // Update email settings
        $config['email'] = [
            'smtp_host' => $input['smtp_host'] ?? 'localhost',
            'smtp_port' => (int)($input['smtp_port'] ?? 587),
            'smtp_auth' => !empty($input['smtp_auth']),
            'smtp_username' => $input['smtp_username'] ?? '',
            'smtp_password' => $input['smtp_password'] ?? '',
            'smtp_secure' => $input['smtp_secure'] ?? '',
            'from_address' => $input['from_address'] ?? 'noreply@feuerwehr.local',
            'from_name' => $input['from_name'] ?? 'Feuerwehr Management System',
            'to_address' => $input['to_address'] ?? '',
        ];
        
        // Generate PHP config file content
        $configContent = "<?php\n";
        $configContent .= "/**\n";
        $configContent .= " * Configuration file for Feuerwehr App\n";
        $configContent .= " * Last updated: " . date('Y-m-d H:i:s') . "\n";
        $configContent .= " */\n\n";
        $configContent .= "return " . var_export($config, true) . ";\n";
        
        // Save config file
        if (file_put_contents($configFile, $configContent) === false) {
            throw new Exception('Failed to write config file');
        }
        
        echo json_encode(['success' => true, 'message' => 'Settings saved successfully']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Method not allowed
http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Method not allowed']);
