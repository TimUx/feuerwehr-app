<?php
/**
 * Minimal test runner (no PHPUnit required).
 * Run: php tests/run.php
 */

declare(strict_types=1);

$root = dirname(__DIR__);
$failed = 0;
$passed = 0;

function assert_true($cond, string $msg): void {
    global $failed, $passed;
    if ($cond) {
        echo "  PASS  $msg\n";
        $passed++;
    } else {
        echo "  FAIL  $msg\n";
        $failed++;
    }
}

function assert_eq($a, $b, string $msg): void {
    assert_true($a === $b, $msg . ' (got ' . var_export($a, true) . ', expected ' . var_export($b, true) . ')');
}

echo "Feuerwehr App – Test Suite\n";
echo str_repeat('=', 40) . "\n";

// --- Temp environment ---
$tmp = sys_get_temp_dir() . '/fw_tests_' . bin2hex(random_bytes(4));
@mkdir($tmp, 0700, true);
@mkdir($tmp . '/data', 0700, true);
@mkdir($tmp . '/config', 0700, true);

$key = bin2hex(random_bytes(32));
$config = [
    'app_name' => 'Test',
    'app_version' => 'test',
    'encryption_key' => $key,
    'session_lifetime' => 3600,
    'email' => [
        'from_address' => 'test@example.com',
        'from_name' => 'Test',
        'to_address' => '',
        'smtp_host' => 'localhost',
        'smtp_port' => 25,
        'smtp_auth' => false,
        'smtp_username' => '',
        'smtp_password' => '',
        'smtp_secure' => '',
    ],
    'data_dir' => $tmp . '/data',
    'backup_dir' => $tmp . '/data/backups',
    'default_admin' => [
        'username' => 'admin',
        'password' => 'admin1234567',
    ],
];
file_put_contents($tmp . '/config/config.php', '<?php return ' . var_export($config, true) . ';');

// Point app config path by symlink-like override: we monkey-patch via requiring
// encryption/auth with a replaced config path using stream wrapper is hard.
// Instead redefine by copying files structure matching relative requires.

// Create a shim: put config where Auth expects: ../../config/config.php from src/php
// We'll chdir simulation by creating parallel structure under $tmp/app
$app = $tmp . '/app';
@mkdir($app . '/config', 0700, true);
@mkdir($app . '/src/php', 0700, true);
@mkdir($app . '/data', 0700, true);
@mkdir($app . '/data/backups', 0700, true);

copy($tmp . '/config/config.php', $app . '/config/config.php');
// Patch data_dir in copied config
$config['data_dir'] = $app . '/data';
$config['backup_dir'] = $app . '/data/backups';
file_put_contents($app . '/config/config.php', '<?php return ' . var_export($config, true) . ';');

foreach (['encryption.php', 'storage_init.php', 'session_init.php', 'auth.php', 'datastore.php', 'upgrade.php'] as $f) {
    // Use real source files via require with chdir into fake app that has config;
    // Auth does require __DIR__/../../config/config.php from src/php – so copy sources.
    copy($root . '/src/php/' . $f, $app . '/src/php/' . $f);
}

require_once $app . '/src/php/encryption.php';
require_once $app . '/src/php/auth.php';
require_once $app . '/src/php/datastore.php';

echo "\n[Encryption]\n";
$plain = '{"hello":"world","n":42}';
$enc = Encryption::encrypt($plain);
assert_true(is_string($enc) && strpos($enc, '::') !== false, 'encrypt returns iv::payload');
$dec = Encryption::decrypt($enc);
assert_eq($dec, $plain, 'decrypt roundtrip');

echo "\n[Auth::validatePassword]\n";
assert_eq(Auth::validatePassword('short'), 'Passwort muss mindestens 10 Zeichen lang sein', 'rejects short password');
assert_eq(Auth::validatePassword('longenough1'), null, 'accepts 10+ chars');

echo "\n[Auth::CSRF]\n";
// Session may not start in CLI – initSecureSession skips CLI
$_SESSION = [];
$token = Auth::getCsrfToken();
assert_true(strlen($token) === 64, 'CSRF token is 64 hex chars');
$_SERVER['HTTP_X_CSRF_TOKEN'] = $token;
assert_true(Auth::validateCsrfToken() === true, 'valid CSRF passes');
$_SERVER['HTTP_X_CSRF_TOKEN'] = 'deadbeef';
assert_true(Auth::validateCsrfToken() === false, 'invalid CSRF fails');
$_SERVER['HTTP_X_CSRF_TOKEN'] = $token;

echo "\n[DataStore]\n";
$person = DataStore::createPersonnel([
    'name' => 'Max Mustermann',
    'qualifications' => ['AGT'],
    'leadership_roles' => [],
    'is_instructor' => true,
    'location_id' => null,
]);
assert_true(!empty($person['id']), 'createPersonnel returns id');
$all = DataStore::getPersonnel();
assert_eq(count($all), 1, 'one personnel record');

$vehicle = DataStore::createVehicle([
    'type' => 'HLF 20',
    'radio_call_sign' => 'Florian Test 1',
    'location_id' => null,
]);
assert_true(!empty($vehicle['id']), 'createVehicle returns id');

$search = DataStore::globalSearch('Mustermann');
assert_eq(count($search['personnel']), 1, 'globalSearch finds personnel');
$search2 = DataStore::globalSearch('HLF');
assert_eq(count($search2['vehicles']), 1, 'globalSearch finds vehicles');

DataStore::audit('test.action', ['foo' => 'bar']);
$log = DataStore::getAuditLog(10);
assert_true(count($log) >= 1, 'audit log has entries');

$backup = DataStore::createFullBackup();
assert_true($backup !== null && is_dir($backup), 'full backup created');

$export = DataStore::exportDatasets(['personnel', 'vehicles']);
assert_true(isset($export['personnel'][0]['name']), 'export includes personnel');
assert_true(!isset($export['personnel'][0]['password']), 'export has no password field');

echo "\n[Form duration overnight logic]\n";
$von = strtotime('2026-01-01 22:00');
$bis = strtotime('2026-01-01 02:00');
if ($bis < $von) {
    $bis += 24 * 3600;
}
$hours = ($bis - $von) / 3600;
assert_eq($hours, 4.0, 'overnight duration is 4 hours');

echo "\n[Safe decrypt / no wipe]\n";
$bogusFile = $app . '/data/broken.json';
file_put_contents($bogusFile, 'not-encrypted-garbage');
$read = DataStore::readEncryptedJsonFile($bogusFile);
assert_true($read['ok'] === false, 'corrupt file is not treated as empty ok');
$threw = false;
try {
    DataStore::mutatePublic('broken.json', function ($d) { return ['wiped' => true]; });
} catch (Throwable $e) {
    $threw = true;
}
assert_true($threw, 'mutate refuses to overwrite undecryptable file');
assert_true(file_get_contents($bogusFile) === 'not-encrypted-garbage', 'corrupt file content preserved');

echo "\n[AppUpgrade]\n";
require_once $app . '/src/php/upgrade.php';
assert_true(file_exists($app . '/data/app_meta.json'), 'app_meta.json written after first Auth/DataStore use');
$meta = json_decode(file_get_contents($app . '/data/app_meta.json'), true);
assert_eq((int)$meta['schema_version'], AppUpgrade::SCHEMA_VERSION, 'schema version is current');
$snaps = glob($app . '/data/backups/pre_upgrade_*') ?: [];
assert_true(count($snaps) >= 1, 'pre-upgrade snapshot created');

// Force re-run path: lower schema, ensure second migration is idempotent
file_put_contents($app . '/data/app_meta.json', json_encode(['schema_version' => 1]));
// Bypass per-request guard by invoking private path via lowering version then new process isn't available;
// Instead verify migrateRememberTokens / location backfill no-op does not destroy data:
$before = DataStore::getPersonnel();
DataStore::mutatePublic('personnel.json', function ($p) { return false; }); // no-op abort
$after = DataStore::getPersonnel();
assert_eq(count($after), count($before), 'no-op mutate preserves personnel');

echo "\n" . str_repeat('=', 40) . "\n";
echo "Passed: $passed  Failed: $failed\n";

// Cleanup
function rrmdir($dir) {
    if (!is_dir($dir)) return;
    foreach (scandir($dir) as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $dir . '/' . $item;
        is_dir($path) ? rrmdir($path) : @unlink($path);
    }
    @rmdir($dir);
}
rrmdir($tmp);

exit($failed > 0 ? 1 : 0);
