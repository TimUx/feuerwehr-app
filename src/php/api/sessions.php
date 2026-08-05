<?php
/**
 * Remember-me session management API
 */

header('Content-Type: application/json; charset=UTF-8');
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../datastore.php';

Auth::init();
sendSecurityHeaders();
Auth::requireAuth();

$method = $_SERVER['REQUEST_METHOD'];
$user = Auth::getUser();

try {
    if ($method === 'GET') {
        $forUser = Auth::isAdmin() && isset($_GET['all']) ? null : $user['id'];
        $sessions = Auth::listRememberSessions($forUser);

        // Enrich with username for admin overview
        if (Auth::isAdmin() && $forUser === null) {
            $usersById = [];
            foreach (Auth::listUsers() as $u) {
                $usersById[$u['id']] = $u['username'];
            }
            foreach ($sessions as &$s) {
                $s['username'] = $usersById[$s['user_id']] ?? '–';
            }
            unset($s);
        }

        echo json_encode(['success' => true, 'data' => $sessions]);
        exit;
    }

    if ($method === 'DELETE' || ($method === 'POST' && ($_GET['action'] ?? '') === 'revoke')) {
        Auth::requireCsrfToken();
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $sessionId = $input['id'] ?? '';
        $revokeAll = !empty($input['all']);

        if ($revokeAll) {
            $targetUser = $user['id'];
            if (Auth::isAdmin() && !empty($input['user_id'])) {
                $targetUser = $input['user_id'];
            }
            $count = Auth::revokeAllRememberSessions($targetUser);
            DataStore::audit('session.revoke_all', ['user_id' => $targetUser, 'count' => $count]);
            echo json_encode(['success' => true, 'message' => "$count Sitzung(en) widerrufen", 'count' => $count]);
            exit;
        }

        if ($sessionId === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Sitzungs-ID fehlt']);
            exit;
        }

        $restrict = Auth::isAdmin() ? null : $user['id'];
        $ok = Auth::revokeRememberSession($sessionId, $restrict);
        if ($ok) {
            DataStore::audit('session.revoke', ['session_id' => $sessionId]);
            echo json_encode(['success' => true, 'message' => 'Sitzung widerrufen']);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Sitzung nicht gefunden']);
        }
        exit;
    }

    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Methode nicht erlaubt']);
} catch (Exception $e) {
    http_response_code(500);
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Interner Fehler']);
}
