<?php
/**
 * Lightweight health check – no auth required.
 * Returns JSON with status of critical runtime dependencies.
 */

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');

$ok = true;
$checks = [];

$configPath = __DIR__ . '/config/config.php';
$configExists = file_exists($configPath);
$checks['config'] = [
    'ok' => $configExists,
    'detail' => $configExists ? 'present' : 'missing',
];
if (!$configExists) {
    $ok = false;
}

$dataDir = null;
$emailConfig = [];
if ($configExists) {
    try {
        $config = require $configPath;
        $dataDir = $config['data_dir'] ?? (__DIR__ . '/data');
        $key = $config['encryption_key'] ?? '';
        $encryptionOk = is_string($key) && (strlen($key) === 64 || strlen($key) === 32);
        $checks['encryption_key'] = [
            'ok' => $encryptionOk,
            'detail' => $encryptionOk ? 'configured' : 'invalid',
        ];
        if (!$encryptionOk) {
            $ok = false;
        }
        $emailConfig = $config['email'] ?? [];
    } catch (Throwable $e) {
        $ok = false;
        $checks['config'] = ['ok' => false, 'detail' => 'unreadable'];
    }
}

if ($dataDir) {
    $writable = is_dir($dataDir) && is_writable($dataDir);
    $checks['data_dir'] = [
        'ok' => $writable,
        'detail' => $writable ? 'writable' : 'not writable',
    ];
    if (!$writable) {
        $ok = false;
    }

    $backupDir = ($config['backup_dir'] ?? ($dataDir . '/backups'));
    $backupOk = is_dir($backupDir) ? is_writable($backupDir) : (@mkdir($backupDir, 0700, true) && is_writable($backupDir));
    $checks['backup_dir'] = [
        'ok' => (bool)$backupOk,
        'detail' => $backupOk ? 'writable' : 'not writable',
    ];

    $diskFree = @disk_free_space($dataDir);
    $diskTotal = @disk_total_space($dataDir);
    $checks['disk'] = [
        'ok' => $diskFree === false ? true : ($diskFree > 5 * 1024 * 1024),
        'free_bytes' => $diskFree === false ? null : (int) $diskFree,
        'total_bytes' => $diskTotal === false ? null : (int) $diskTotal,
    ];
    if ($diskFree !== false && $diskFree <= 5 * 1024 * 1024) {
        $ok = false;
    }
}

$checks['php_extensions'] = [
    'ok' => extension_loaded('openssl') && extension_loaded('json') && extension_loaded('mbstring'),
    'openssl' => extension_loaded('openssl'),
    'json' => extension_loaded('json'),
    'mbstring' => extension_loaded('mbstring'),
];
if (!$checks['php_extensions']['ok']) {
    $ok = false;
}

// Schema / upgrade status (plaintext meta – safe if encryption ok)
$metaPath = $dataDir ? ($dataDir . '/app_meta.json') : null;
$schemaVersion = null;
if ($metaPath && file_exists($metaPath)) {
    $meta = json_decode((string)@file_get_contents($metaPath), true);
    $schemaVersion = is_array($meta) ? ($meta['schema_version'] ?? null) : null;
}
$expectedSchema = 2;
$checks['schema'] = [
    'ok' => $schemaVersion === null || (int)$schemaVersion >= $expectedSchema || $schemaVersion === 0,
    'current' => $schemaVersion,
    'expected' => $expectedSchema,
    'detail' => $schemaVersion === null
        ? 'pending first request'
        : ('v' . (int)$schemaVersion),
];

// Data decrypt smoke test (users.json if present)
if ($dataDir && file_exists($dataDir . '/users.json') && file_exists(__DIR__ . '/src/php/datastore.php')) {
    try {
        require_once __DIR__ . '/src/php/encryption.php';
        require_once __DIR__ . '/src/php/datastore.php';
        $read = DataStore::readEncryptedJsonFile($dataDir . '/users.json');
        $checks['users_decrypt'] = [
            'ok' => $read['ok'],
            'detail' => $read['ok'] ? ('ok (' . count($read['data']) . ' users)') : $read['error'],
        ];
        if (!$read['ok']) {
            $ok = false;
        }
    } catch (Throwable $e) {
        $checks['users_decrypt'] = ['ok' => false, 'detail' => $e->getMessage()];
        $ok = false;
    }
}

// SMTP reachability (TCP connect only – does not send mail)
$smtpHost = $emailConfig['smtp_host'] ?? '';
$smtpPort = (int)($emailConfig['smtp_port'] ?? 0);
if ($dataDir && file_exists($dataDir . '/email_settings.json') && file_exists(__DIR__ . '/src/php/encryption.php')) {
    try {
        require_once __DIR__ . '/src/php/encryption.php';
        require_once __DIR__ . '/src/php/datastore.php';
        $stored = DataStore::getEmailSettings();
        if (!empty($stored['smtp_host'])) {
            $smtpHost = $stored['smtp_host'];
            $smtpPort = (int)($stored['smtp_port'] ?? 587);
        }
    } catch (Throwable $e) {
        // ignore – fall back to config.php values
    }
}

if ($smtpHost !== '' && $smtpPort > 0) {
    $errno = 0;
    $errstr = '';
    $fp = @fsockopen($smtpHost, $smtpPort, $errno, $errstr, 3);
    $smtpOk = is_resource($fp);
    if ($smtpOk) {
        fclose($fp);
    }
    $checks['smtp'] = [
        'ok' => $smtpOk,
        'host' => $smtpHost,
        'port' => $smtpPort,
        'detail' => $smtpOk ? 'reachable' : trim($errstr ?: ('error ' . $errno)),
    ];
    // SMTP down is warning – do not flip overall status to degraded for optional mail
} else {
    $checks['smtp'] = [
        'ok' => true,
        'detail' => 'not configured',
    ];
}

http_response_code($ok ? 200 : 503);
echo json_encode([
    'status' => $ok ? 'ok' : 'degraded',
    'app' => 'feuerwehr-app',
    'time' => date('c'),
    'checks' => $checks,
], JSON_PRETTY_PRINT);
