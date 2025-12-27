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
                    // Check location access
                    $user = Auth::getUser();
                    $userLocationId = $user['location_id'] ?? null;
                    
                    // Location-restricted admins can only access their location's vehicles
                    if (Auth::hasLocationRestriction()) {
                        $vehicleLocationId = $vehicle['location_id'] ?? null;
                        if (!Auth::canAccessLocation($vehicleLocationId)) {
                            http_response_code(403);
                            echo json_encode(['success' => false, 'message' => 'Zugriff verweigert']);
                            exit;
                        }
                    }
                    // Regular users (non-admin/non-operator) also have location restrictions
                    elseif (!Auth::isAdmin() && !Auth::isOperator()) {
                        if (!$userLocationId || (isset($vehicle['location_id']) && $vehicle['location_id'] !== $userLocationId)) {
                            http_response_code(403);
                            echo json_encode(['success' => false, 'message' => 'Zugriff verweigert']);
                            exit;
                        }
                    }
                    
                    echo json_encode(['success' => true, 'data' => $vehicle]);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Fahrzeug nicht gefunden']);
                }
            } else {
                // Filter vehicles by location
                $user = Auth::getUser();
                $userLocationId = $user['location_id'] ?? null;
                
                // Location-restricted admins only see their location's vehicles
                if (Auth::hasLocationRestriction()) {
                    $vehicles = $userLocationId ? DataStore::getVehiclesByLocation($userLocationId) : [];
                }
                // Admins without location restriction have global access
                elseif (Auth::isAdmin() || Auth::isOperator()) {
                    $vehicles = DataStore::getVehicles();
                } 
                // Regular users see only their location's vehicles
                else {
                    $vehicles = $userLocationId ? DataStore::getVehiclesByLocation($userLocationId) : [];
                }
                
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

            // If admin has location restriction, auto-set location to their assigned location
            if (Auth::hasLocationRestriction()) {
                $userLocationId = Auth::getUserLocationId();
                $data['location_id'] = $userLocationId;
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

            // Check if vehicle exists and if admin has access to it
            $existingVehicle = DataStore::getVehicleById($data['id']);
            if (!$existingVehicle) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Fahrzeug nicht gefunden']);
                break;
            }
            
            // Validate location access for location-restricted admins
            if (Auth::hasLocationRestriction()) {
                $existingLocationId = $existingVehicle['location_id'] ?? null;
                if (!Auth::canAccessLocation($existingLocationId)) {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => 'Zugriff verweigert. Sie können nur Fahrzeuge Ihres Standorts bearbeiten.']);
                    break;
                }
                
                // Don't allow changing location for location-restricted admins
                if (isset($data['location_id']) && $data['location_id'] !== $existingLocationId) {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => 'Sie können den Standort nicht ändern.']);
                    break;
                }
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

            // Check if vehicle exists and if admin has access to it
            $existingVehicle = DataStore::getVehicleById($data['id']);
            if (!$existingVehicle) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Fahrzeug nicht gefunden']);
                break;
            }
            
            // Validate location access for location-restricted admins
            if (Auth::hasLocationRestriction()) {
                $existingLocationId = $existingVehicle['location_id'] ?? null;
                if (!Auth::canAccessLocation($existingLocationId)) {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => 'Zugriff verweigert. Sie können nur Fahrzeuge Ihres Standorts löschen.']);
                    break;
                }
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
