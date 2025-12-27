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
                    // Check location access
                    $user = Auth::getUser();
                    $userLocationId = $user['location_id'] ?? null;
                    
                    // Location-restricted admins can only access their location's personnel
                    if (Auth::hasLocationRestriction()) {
                        $personnelLocationId = $personnel['location_id'] ?? null;
                        if (!Auth::canAccessLocation($personnelLocationId)) {
                            http_response_code(403);
                            echo json_encode(['success' => false, 'message' => 'Zugriff verweigert']);
                            exit;
                        }
                    }
                    // Regular users (non-admin/non-operator) also have location restrictions
                    elseif (!Auth::isAdmin() && !Auth::isOperator()) {
                        if (!$userLocationId || (isset($personnel['location_id']) && $personnel['location_id'] !== $userLocationId)) {
                            http_response_code(403);
                            echo json_encode(['success' => false, 'message' => 'Zugriff verweigert']);
                            exit;
                        }
                    }
                    
                    echo json_encode(['success' => true, 'data' => $personnel]);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Einsatzkraft nicht gefunden']);
                }
            } else {
                // Filter personnel by location
                $user = Auth::getUser();
                $userLocationId = $user['location_id'] ?? null;
                
                // Location-restricted admins only see their location's personnel
                if (Auth::hasLocationRestriction()) {
                    $personnel = $userLocationId ? DataStore::getPersonnelByLocation($userLocationId) : [];
                }
                // Admins without location restriction have global access
                elseif (Auth::isAdmin() || Auth::isOperator()) {
                    $personnel = DataStore::getPersonnel();
                } 
                // Regular users see only their location's personnel
                else {
                    $personnel = $userLocationId ? DataStore::getPersonnelByLocation($userLocationId) : [];
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

            // If admin has location restriction, auto-set location to their assigned location
            if (Auth::hasLocationRestriction()) {
                $userLocationId = Auth::getUserLocationId();
                $data['location_id'] = $userLocationId;
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

            // Check if personnel exists and if admin has access to it
            $existingPersonnel = DataStore::getPersonnelById($data['id']);
            if (!$existingPersonnel) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Einsatzkraft nicht gefunden']);
                break;
            }
            
            // Validate location access for location-restricted admins
            if (Auth::hasLocationRestriction()) {
                $existingLocationId = $existingPersonnel['location_id'] ?? null;
                if (!Auth::canAccessLocation($existingLocationId)) {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => 'Zugriff verweigert. Sie können nur Einsatzkräfte Ihres Standorts bearbeiten.']);
                    break;
                }
                
                // Don't allow changing location for location-restricted admins
                if (isset($data['location_id']) && $data['location_id'] !== $existingLocationId) {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => 'Sie können den Standort nicht ändern.']);
                    break;
                }
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

            // Check if personnel exists and if admin has access to it
            $existingPersonnel = DataStore::getPersonnelById($data['id']);
            if (!$existingPersonnel) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Einsatzkraft nicht gefunden']);
                break;
            }
            
            // Validate location access for location-restricted admins
            if (Auth::hasLocationRestriction()) {
                $existingLocationId = $existingPersonnel['location_id'] ?? null;
                if (!Auth::canAccessLocation($existingLocationId)) {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => 'Zugriff verweigert. Sie können nur Einsatzkräfte Ihres Standorts löschen.']);
                    break;
                }
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
