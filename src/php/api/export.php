<?php
/**
 * Export / Backup API (Admin)
 * GET  ?action=list|download&format=json|csv&datasets=personnel,missions
 * POST ?action=backup  → create full snapshot
 */

header('Content-Type: application/json; charset=UTF-8');
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../datastore.php';

Auth::init();
sendSecurityHeaders();
Auth::requireAdmin();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'list';

try {
    if ($method === 'POST') {
        Auth::requireCsrfToken();
        if ($action === 'backup') {
            $path = DataStore::createFullBackup();
            if (!$path) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Backup konnte nicht erstellt werden']);
                exit;
            }
            echo json_encode([
                'success' => true,
                'message' => 'Vollbackup erstellt',
                'id' => basename($path),
            ]);
            exit;
        }
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Ungültige Aktion']);
        exit;
    }

    if ($method !== 'GET') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Methode nicht erlaubt']);
        exit;
    }

    if ($action === 'list') {
        echo json_encode([
            'success' => true,
            'backups' => DataStore::listFullBackups(),
            'datasets' => ['personnel', 'vehicles', 'locations', 'attendance', 'missions', 'phone_numbers', 'settings', 'audit'],
        ]);
        exit;
    }

    if ($action === 'download') {
        $format = strtolower($_GET['format'] ?? 'json');
        $datasetsParam = $_GET['datasets'] ?? 'personnel,vehicles,locations,attendance,missions,phone_numbers,settings,audit';
        $keys = array_values(array_filter(array_map('trim', explode(',', $datasetsParam))));
        $data = DataStore::exportDatasets($keys);
        DataStore::audit('export.download', ['format' => $format, 'datasets' => $keys]);

        $stamp = date('Ymd_His');
        if ($format === 'csv') {
            // Flatten to one CSV per primary table; zip-like multi-section as text
            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="feuerwehr_export_' . $stamp . '.csv"');
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM
            foreach ($data as $name => $rows) {
                fputcsv($out, ['# Dataset: ' . $name], ';');
                if (empty($rows) || !is_array($rows)) {
                    fputcsv($out, ['(leer)'], ';');
                    fputcsv($out, [], ';');
                    continue;
                }
                // If list of assoc arrays
                if (isset($rows[0]) && is_array($rows[0])) {
                    $headers = array_keys($rows[0]);
                    fputcsv($out, $headers, ';');
                    foreach ($rows as $row) {
                        $line = [];
                        foreach ($headers as $h) {
                            $val = $row[$h] ?? '';
                            if (is_array($val)) {
                                $val = json_encode($val, JSON_UNESCAPED_UNICODE);
                            }
                            $line[] = $val;
                        }
                        fputcsv($out, $line, ';');
                    }
                } else {
                    // Flat associative (settings)
                    fputcsv($out, ['key', 'value'], ';');
                    foreach ($rows as $k => $v) {
                        if (is_array($v)) {
                            $v = json_encode($v, JSON_UNESCAPED_UNICODE);
                        }
                        fputcsv($out, [$k, $v], ';');
                    }
                }
                fputcsv($out, [], ';');
            }
            fclose($out);
            exit;
        }

        header('Content-Type: application/json; charset=UTF-8');
        header('Content-Disposition: attachment; filename="feuerwehr_export_' . $stamp . '.json"');
        echo json_encode([
            'exported_at' => date('c'),
            'datasets' => $data,
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Ungültige Aktion']);
} catch (Exception $e) {
    http_response_code(500);
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Interner Fehler']);
}
