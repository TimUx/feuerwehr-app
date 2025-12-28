<?php
/**
 * Email Settings API - Save and test SMTP configuration
 */

require_once __DIR__ . '/../auth.php';

header('Content-Type: application/json');

// Initialize authentication
Auth::init();

// Check authentication and global admin role
if (!Auth::isAuthenticated() || !Auth::isGlobalAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$configFile = __DIR__ . '/../../../config/config.php';

// Handle test email
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'test') {
    try {
        // Load email helper
        require_once __DIR__ . '/../email_pdf.php';
        
        // Load current config
        $config = file_exists($configFile) ? require $configFile : [];
        $emailConfig = $config['email'] ?? [];
        
        if (empty($emailConfig['from_address'])) {
            throw new Exception('Keine Absender-Adresse konfiguriert');
        }
        
        // Prepare test email - send to the from_address as a test
        $to = $emailConfig['from_address'];
        $subject = 'Test-E-Mail - Feuerwehr Management System';
        $htmlBody = '<html><body style="font-family: Arial, sans-serif;">';
        $htmlBody .= '<h2>Test-E-Mail</h2>';
        $htmlBody .= '<p>Dies ist eine Test-E-Mail vom Feuerwehr Management System.</p>';
        $htmlBody .= '<p><strong>Die E-Mail-Konfiguration funktioniert korrekt!</strong></p>';
        $htmlBody .= '<hr>';
        $htmlBody .= '<p><small>Gesendet am: ' . date('d.m.Y H:i:s') . '<br>';
        $htmlBody .= 'Von: ' . htmlspecialchars($_SERVER['SERVER_NAME'] ?? 'Feuerwehr Management System') . '</small></p>';
        $htmlBody .= '</body></html>';
        
        // Send test email using EmailPDF helper
        $result = EmailPDF::sendEmail($to, $subject, $htmlBody);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Test-E-Mail erfolgreich versendet']);
        } else {
            $errorDetails = EmailPDF::getLastError();
            $errorMessage = 'E-Mail konnte nicht versendet werden. ';
            if (!empty($errorDetails)) {
                $errorMessage .= $errorDetails;
            } else {
                $errorMessage .= 'Bitte überprüfen Sie die SMTP-Einstellungen und stellen Sie sicher, dass der SMTP-Server erreichbar ist.';
            }
            throw new Exception($errorMessage);
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
