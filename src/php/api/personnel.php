<?php
/**
 * Personnel API - CRUD operations
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../datastore.php';

// Initialize authentication
Auth::init();

// Check authentication
if (!Auth::isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Nicht authentifiziert']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // Get all personnel or single by ID
            if (isset($_GET['id'])) {
                $personnel = DataStore::getPersonnelById($_GET['id']);
                if ($personnel) {
                    // Check location access for non-admins/operators
                    $user = Auth::getUser();
                    $userLocationId = $user['location_id'] ?? null;
                    $isGlobalUser = Auth::isAdmin() || Auth::isOperator() || empty($userLocationId);
                    
                    if (!$isGlobalUser && isset($personnel['location_id']) && $personnel['location_id'] !== $userLocationId) {
                        http_response_code(403);
                        echo json_encode(['success' => false, 'message' => 'Zugriff verweigert']);
                        exit;
                    }
                    
                    echo json_encode(['success' => true, 'data' => $personnel]);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Einsatzkraft nicht gefunden']);
                }
            } else {
                // Filter personnel by location for non-global users
                $user = Auth::getUser();
                $userLocationId = $user['location_id'] ?? null;
                $isGlobalUser = Auth::isAdmin() || Auth::isOperator() || empty($userLocationId);
                
                if ($isGlobalUser) {
                    $personnel = DataStore::getPersonnel();
                } else {
                    $personnel = DataStore::getPersonnelByLocation($userLocationId);
                }
                
                echo json_encode(['success' => true, 'data' => $personnel]);
            }
            break;

        case 'POST':
            // Create new personnel - Admin only
            Auth::requireAdmin();
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['name'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Name ist erforderlich']);
                break;
            }

            $personnel = DataStore::createPersonnel($data);
            echo json_encode(['success' => true, 'data' => $personnel, 'message' => 'Einsatzkraft erstellt']);
            break;

        case 'PUT':
            // Update personnel - Admin only
            Auth::requireAdmin();
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID ist erforderlich']);
                break;
            }

            $personnel = DataStore::updatePersonnel($data['id'], $data);
            if ($personnel) {
                echo json_encode(['success' => true, 'data' => $personnel, 'message' => 'Einsatzkraft aktualisiert']);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Einsatzkraft nicht gefunden']);
            }
            break;

        case 'DELETE':
            // Delete personnel - Admin only
            Auth::requireAdmin();
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID ist erforderlich']);
                break;
            }

            DataStore::deletePersonnel($data['id']);
            echo json_encode(['success' => true, 'message' => 'Einsatzkraft gelöscht']);
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
