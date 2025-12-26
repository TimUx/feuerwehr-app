<?php
/**
 * Hazardous Materials API
 * Loads UN number database from external JSON file
 */

require_once __DIR__ . '/../auth.php';

Auth::requireOperator();

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET' && isset($_GET['un'])) {
    // Search by UN number
    $unNumber = $_GET['un'];
    $material = searchHazardousMaterial($unNumber);
    
    if ($material) {
        echo json_encode(['success' => true, 'data' => $material]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gefahrstoff nicht gefunden']);
    }
    exit;
}

function searchHazardousMaterial($unNumber) {
    static $materials = null;
    
    // Load materials from JSON file on first call (lazy loading)
    if ($materials === null) {
        $dataFile = __DIR__ . '/../data/un_numbers.json';
        
        if (!file_exists($dataFile)) {
            error_log("UN numbers data file not found: $dataFile");
            return null;
        }
        
        $jsonContent = file_get_contents($dataFile);
        if ($jsonContent === false) {
            error_log("Failed to read UN numbers data file: $dataFile");
            return null;
        }
        
        $materials = json_decode($jsonContent, true);
        if ($materials === null) {
            error_log("Failed to parse UN numbers JSON: " . json_last_error_msg());
            return null;
        }
    }
    
    return $materials[$unNumber] ?? null;
}
