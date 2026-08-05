<?php
/**
 * Global search API
 */

header('Content-Type: application/json; charset=UTF-8');
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../datastore.php';

Auth::init();
sendSecurityHeaders();
Auth::requireOperator();

$q = trim($_GET['q'] ?? '');
if (mb_strlen($q) < 2) {
    echo json_encode(['success' => true, 'data' => [
        'personnel' => [], 'vehicles' => [], 'attendance' => [], 'missions' => [],
    ], 'message' => 'Mindestens 2 Zeichen eingeben']);
    exit;
}

$locationId = Auth::hasGlobalAccess() ? null : Auth::getUserLocationId();
$results = DataStore::globalSearch($q, $locationId);

echo json_encode(['success' => true, 'query' => $q, 'data' => $results]);
