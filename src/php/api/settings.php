<?php
/**
 * Settings API - CRUD operations for general settings
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../datastore.php';

// Initialize authentication
Auth::init();

// Check authentication and global admin rights
if (!Auth::isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Nicht authentifiziert']);
    exit;
}

Auth::requireGlobalAdmin();

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // Get all settings
            $settings = DataStore::getSettings();
            echo json_encode(['success' => true, 'data' => $settings]);
            break;

        case 'POST':
            // Update settings
            $data = [
                'fire_department_name' => $_POST['fire_department_name'] ?? '',
                'fire_department_city' => $_POST['fire_department_city'] ?? '',
                'email_recipient' => $_POST['email_recipient'] ?? '',
                'contact_phone' => $_POST['contact_phone'] ?? '',
                'contact_email' => $_POST['contact_email'] ?? '',
                'address' => $_POST['address'] ?? ''
            ];
            
            // Validate required fields
            if (empty($data['fire_department_name'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Name der Feuerwehr ist erforderlich']);
                exit;
            }
            
            // Handle logo upload
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                $logoResult = handleLogoUpload($_FILES['logo']);
                if ($logoResult['success']) {
                    $data['logo_filename'] = $logoResult['filename'];
                } else {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => $logoResult['message']]);
                    exit;
                }
            }
            
            $settings = DataStore::updateSettings($data);
            echo json_encode(['success' => true, 'data' => $settings, 'message' => 'Einstellungen wurden gespeichert']);
            break;

        case 'DELETE':
            // Delete/remove specific setting (e.g., logo)
            $input = json_decode(file_get_contents('php://input'), true);
            $action = $input['action'] ?? '';
            
            if ($action === 'remove_logo') {
                $result = DataStore::removeLogo();
                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Logo wurde entfernt']);
                } else {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => 'Fehler beim Entfernen des Logos']);
                }
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Ungültige Aktion']);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Methode nicht erlaubt']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Serverfehler: ' . $e->getMessage()]);
}

/**
 * Handle logo file upload
 */
function handleLogoUpload($file) {
    // Check file size (max 2MB)
    if ($file['size'] > 2 * 1024 * 1024) {
        return ['success' => false, 'message' => 'Datei ist zu groß (max. 2 MB)'];
    }
    
    // Check file type
    $allowedTypes = ['image/png', 'image/jpeg', 'image/jpg', 'image/svg+xml', 'image/gif'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    
    if (!in_array($mimeType, $allowedTypes)) {
        return ['success' => false, 'message' => 'Ungültiger Dateityp. Nur Bilder (PNG, JPG, SVG, GIF) erlaubt'];
    }
    
    // Create settings directory if it doesn't exist
    // Uses 0755 permissions because logo files are served directly via HTTP
    $uploadDir = __DIR__ . '/../../../data/settings';
    if (!file_exists($uploadDir)) {
        if (!@mkdir($uploadDir, 0755, true)) {
            error_log("Failed to create settings directory: " . $uploadDir);
            return ['success' => false, 'message' => 'Fehler beim Erstellen des Upload-Verzeichnisses. Bitte prüfen Sie die Dateirechte.'];
        }
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'logo_' . time() . '.' . $extension;
    $targetPath = $uploadDir . '/' . $filename;
    
    // Remove old logo if exists
    $settings = DataStore::getSettings();
    if (!empty($settings['logo_filename'])) {
        $oldLogo = $uploadDir . '/' . $settings['logo_filename'];
        if (file_exists($oldLogo)) {
            unlink($oldLogo);
        }
    }
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => true, 'filename' => $filename];
    } else {
        return ['success' => false, 'message' => 'Fehler beim Hochladen der Datei'];
    }
}
