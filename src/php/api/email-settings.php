<?php
/**
 * Email Settings API - Save and test SMTP configuration
 */

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../datastore.php';

header('Content-Type: application/json');

// Initialize authentication
Auth::init();
sendSecurityHeaders();

// Check authentication and global admin role
if (!Auth::isAuthenticated() || !Auth::isGlobalAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Handle test email
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'test') {
    Auth::requireCsrfToken();
    try {
        // Load email helper
        require_once __DIR__ . '/../email_pdf.php';

        $emailConfig = DataStore::getEmailSettings();

        if (empty($emailConfig['from_address'])) {
            echo json_encode(['success' => false, 'error' => 'Keine Absender-Adresse konfiguriert']);
            exit;
        }

        // Prepare test email - send to to_address, fall back to from_address
        $to = DataStore::getDefaultRecipient();
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
            $smtpError = EmailPDF::getLastError();
            $clientMessage = 'E-Mail konnte nicht versendet werden. ';
            if (!empty($smtpError)) {
                $clientMessage .= $smtpError;
            } else {
                $clientMessage .= 'Bitte überprüfen Sie die SMTP-Einstellungen und stellen Sie sicher, dass der SMTP-Server erreichbar ist.';
            }
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $clientMessage]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        error_log('Email test failed: ' . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Ein interner Fehler ist aufgetreten. Bitte prüfen Sie die Server-Logs.']);
    }
    exit;
}

// Handle save settings
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::requireCsrfToken();
    try {
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input) {
            throw new Exception('Invalid JSON input');
        }

        $existing = DataStore::getEmailSettings();
        // Empty password field means "keep existing password"
        $smtpPassword = $input['smtp_password'] ?? '';
        if ($smtpPassword === '') {
            $smtpPassword = $existing['smtp_password'] ?? '';
        }

        DataStore::updateEmailSettings([
            'smtp_host'     => $input['smtp_host']     ?? '',
            'smtp_port'     => (int) ($input['smtp_port'] ?? 587),
            'smtp_auth'     => !empty($input['smtp_auth']),
            'smtp_username' => $input['smtp_username'] ?? '',
            'smtp_password' => $smtpPassword,
            'smtp_secure'   => $input['smtp_secure']   ?? '',
            'from_address'  => $input['from_address']  ?? 'noreply@feuerwehr.local',
            'from_name'     => $input['from_name']     ?? 'Feuerwehr Management System',
            'to_address'    => $input['to_address']    ?? '',
            'contact_email' => $input['contact_email'] ?? '',
        ]);

        echo json_encode(['success' => true, 'message' => 'Settings saved successfully']);
    } catch (Exception $e) {
        http_response_code(500);
        error_log($e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Ein interner Fehler ist aufgetreten.']);
    }
    exit;
}

// Handle GET - return current settings
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $emailConfig = DataStore::getEmailSettings();
    // Never expose the SMTP password in the response
    $emailConfig['smtp_password'] = '';
    echo json_encode(['success' => true, 'settings' => $emailConfig]);
    exit;
}

// Method not allowed
http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Method not allowed']);
