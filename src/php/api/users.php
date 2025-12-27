<?php
/**
 * Users API - CRUD operations (Admin only)
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../auth.php';

// Initialize authentication
Auth::init();

// Check authentication and admin role
Auth::requireAdmin();

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // Get all users
            $users = Auth::listUsers();
            echo json_encode(['success' => true, 'data' => $users]);
            break;

        case 'POST':
            // Create new user
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['username']) || empty($data['password'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Benutzername und Passwort sind erforderlich']);
                break;
            }

            $role = $data['role'] ?? 'operator';
            $locationId = isset($data['location_id']) && $data['location_id'] !== '' ? $data['location_id'] : null;
            $email = isset($data['email']) && $data['email'] !== '' ? $data['email'] : null;
            $success = Auth::createUser($data['username'], $data['password'], $role, $locationId, $email);
            
            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Benutzer erstellt']);
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Benutzername existiert bereits']);
            }
            break;

        case 'PUT':
            // Update user
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID ist erforderlich']);
                break;
            }

            $updateData = [];
            if (isset($data['username'])) {
                $updateData['username'] = $data['username'];
            }
            if (isset($data['password']) && !empty($data['password'])) {
                $updateData['password'] = $data['password'];
            }
            if (isset($data['role'])) {
                $updateData['role'] = $data['role'];
            }
            if (isset($data['location_id'])) {
                $updateData['location_id'] = $data['location_id'] !== '' ? $data['location_id'] : null;
            }
            if (isset($data['email'])) {
                $updateData['email'] = $data['email'] !== '' ? $data['email'] : null;
            }

            $success = Auth::updateUser($data['id'], $updateData);
            
            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Benutzer aktualisiert']);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Benutzer nicht gefunden']);
            }
            break;

        case 'DELETE':
            // Delete user
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID ist erforderlich']);
                break;
            }

            Auth::deleteUser($data['id']);
            echo json_encode(['success' => true, 'message' => 'Benutzer gelöscht']);
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
