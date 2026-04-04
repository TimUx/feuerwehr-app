<?php
/**
 * Password Reset API
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../email_pdf.php';

// Initialize authentication (but don't require login for password reset)
Auth::init();
sendSecurityHeaders();

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'POST') {
        Auth::requireCsrfToken();
        $action = $_GET['action'] ?? 'request';
        
        if ($action === 'request') {
            // Request password reset
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['username'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Benutzername ist erforderlich']);
                exit;
            }
            
            $username = $data['username'];
            $result = Auth::generatePasswordResetToken($username);
            
            if (!$result) {
                // Don't reveal if user exists - security best practice
                echo json_encode(['success' => true, 'message' => 'Falls ein Konto mit diesem Benutzernamen existiert und eine E-Mail-Adresse hinterlegt ist, wurde ein Link zur Passwort-Wiederherstellung gesendet.']);
                exit;
            }
            
            if (isset($result['error']) && $result['error'] === 'no_email') {
                // Don't reveal if user exists - security best practice
                echo json_encode(['success' => true, 'message' => 'Falls ein Konto mit diesem Benutzernamen existiert und eine E-Mail-Adresse hinterlegt ist, wurde ein Link zur Passwort-Wiederherstellung gesendet.']);
                exit;
            }
            
            // Send password reset email
            $token = $result['token'];
            $email = $result['email'];
            $username = $result['username'];
            
            // Construct reset link
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $resetLink = "{$protocol}://{$host}/index.php?action=reset-password&token=" . urlencode($token);
            
            // Email content
            $subject = 'Passwort-Wiederherstellung - Feuerwehr Management';
            $htmlBody = "
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background: #d32f2f; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                        .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 5px 5px; }
                        .button { display: inline-block; padding: 12px 24px; background: #d32f2f; color: white; text-decoration: none; border-radius: 4px; margin: 20px 0; }
                        .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h1>🚒 Passwort-Wiederherstellung</h1>
                        </div>
                        <div class='content'>
                            <p>Hallo <strong>" . htmlspecialchars($username) . "</strong>,</p>
                            <p>Sie haben eine Passwort-Wiederherstellung für Ihr Konto angefordert.</p>
                            <p>Klicken Sie auf den folgenden Link, um ein neues Passwort zu setzen:</p>
                            <p style='text-align: center;'>
                                <a href='" . htmlspecialchars($resetLink) . "' class='button'>Passwort zurücksetzen</a>
                            </p>
                            <p>Oder kopieren Sie diesen Link in Ihren Browser:</p>
                            <p style='word-break: break-all; background: white; padding: 10px; border-radius: 4px;'>" . htmlspecialchars($resetLink) . "</p>
                            <p><strong>Wichtig:</strong> Dieser Link ist nur 1 Stunde gültig.</p>
                            <p>Falls Sie diese Anfrage nicht gestellt haben, können Sie diese E-Mail ignorieren.</p>
                        </div>
                        <div class='footer'>
                            <p>Feuerwehr Management System</p>
                        </div>
                    </div>
                </body>
                </html>
            ";
            
            // Send email without attachments (pass null for attachment parameters)
            $emailSent = EmailPDF::sendEmailWithAttachments($email, $subject, $htmlBody, null, 'document.pdf', null, null);
            
            if ($emailSent) {
                echo json_encode(['success' => true, 'message' => 'Falls ein Konto mit diesem Benutzernamen existiert und eine E-Mail-Adresse hinterlegt ist, wurde ein Link zur Passwort-Wiederherstellung gesendet.']);
            } else {
                error_log("Failed to send password reset email to {$email}: " . EmailPDF::getLastError());
                // Don't reveal error details to user - security best practice
                echo json_encode(['success' => true, 'message' => 'Falls ein Konto mit diesem Benutzernamen existiert und eine E-Mail-Adresse hinterlegt ist, wurde ein Link zur Passwort-Wiederherstellung gesendet.']);
            }
            
        } elseif ($action === 'verify') {
            // Verify token
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['token'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Token ist erforderlich']);
                exit;
            }
            
            $tokenData = Auth::verifyPasswordResetToken($data['token']);
            
            if ($tokenData) {
                echo json_encode(['success' => true, 'username' => $tokenData['username']]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Ungültiger oder abgelaufener Token']);
            }
            
        } elseif ($action === 'reset') {
            // Reset password with token
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['token']) || empty($data['password'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Token und neues Passwort sind erforderlich']);
                exit;
            }
            
            $success = Auth::resetPassword($data['token'], $data['password']);
            
            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Passwort erfolgreich zurückgesetzt']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Ungültiger oder abgelaufener Token']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Ungültige Aktion']);
        }
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Methode nicht erlaubt']);
    }
} catch (Exception $e) {
    http_response_code(500);
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Ein interner Fehler ist aufgetreten.']);
}
