<?php
/**
 * Phone Numbers API
 */

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../datastore.php';

Auth::requireAuth();

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$dataStore = new DataStore();

try {
    switch ($method) {
        case 'GET':
            $phoneNumbers = $dataStore->getPhoneNumbers();
            echo json_encode(['success' => true, 'data' => $phoneNumbers]);
            break;
            
        case 'POST':
            Auth::requireAdmin();
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['name']) || empty($data['organization']) || empty($data['role']) || empty($data['phone'])) {
                echo json_encode(['success' => false, 'message' => 'Alle Felder sind erforderlich']);
                exit;
            }
            
            if (empty($data['id'])) {
                // Create new
                $id = uniqid('phone_', true);
                $phoneNumber = [
                    'id' => $id,
                    'name' => $data['name'],
                    'organization' => $data['organization'],
                    'role' => $data['role'],
                    'phone' => $data['phone'],
                    'created' => date('Y-m-d H:i:s')
                ];
                $dataStore->addPhoneNumber($phoneNumber);
            } else {
                // Update existing
                $phoneNumber = [
                    'id' => $data['id'],
                    'name' => $data['name'],
                    'organization' => $data['organization'],
                    'role' => $data['role'],
                    'phone' => $data['phone']
                ];
                $dataStore->updatePhoneNumber($data['id'], $phoneNumber);
            }
            
            echo json_encode(['success' => true]);
            break;
            
        case 'DELETE':
            Auth::requireAdmin();
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['id'])) {
                echo json_encode(['success' => false, 'message' => 'ID erforderlich']);
                exit;
            }
            
            $dataStore->deletePhoneNumber($data['id']);
            echo json_encode(['success' => true]);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
