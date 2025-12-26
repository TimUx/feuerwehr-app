<?php
/**
 * Locations API - CRUD operations
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
            // Get all locations or single by ID
            if (isset($_GET['id'])) {
                $location = DataStore::getLocationById($_GET['id']);
                if ($location) {
                    echo json_encode(['success' => true, 'data' => $location]);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Standort nicht gefunden']);
                }
            } else {
                $locations = DataStore::getLocations();
                echo json_encode(['success' => true, 'data' => $locations]);
            }
            break;

        case 'POST':
            // Create new location - Admin only
            Auth::requireAdmin();
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['name'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Name ist erforderlich']);
                break;
            }

            $location = DataStore::createLocation($data);
            echo json_encode(['success' => true, 'data' => $location, 'message' => 'Standort erstellt']);
            break;

        case 'PUT':
            // Update location - Admin only
            Auth::requireAdmin();
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID ist erforderlich']);
                break;
            }

            $location = DataStore::updateLocation($data['id'], $data);
            if ($location) {
                echo json_encode(['success' => true, 'data' => $location, 'message' => 'Standort aktualisiert']);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Standort nicht gefunden']);
            }
            break;

        case 'DELETE':
            // Delete location - Admin only
            Auth::requireAdmin();
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID ist erforderlich']);
                break;
            }

            DataStore::deleteLocation($data['id']);
            echo json_encode(['success' => true, 'message' => 'Standort gelöscht']);
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
