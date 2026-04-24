<?php
/**
 * Send ntfy notifications using per-location configuration (server-side POST).
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../datastore.php';

Auth::init();
sendSecurityHeaders();

if (!Auth::isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Nicht authentifiziert']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Methode nicht erlaubt']);
    exit;
}

Auth::requireCsrfToken();

/**
 * @return array{ok:bool,error?:string,http_code?:int}
 */
function feuerwehr_ntfy_publish(string $publishUrl, string $message, ?string $title, ?string $bearerToken, ?int $ttlSeconds): array {
    $parts = parse_url($publishUrl);
    if ($parts === false || empty($parts['scheme']) || empty($parts['host'])) {
        return ['ok' => false, 'error' => 'Ungültige ntfy-URL'];
    }
    $scheme = strtolower($parts['scheme']);
    if (!in_array($scheme, ['http', 'https'], true)) {
        return ['ok' => false, 'error' => 'Nur http/https-URLs sind erlaubt'];
    }

    $headers = ['Content-Type: text/plain; charset=utf-8'];
    if ($title !== null && $title !== '') {
        $headers[] = 'Title: ' . str_replace(["\r", "\n"], '', $title);
    }
    if ($ttlSeconds !== null && $ttlSeconds > 0) {
        $headers[] = 'X-Ntfy-TTL: ' . $ttlSeconds;
    }
    if ($bearerToken !== null && $bearerToken !== '') {
        $headers[] = 'Authorization: Bearer ' . str_replace(["\r", "\n"], '', $bearerToken);
    }

    $ch = curl_init($publishUrl);
    if ($ch === false) {
        return ['ok' => false, 'error' => 'curl_init fehlgeschlagen'];
    }
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $message,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_TIMEOUT => 25,
        CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
    ]);
    curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err !== '') {
        return ['ok' => false, 'error' => $err, 'http_code' => $httpCode];
    }
    if ($httpCode >= 200 && $httpCode < 300) {
        return ['ok' => true, 'http_code' => $httpCode];
    }
    return ['ok' => false, 'error' => 'ntfy HTTP ' . $httpCode, 'http_code' => $httpCode];
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Ungültige Anfrage']);
        exit;
    }

    $message = isset($data['message']) ? trim((string)$data['message']) : '';
    if ($message === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Nachrichtentext ist erforderlich']);
        exit;
    }
    if (strlen($message) > 4096) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Nachricht ist zu lang (max. 4096 Zeichen)']);
        exit;
    }

    $scope = isset($data['scope']) ? (string)$data['scope'] : 'own';
    if (!in_array($scope, ['own', 'all'], true)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Ungültiger Empfangsbereich']);
        exit;
    }

    $title = isset($data['title']) ? trim((string)$data['title']) : '';
    $title = $title === '' ? null : $title;

    $ttl = null;
    if (isset($data['ttl']) && $data['ttl'] !== '' && $data['ttl'] !== null) {
        $ttl = (int)$data['ttl'];
        if ($ttl < 1 || $ttl > 86400 * 30) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'TTL muss zwischen 1 und ' . (86400 * 30) . ' Sekunden liegen']);
            exit;
        }
    }

    $canSendAll = Auth::isOperatorOrAbove() && !Auth::hasLocationRestriction();
    if ($scope === 'all' && !$canSendAll) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Keine Berechtigung für alle Standorte']);
        exit;
    }

    $user = Auth::getUser();
    $defaultTitle = 'Feuerwehr (' . ($user['username'] ?? '') . ')';

    $locations = [];
    if ($scope === 'all') {
        $locations = DataStore::getLocations();
    } else {
        $uid = Auth::getUserLocationId();
        if (empty($uid)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Ihrem Benutzer ist kein Standort zugewiesen']);
            exit;
        }
        $one = DataStore::getLocationById($uid);
        if (!$one) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Standort nicht gefunden']);
            exit;
        }
        $locations = [$one];
    }

    $results = [];
    $okCount = 0;
    $skipCount = 0;

    if (count($locations) === 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Keine Standorte vorhanden']);
        exit;
    }

    foreach ($locations as $loc) {
        $id = $loc['id'] ?? '';
        $name = $loc['name'] ?? '';
        $url = isset($loc['ntfy_url']) ? trim((string)$loc['ntfy_url']) : '';
        if ($url === '') {
            $results[] = [
                'location_id' => $id,
                'location_name' => $name,
                'ok' => false,
                'skipped' => true,
                'error' => 'Keine ntfy-URL konfiguriert',
            ];
            $skipCount++;
            continue;
        }

        $token = isset($loc['ntfy_token']) ? (string)$loc['ntfy_token'] : '';
        $token = $token === '' ? null : $token;
        $pubTitle = $title ?? $defaultTitle;

        $r = feuerwehr_ntfy_publish($url, $message, $pubTitle, $token, $ttl);
        if (!empty($r['ok'])) {
            $okCount++;
            $results[] = [
                'location_id' => $id,
                'location_name' => $name,
                'ok' => true,
            ];
        } else {
            $results[] = [
                'location_id' => $id,
                'location_name' => $name,
                'ok' => false,
                'error' => $r['error'] ?? 'Unbekannter Fehler',
            ];
        }
    }

    $failCount = 0;
    foreach ($results as $row) {
        if (empty($row['ok']) && empty($row['skipped'])) {
            $failCount++;
        }
    }
    $success = $okCount > 0 && $failCount === 0;
    $partial = $okCount > 0 && ($failCount > 0 || $skipCount > 0);

    $msgParts = [];
    if ($okCount > 0) {
        $msgParts[] = $okCount . ' Nachricht(en) gesendet';
    }
    if ($skipCount > 0) {
        $msgParts[] = $skipCount . ' ohne ntfy-URL übersprungen';
    }
    if ($failCount > 0) {
        $msgParts[] = $failCount . ' fehlgeschlagen';
    }
    if ($okCount === 0 && $skipCount === count($results)) {
        $summary = 'Kein Standort hat eine ntfy-URL hinterlegt.';
    } elseif ($okCount === 0) {
        $summary = 'Versand fehlgeschlagen.';
    } else {
        $summary = implode(', ', $msgParts) . '.';
    }

    echo json_encode([
        'success' => $success,
        'partial' => $partial,
        'message' => $summary,
        'results' => $results,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Ein interner Fehler ist aufgetreten.']);
}
