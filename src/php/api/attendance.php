<?php
/**
 * Attendance API - CRUD operations for attendance records
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
            // Get all attendance records or single by ID
            if (isset($_GET['id'])) {
                $record = DataStore::getAttendanceRecordById($_GET['id']);
                if ($record) {
                    // Check location access for non-admins/operators
                    $user = Auth::getUser();
                    $userLocationId = $user['location_id'] ?? null;
                    $isGlobalUser = Auth::isAdmin() || Auth::isOperator() || empty($userLocationId);
                    
                    if (!$isGlobalUser && isset($record['location_id']) && $record['location_id'] !== $userLocationId) {
                        http_response_code(403);
                        echo json_encode(['success' => false, 'message' => 'Zugriff verweigert']);
                        exit;
                    }
                    
                    echo json_encode(['success' => true, 'data' => $record]);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Anwesenheitsliste nicht gefunden']);
                }
            } else {
                // Filter attendance records by location for non-global users
                $user = Auth::getUser();
                $userLocationId = $user['location_id'] ?? null;
                $isGlobalUser = Auth::isAdmin() || Auth::isOperator() || empty($userLocationId);
                
                if ($isGlobalUser) {
                    $records = DataStore::getAttendanceRecords();
                } else {
                    $records = DataStore::getAttendanceRecordsByLocation($userLocationId);
                }
                
                echo json_encode(['success' => true, 'data' => $records]);
            }
            break;

        case 'POST':
            // Create new attendance record
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['date']) && empty($data['datum'])) {
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
            $record = DataStore::createAttendanceRecord($data);
            echo json_encode(['success' => true, 'data' => $record, 'message' => 'Anwesenheitsliste erstellt']);
            break;

        case 'PUT':
            // Update attendance record
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID ist erforderlich']);
                break;
            }

            // Check if record exists and user has access
            $existingRecord = DataStore::getAttendanceRecordById($data['id']);
            if (!$existingRecord) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Anwesenheitsliste nicht gefunden']);
                break;
            }

            // Check location access for non-admins/operators
            $user = Auth::getUser();
            $userLocationId = $user['location_id'] ?? null;
            $isGlobalUser = Auth::isAdmin() || Auth::isOperator() || empty($userLocationId);
            
            if (!$isGlobalUser && isset($existingRecord['location_id']) && $existingRecord['location_id'] !== $userLocationId) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Zugriff verweigert']);
                exit;
            }

            $record = DataStore::updateAttendanceRecord($data['id'], $data);
            if ($record) {
                echo json_encode(['success' => true, 'data' => $record, 'message' => 'Anwesenheitsliste aktualisiert']);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Anwesenheitsliste nicht gefunden']);
            }
            break;

        case 'DELETE':
            // Delete attendance record
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID ist erforderlich']);
                break;
            }

            // Check if record exists and user has access
            $existingRecord = DataStore::getAttendanceRecordById($data['id']);
            if (!$existingRecord) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Anwesenheitsliste nicht gefunden']);
                break;
            }

            // Check location access for non-admins/operators
            $user = Auth::getUser();
            $userLocationId = $user['location_id'] ?? null;
            $isGlobalUser = Auth::isAdmin() || Auth::isOperator() || empty($userLocationId);
            
            if (!$isGlobalUser && isset($existingRecord['location_id']) && $existingRecord['location_id'] !== $userLocationId) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Zugriff verweigert']);
                exit;
            }

            DataStore::deleteAttendanceRecord($data['id']);
            echo json_encode(['success' => true, 'message' => 'Anwesenheitsliste gelöscht']);
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
