<?php
/**
 * Vehicles API - CRUD operations
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
            // Get all vehicles or single by ID
            if (isset($_GET['id'])) {
                $vehicle = DataStore::getVehicleById($_GET['id']);
                if ($vehicle) {
                    echo json_encode(['success' => true, 'data' => $vehicle]);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Fahrzeug nicht gefunden']);
                }
            } else {
                $vehicles = DataStore::getVehicles();
                echo json_encode(['success' => true, 'data' => $vehicles]);
            }
            break;

        case 'POST':
            // Create new vehicle - Admin only
            Auth::requireAdmin();
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['type'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Typ ist erforderlich']);
                break;
            }

            $vehicle = DataStore::createVehicle($data);
            echo json_encode(['success' => true, 'data' => $vehicle, 'message' => 'Fahrzeug erstellt']);
            break;

        case 'PUT':
            // Update vehicle - Admin only
            Auth::requireAdmin();
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID ist erforderlich']);
                break;
            }

            $vehicle = DataStore::updateVehicle($data['id'], $data);
            if ($vehicle) {
                echo json_encode(['success' => true, 'data' => $vehicle, 'message' => 'Fahrzeug aktualisiert']);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Fahrzeug nicht gefunden']);
            }
            break;

        case 'DELETE':
            // Delete vehicle - Admin only
            Auth::requireAdmin();
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID ist erforderlich']);
                break;
            }

            DataStore::deleteVehicle($data['id']);
            echo json_encode(['success' => true, 'message' => 'Fahrzeug gelöscht']);
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
