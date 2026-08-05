<?php
/**
 * Calendar events API
 */

header('Content-Type: application/json; charset=UTF-8');
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../datastore.php';

Auth::init();
sendSecurityHeaders();
Auth::requireOperator();

$month = $_GET['month'] ?? date('Y-m');
$locationId = Auth::hasGlobalAccess() ? null : Auth::getUserLocationId();
if (Auth::hasGlobalAccess() && !empty($_GET['location_id'])) {
    $locationId = $_GET['location_id'];
}

$events = DataStore::getCalendarEvents($month, $locationId);
echo json_encode(['success' => true, 'month' => $month, 'data' => $events]);
