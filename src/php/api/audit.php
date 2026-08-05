<?php
/**
 * Audit log API (Admin)
 */

header('Content-Type: application/json; charset=UTF-8');
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../datastore.php';

Auth::init();
sendSecurityHeaders();
Auth::requireAdmin();

$limit = min(500, max(1, (int)($_GET['limit'] ?? 200)));
$entries = DataStore::getAuditLog($limit);

echo json_encode(['success' => true, 'data' => $entries]);
