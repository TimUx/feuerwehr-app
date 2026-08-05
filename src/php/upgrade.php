<?php
/**
 * Idempotent upgrade / migration runner for existing installations.
 *
 * Goals:
 * - Never overwrite config.php or the encryption key
 * - Never wipe data files if decryption fails
 * - Create a pre-upgrade snapshot before schema migrations
 * - Bring legacy records (remember tokens, location_id, email settings) forward
 */

require_once __DIR__ . '/encryption.php';
require_once __DIR__ . '/storage_init.php';

class AppUpgrade {
    /** Bump when adding a new migration step */
    public const SCHEMA_VERSION = 2;

    /**
     * Run pending migrations once per request when behind schema.
     * Safe to call from Auth::init() / login / API.
     */
    public static function runIfNeeded(): void {
        static $checkedThisRequest = false;
        if ($checkedThisRequest) {
            return;
        }
        $checkedThisRequest = true;

        if (php_sapi_name() !== 'cli' && !file_exists(__DIR__ . '/../../config/config.php')) {
            return; // installer not finished
        }

        try {
            $config = require __DIR__ . '/../../config/config.php';
            $dataDir = $config['data_dir'] ?? (__DIR__ . '/../../data');
            $backupDir = $config['backup_dir'] ?? ($dataDir . '/backups');

            initDataDirectory($dataDir);
            if (!is_dir($backupDir)) {
                @mkdir($backupDir, 0700, true);
            }

            $metaPath = $dataDir . '/app_meta.json';
            $meta = self::readMeta($metaPath);
            $from = (int)($meta['schema_version'] ?? 0);
            $originalFrom = $from;

            if ($from >= self::SCHEMA_VERSION) {
                return;
            }

            // Integrity check: refuse upgrade wipe if core files decrypt badly
            $integrity = self::checkCoreIntegrity($dataDir);
            if (!$integrity['ok']) {
                error_log('AppUpgrade aborted: ' . $integrity['message']);
                // Do not bump schema version – keep retrying safely
                return;
            }

            // Snapshot before mutating anything
            self::createPreUpgradeSnapshot($dataDir, $backupDir, $originalFrom);

            if ($from < 1) {
                self::migrateTo1($dataDir, $config);
                $from = 1;
            }
            if ($from < 2) {
                self::migrateTo2($dataDir, $config);
                $from = 2;
            }

            $meta['previous_schema'] = $originalFrom;
            $meta['schema_version'] = self::SCHEMA_VERSION;
            $meta['app_version'] = $config['app_version'] ?? 'unknown';
            $meta['upgraded_at'] = date('c');
            self::writeMeta($metaPath, $meta);

            error_log('AppUpgrade: migrated to schema ' . self::SCHEMA_VERSION);
        } catch (Throwable $e) {
            error_log('AppUpgrade failed (data left unchanged where possible): ' . $e->getMessage());
        }
    }

    private static function readMeta(string $path): array {
        if (!file_exists($path)) {
            return ['schema_version' => 0];
        }
        // Meta is plaintext JSON (not secrets) so upgrades can run even if encryption has issues
        $raw = @file_get_contents($path);
        $data = $raw ? json_decode($raw, true) : null;
        return is_array($data) ? $data : ['schema_version' => 0];
    }

    private static function writeMeta(string $path, array $meta): void {
        file_put_contents($path, json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
        @chmod($path, 0640);
    }

    /**
     * Verify users + at least that existing encrypted files decrypt or are empty.
     */
    private static function checkCoreIntegrity(string $dataDir): array {
        require_once __DIR__ . '/datastore.php';
        $critical = ['users.json'];
        foreach ($critical as $file) {
            $path = $dataDir . '/' . $file;
            if (!file_exists($path)) {
                continue; // fresh install edge case
            }
            $result = DataStore::readEncryptedJsonFile($path);
            if (!$result['ok']) {
                return [
                    'ok' => false,
                    'message' => "Cannot decrypt {$file} ({$result['error']}). Check encryption_key in config.php – refusing upgrade writes.",
                ];
            }
        }

        // Soft-check other data files: log but don't hard-fail if one optional file is bad
        foreach (glob($dataDir . '/*.json') ?: [] as $path) {
            $base = basename($path);
            if ($base === 'app_meta.json' || substr($base, -5) === '.lock') {
                continue;
            }
            // Skip known plaintext helpers if any
            if ($base === 'geocode_cache.json') {
                continue;
            }
            // Encrypted store files
            if (in_array($base, ['users.json', 'personnel.json', 'vehicles.json', 'locations.json', 'attendance.json', 'missions.json', 'phone-numbers.json', 'settings.json', 'email_settings.json', 'remember_tokens.json', 'password_reset_tokens.json', 'rate_limits.json', 'audit.json'], true)) {
                $result = DataStore::readEncryptedJsonFile($path);
                if (!$result['ok'] && !$result['empty']) {
                    error_log("AppUpgrade warning: {$base} decrypt issue: {$result['error']}");
                }
            }
        }

        return ['ok' => true, 'message' => 'ok'];
    }

    private static function createPreUpgradeSnapshot(string $dataDir, string $backupDir, int $fromVersion): void {
        $snap = $backupDir . '/pre_upgrade_v' . $fromVersion . '_' . date('Ymd_His');
        if (!@mkdir($snap, 0700, true) && !is_dir($snap)) {
            error_log('AppUpgrade: could not create snapshot dir ' . $snap);
            return;
        }
        foreach (glob($dataDir . '/*.json') ?: [] as $file) {
            $base = basename($file);
            if ($base === 'app_meta.json') {
                continue;
            }
            @copy($file, $snap . '/' . $base);
        }
        @file_put_contents($snap . '/README.txt', "Pre-upgrade snapshot from schema {$fromVersion}\nCreated: " . date('c') . "\n");
    }

    /**
     * Schema 1: ensure directories, migrate email settings from config if missing,
     * normalize remember-me tokens with ids.
     */
    private static function migrateTo1(string $dataDir, array $config): void {
        require_once __DIR__ . '/datastore.php';

        // Ensure uploads / settings dirs
        foreach (['uploads', 'settings', 'tmp', 'backups'] as $sub) {
            $dir = $dataDir . '/' . $sub;
            if ($sub === 'backups') {
                $dir = $config['backup_dir'] ?? $dir;
            }
            if (!is_dir($dir)) {
                @mkdir($dir, 0700, true);
            }
        }

        // Email settings file: create from config.php if absent (non-destructive)
        $emailFile = $dataDir . '/email_settings.json';
        if (!file_exists($emailFile)) {
            $legacy = $config['email'] ?? [];
            if (!empty($legacy)) {
                try {
                    DataStore::updateEmailSettings([
                        'smtp_host'     => $legacy['smtp_host'] ?? '',
                        'smtp_port'     => (int)($legacy['smtp_port'] ?? 587),
                        'smtp_auth'     => !empty($legacy['smtp_auth']),
                        'smtp_username' => $legacy['smtp_username'] ?? '',
                        'smtp_password' => $legacy['smtp_password'] ?? '',
                        'smtp_secure'   => $legacy['smtp_secure'] ?? 'tls',
                        'from_address'  => $legacy['from_address'] ?? '',
                        'from_name'     => $legacy['from_name'] ?? 'Feuerwehr Management System',
                        'to_address'    => $legacy['to_address'] ?? '',
                        'contact_email' => '',
                    ]);
                } catch (Throwable $e) {
                    error_log('AppUpgrade migrateTo1 email: ' . $e->getMessage());
                }
            }
        }

        self::migrateRememberTokens($dataDir);
    }

    /**
     * Schema 2: backfill location_id from legacy fields; ensure attendance/missions usable.
     */
    private static function migrateTo2(string $dataDir, array $config): void {
        require_once __DIR__ . '/datastore.php';

        $locations = DataStore::getLocations();
        $byName = [];
        foreach ($locations as $loc) {
            if (!empty($loc['name']) && !empty($loc['id'])) {
                $byName[mb_strtolower(trim($loc['name']))] = $loc['id'];
            }
        }

        // Vehicles: location (name) → location_id
        DataStore::mutatePublic('vehicles.json', function ($vehicles) use ($byName) {
            if (!is_array($vehicles)) {
                return false;
            }
            $changed = false;
            foreach ($vehicles as &$v) {
                if (empty($v['location_id']) && !empty($v['location'])) {
                    $key = mb_strtolower(trim((string)$v['location']));
                    if (isset($byName[$key])) {
                        $v['location_id'] = $byName[$key];
                        $changed = true;
                    }
                }
            }
            unset($v);
            return $changed ? $vehicles : false;
        });

        // Attendance / missions: standort name or id normalization
        foreach (['attendance.json', 'missions.json'] as $file) {
            $path = $dataDir . '/' . $file;
            if (!file_exists($path)) {
                continue;
            }
            DataStore::mutatePublic($file, function ($rows) use ($byName) {
                if (!is_array($rows)) {
                    return false;
                }
                $changed = false;
                foreach ($rows as &$r) {
                    if (empty($r['location_id'])) {
                        $standort = $r['standort'] ?? null;
                        if (is_string($standort) && $standort !== '') {
                            if (strpos($standort, 'loc_') === 0) {
                                $r['location_id'] = $standort;
                                $changed = true;
                            } else {
                                $key = mb_strtolower(trim($standort));
                                if (isset($byName[$key])) {
                                    $r['location_id'] = $byName[$key];
                                    $changed = true;
                                }
                            }
                        }
                    }
                    if (empty($r['date'])) {
                        if (!empty($r['datum'])) {
                            $r['date'] = $r['datum'];
                            $changed = true;
                        } elseif (!empty($r['einsatzdatum'])) {
                            $r['date'] = $r['einsatzdatum'];
                            $changed = true;
                        }
                    }
                }
                unset($r);
                return $changed ? $rows : false;
            });
        }

        self::migrateRememberTokens($dataDir);
    }

    private static function migrateRememberTokens(string $dataDir): void {
        require_once __DIR__ . '/datastore.php';
        $file = 'remember_tokens.json';
        $path = $dataDir . '/' . $file;
        if (!file_exists($path)) {
            return;
        }
        try {
            DataStore::mutatePublic($file, function ($tokens) {
                if (!is_array($tokens)) {
                    return false;
                }
                $changed = false;
                $now = time();
                $out = [];
                foreach ($tokens as $item) {
                    if (!is_array($item)) {
                        continue;
                    }
                    // Drop expired
                    if (($item['expiry'] ?? 0) <= $now) {
                        $changed = true;
                        continue;
                    }
                    if (empty($item['id'])) {
                        $item['id'] = 'rem_' . bin2hex(random_bytes(8));
                        $changed = true;
                    }
                    if (!isset($item['user_agent'])) {
                        $item['user_agent'] = 'legacy';
                        $changed = true;
                    }
                    if (!array_key_exists('ip', $item)) {
                        $item['ip'] = null;
                        $changed = true;
                    }
                    $out[] = $item;
                }
                return $changed ? $out : false;
            });
        } catch (Throwable $e) {
            error_log('AppUpgrade remember_tokens: ' . $e->getMessage());
        }
    }
}
