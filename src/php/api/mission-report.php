<?php
/**
 * Mission Report API - CRUD operations for mission reports
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../datastore.php';

// Initialize authentication
Auth::init();

// Check authentication - require operator role
if (!Auth::isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Nicht authentifiziert']);
    exit;
}

Auth::requireOperator();

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // Get all mission reports or single by ID
            if (isset($_GET['id'])) {
                $report = DataStore::getMissionReportById($_GET['id']);
                if ($report) {
                    // Check location access for non-admins/operators
                    $user = Auth::getUser();
                    $userLocationId = $user['location_id'] ?? null;
                    $isGlobalUser = Auth::isAdmin() || Auth::isOperator() || empty($userLocationId);
                    
                    if (!$isGlobalUser && isset($report['location_id']) && $report['location_id'] !== $userLocationId) {
                        http_response_code(403);
                        echo json_encode(['success' => false, 'message' => 'Zugriff verweigert']);
                        exit;
                    }
                    
                    echo json_encode(['success' => true, 'data' => $report]);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Einsatzbericht nicht gefunden']);
                }
            } else {
                // Filter mission reports by location for non-global users
                $user = Auth::getUser();
                $userLocationId = $user['location_id'] ?? null;
                $isGlobalUser = Auth::isAdmin() || Auth::isOperator() || empty($userLocationId);
                
                if ($isGlobalUser) {
                    $reports = DataStore::getMissionReports();
                } else {
                    $reports = DataStore::getMissionReportsByLocation($userLocationId);
                }
                
                echo json_encode(['success' => true, 'data' => $reports]);
            }
            break;

        case 'POST':
            // Create new mission report
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['date'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Datum ist erforderlich']);
                break;
            }

            // Add location_id from user if not already set
            $user = Auth::getUser();
            if (!isset($data['location_id']) && !empty($user['location_id'])) {
                $data['location_id'] = $user['location_id'];
            }
            
            $data['created_by'] = $user['id'];
            $report = DataStore::createMissionReport($data);
            echo json_encode(['success' => true, 'data' => $report, 'message' => 'Einsatzbericht erstellt']);
            break;

        case 'PUT':
            // Update mission report
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID ist erforderlich']);
                break;
            }

            // Check if report exists and user has access
            $existingReport = DataStore::getMissionReportById($data['id']);
            if (!$existingReport) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Einsatzbericht nicht gefunden']);
                break;
            }

            // Check location access for non-admins/operators
            $user = Auth::getUser();
            $userLocationId = $user['location_id'] ?? null;
            $isGlobalUser = Auth::isAdmin() || Auth::isOperator() || empty($userLocationId);
            
            if (!$isGlobalUser && isset($existingReport['location_id']) && $existingReport['location_id'] !== $userLocationId) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Zugriff verweigert']);
                exit;
            }

            $report = DataStore::updateMissionReport($data['id'], $data);
            if ($report) {
                echo json_encode(['success' => true, 'data' => $report, 'message' => 'Einsatzbericht aktualisiert']);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Einsatzbericht nicht gefunden']);
            }
            break;

        case 'DELETE':
            // Delete mission report
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID ist erforderlich']);
                break;
            }

            // Check if report exists and user has access
            $existingReport = DataStore::getMissionReportById($data['id']);
            if (!$existingReport) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Einsatzbericht nicht gefunden']);
                break;
            }

            // Check location access for non-admins/operators
            $user = Auth::getUser();
            $userLocationId = $user['location_id'] ?? null;
            $isGlobalUser = Auth::isAdmin() || Auth::isOperator() || empty($userLocationId);
            
            if (!$isGlobalUser && isset($existingReport['location_id']) && $existingReport['location_id'] !== $userLocationId) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Zugriff verweigert']);
                exit;
            }

            DataStore::deleteMissionReport($data['id']);
            echo json_encode(['success' => true, 'message' => 'Einsatzbericht gelöscht']);
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
