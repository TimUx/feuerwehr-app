<?php
/**
 * Data storage class for managing encrypted JSON files
 */

require_once __DIR__ . '/encryption.php';
require_once __DIR__ . '/storage_init.php';

class DataStore {
    private static $config;
    private static $dataDir;

    private static function init() {
        if (!self::$config) {
            self::$config = require __DIR__ . '/../../config/config.php';
            self::$dataDir = self::$config['data_dir'];
        }

        // Delegate directory creation + write-test to the shared helper.
        // The helper uses a static flag so the write-test runs at most
        // once per PHP process regardless of how many classes call it.
        initDataDirectory(self::$dataDir);
    }

    /**
     * Decrypt and JSON-decode an encrypted data file.
     * Distinguishes "missing/empty file" from "corrupt / wrong key".
     *
     * @return array{ok:bool,data:array,empty:bool,error:?string}
     */
    public static function readEncryptedJsonFile(string $filepath): array {
        if (!file_exists($filepath)) {
            return ['ok' => true, 'data' => [], 'empty' => true, 'error' => null];
        }
        $encrypted = file_get_contents($filepath);
        if ($encrypted === false) {
            return ['ok' => false, 'data' => [], 'empty' => false, 'error' => 'unreadable'];
        }
        if (trim($encrypted) === '') {
            return ['ok' => true, 'data' => [], 'empty' => true, 'error' => null];
        }
        $decrypted = Encryption::decrypt($encrypted);
        if ($decrypted === false || $decrypted === null || $decrypted === '') {
            return ['ok' => false, 'data' => [], 'empty' => false, 'error' => 'decrypt_failed'];
        }
        $data = json_decode($decrypted, true);
        if (!is_array($data)) {
            return ['ok' => false, 'data' => [], 'empty' => false, 'error' => 'invalid_json'];
        }
        return ['ok' => true, 'data' => $data, 'empty' => false, 'error' => null];
    }

    /**
     * Load data from encrypted JSON file (unlocked – use mutate() for RMW).
     */
    private static function load($filename) {
        self::init();
        $filepath = self::$dataDir . '/' . $filename;
        $result = self::readEncryptedJsonFile($filepath);
        if (!$result['ok']) {
            error_log('DataStore::load failed for ' . $filename . ': ' . $result['error']);
            throw new Exception('Datenbestand "' . $filename . '" konnte nicht gelesen werden (' . $result['error'] . '). Schreibvorgänge wurden abgebrochen, um Datenverlust zu verhindern.');
        }
        return $result['data'];
    }

    /**
     * Write encrypted JSON to disk (caller must hold the file lock when used from mutate).
     */
    private static function writeEncrypted(string $filepath, $data): void {
        $json = json_encode($data);
        if ($json === false) {
            throw new Exception('JSON encode failed for ' . basename($filepath));
        }
        $encrypted = Encryption::encrypt($json);
        // Atomic replace: write temp then rename to avoid truncated files on crash
        $tmp = $filepath . '.tmp.' . bin2hex(random_bytes(4));
        if (file_put_contents($tmp, $encrypted, LOCK_EX) === false) {
            @unlink($tmp);
            throw new Exception('Failed to write ' . basename($filepath));
        }
        chmod($tmp, 0600);
        if (!@rename($tmp, $filepath)) {
            // Cross-filesystem fallback
            if (!@copy($tmp, $filepath)) {
                @unlink($tmp);
                throw new Exception('Failed to replace ' . basename($filepath));
            }
            @unlink($tmp);
            chmod($filepath, 0600);
        }
    }

    /**
     * Atomically load → modify → save under an exclusive lock.
     * $callback receives the current data array and must return the new array,
     * or false to abort without writing.
     *
     * SAFETY: If an existing file cannot be decrypted, this throws and does NOT write.
     */
    private static function mutate(string $filename, callable $callback) {
        self::init();
        $filepath = self::$dataDir . '/' . $filename;
        $lockPath = $filepath . '.lock';
        $lockFp = fopen($lockPath, 'c+');
        if ($lockFp === false) {
            throw new Exception('Cannot open lock file for ' . $filename);
        }
        if (!flock($lockFp, LOCK_EX)) {
            fclose($lockFp);
            throw new Exception('Cannot lock ' . $filename);
        }
        try {
            $result = self::readEncryptedJsonFile($filepath);
            if (!$result['ok']) {
                throw new Exception('Refuse to mutate ' . $filename . ': ' . $result['error'] . ' (existing data preserved)');
            }
            $data = $result['data'];
            $newData = $callback($data);
            if ($newData === false) {
                return false;
            }
            self::writeEncrypted($filepath, $newData);
            self::rotateBackup($filename);
            return $newData;
        } finally {
            flock($lockFp, LOCK_UN);
            fclose($lockFp);
        }
    }

    /**
     * Public wrapper for locked read-modify-write (used by AppUpgrade migrations).
     */
    public static function mutatePublic(string $filename, callable $callback) {
        return self::mutate($filename, $callback);
    }

    /**
     * Save data to encrypted JSON file (locked).
     */
    private static function save($filename, $data) {
        self::mutate($filename, function () use ($data) {
            return $data;
        });
    }

    /**
     * Keep a rotating copy of critical data files under backup_dir.
     */
    private static function rotateBackup(string $filename): void {
        self::init();
        $source = self::$dataDir . '/' . $filename;
        if (!file_exists($source)) {
            return;
        }
        $backupDir = self::$config['backup_dir'] ?? (self::$dataDir . '/backups');
        if (!is_dir($backupDir)) {
            @mkdir($backupDir, 0700, true);
        }
        if (!is_dir($backupDir) || !is_writable($backupDir)) {
            return;
        }
        $stamp = date('Ymd_His');
        $dest = $backupDir . '/' . pathinfo($filename, PATHINFO_FILENAME) . '_' . $stamp . '.json';
        @copy($source, $dest);
        @chmod($dest, 0600);

        // Keep last 10 backups per file prefix
        $prefix = pathinfo($filename, PATHINFO_FILENAME) . '_';
        $files = glob($backupDir . '/' . $prefix . '*.json') ?: [];
        rsort($files);
        foreach (array_slice($files, 10) as $old) {
            @unlink($old);
        }
    }

    /**
     * Append an audit log entry (best-effort; never throws to callers).
     */
    public static function audit(string $action, array $details = []): void {
        try {
            $user = class_exists('Auth') ? Auth::getUser() : null;
            self::mutate('audit.json', function ($log) use ($action, $details, $user) {
                if (!is_array($log)) {
                    $log = [];
                }
                $log[] = [
                    'id' => 'aud_' . bin2hex(random_bytes(6)),
                    'ts' => date('c'),
                    'action' => $action,
                    'user_id' => $user['id'] ?? null,
                    'username' => $user['username'] ?? null,
                    'details' => $details,
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                ];
                if (count($log) > 1000) {
                    $log = array_slice($log, -1000);
                }
                return $log;
            });
        } catch (Exception $e) {
            error_log('Audit log failed: ' . $e->getMessage());
        }
    }

    /**
     * Return audit log entries (newest first), optionally limited.
     */
    public static function getAuditLog(int $limit = 200): array {
        $log = self::load('audit.json');
        if (!is_array($log)) {
            return [];
        }
        usort($log, function ($a, $b) {
            return strcmp($b['ts'] ?? '', $a['ts'] ?? '');
        });
        return array_slice($log, 0, max(1, $limit));
    }

    /**
     * Create a full snapshot of all encrypted data files into backup_dir.
     * Returns path to the snapshot directory or null on failure.
     */
    public static function createFullBackup(): ?string {
        self::init();
        $backupDir = self::$config['backup_dir'] ?? (self::$dataDir . '/backups');
        if (!is_dir($backupDir)) {
            @mkdir($backupDir, 0700, true);
        }
        if (!is_dir($backupDir) || !is_writable($backupDir)) {
            return null;
        }

        $stamp = date('Ymd_His');
        $snapDir = $backupDir . '/full_' . $stamp;
        if (!@mkdir($snapDir, 0700, true)) {
            return null;
        }

        $files = glob(self::$dataDir . '/*.json') ?: [];
        foreach ($files as $file) {
            $base = basename($file);
            if (substr($base, -5) === '.lock') {
                continue;
            }
            @copy($file, $snapDir . '/' . $base);
        }

        // Prune old full snapshots (keep 15)
        $snaps = glob($backupDir . '/full_*') ?: [];
        rsort($snaps);
        foreach (array_slice($snaps, 15) as $old) {
            if (is_dir($old)) {
                foreach (glob($old . '/*') ?: [] as $f) {
                    @unlink($f);
                }
                @rmdir($old);
            }
        }

        self::audit('backup.full', ['path' => basename($snapDir)]);
        return $snapDir;
    }

    /**
     * List available full backups (newest first).
     */
    public static function listFullBackups(): array {
        self::init();
        $backupDir = self::$config['backup_dir'] ?? (self::$dataDir . '/backups');
        if (!is_dir($backupDir)) {
            return [];
        }
        $snaps = glob($backupDir . '/full_*') ?: [];
        rsort($snaps);
        $out = [];
        foreach ($snaps as $dir) {
            $out[] = [
                'id' => basename($dir),
                'path' => $dir,
                'created' => filemtime($dir) ?: null,
                'files' => count(glob($dir . '/*.json') ?: []),
            ];
        }
        return $out;
    }

    /**
     * Export selected datasets as plain (decrypted) arrays for download.
     * Never includes password hashes or SMTP passwords.
     */
    public static function exportDatasets(array $keys): array {
        $map = [
            'personnel' => fn() => self::getPersonnel(),
            'vehicles' => fn() => self::getVehicles(),
            'locations' => fn() => array_map(function ($loc) {
                unset($loc['ntfy_token']);
                return $loc;
            }, self::getLocations()),
            'attendance' => fn() => self::getAttendanceRecords(),
            'missions' => fn() => self::getMissionReports(),
            'phone_numbers' => fn() => self::getPhoneNumbers(),
            'settings' => fn() => self::getSettings(),
            'audit' => fn() => self::getAuditLog(1000),
        ];

        $export = [];
        foreach ($keys as $key) {
            if (isset($map[$key])) {
                $export[$key] = $map[$key]();
            }
        }
        return $export;
    }

    /**
     * Global search across personnel, vehicles, attendance and missions.
     */
    public static function globalSearch(string $query, ?string $locationId = null): array {
        $q = mb_strtolower(trim($query));
        if ($q === '') {
            return ['personnel' => [], 'vehicles' => [], 'attendance' => [], 'missions' => []];
        }

        $match = function ($haystack) use ($q) {
            return $haystack !== null && mb_strpos(mb_strtolower((string)$haystack), $q) !== false;
        };

        $personnel = array_values(array_filter(
            self::getPersonnelByLocation($locationId),
            fn($p) => $match($p['name'] ?? '')
        ));

        $vehicles = array_values(array_filter(
            self::getVehiclesByLocation($locationId),
            fn($v) => $match($v['type'] ?? '') || $match($v['radio_call_sign'] ?? '')
        ));

        $attendance = array_values(array_filter(
            self::getAttendanceRecordsByLocation($locationId),
            fn($r) => $match($r['thema'] ?? $r['description'] ?? '') || $match($r['datum'] ?? $r['date'] ?? '')
        ));

        $missions = array_values(array_filter(
            self::getMissionReportsByLocation($locationId),
            fn($r) => $match($r['einsatzgrund'] ?? $r['mission_type'] ?? '')
                || $match($r['einsatzort'] ?? $r['location'] ?? '')
                || $match($r['einsatzdatum'] ?? $r['date'] ?? '')
                || $match($r['einsatzleiter'] ?? '')
        ));

        return [
            'personnel' => array_slice($personnel, 0, 50),
            'vehicles' => array_slice($vehicles, 0, 50),
            'attendance' => array_slice($attendance, 0, 50),
            'missions' => array_slice($missions, 0, 50),
        ];
    }

    /**
     * Calendar events for a given month (Y-m).
     */
    public static function getCalendarEvents(string $yearMonth, ?string $locationId = null): array {
        if (!preg_match('/^\d{4}-\d{2}$/', $yearMonth)) {
            $yearMonth = date('Y-m');
        }

        $events = [];
        foreach (self::getAttendanceRecordsByLocation($locationId) as $r) {
            $date = $r['datum'] ?? $r['date'] ?? '';
            if (strpos($date, $yearMonth) === 0) {
                $events[] = [
                    'id' => $r['id'] ?? null,
                    'type' => 'attendance',
                    'date' => $date,
                    'title' => $r['thema'] ?? $r['description'] ?? 'Übung',
                    'meta' => [
                        'von' => $r['von'] ?? null,
                        'bis' => $r['bis'] ?? null,
                    ],
                ];
            }
        }
        foreach (self::getMissionReportsByLocation($locationId) as $r) {
            $date = $r['einsatzdatum'] ?? $r['date'] ?? '';
            if (strpos($date, $yearMonth) === 0) {
                $events[] = [
                    'id' => $r['id'] ?? null,
                    'type' => 'mission',
                    'date' => $date,
                    'title' => $r['einsatzgrund'] ?? $r['mission_type'] ?? 'Einsatz',
                    'meta' => [
                        'ort' => $r['einsatzort'] ?? $r['location'] ?? null,
                    ],
                ];
            }
        }

        usort($events, fn($a, $b) => strcmp($a['date'], $b['date']));
        return $events;
    }

    /**
     * Load records from an encrypted JSON file and apply a filter callback.
     * Only matching records are returned, avoiding loading all data into memory
     * when only a subset is needed.
     */
    private static function loadFiltered(string $filename, callable $filter): array {
        $all = self::load($filename);
        return array_values(array_filter($all, $filter));
    }

    // ==================== Personnel Management ====================

    /**
     * Get all personnel
     */
    public static function getPersonnel() {
        return self::load('personnel.json');
    }

    /**
     * Get personnel filtered by location (if locationId is null, returns all)
     */
    public static function getPersonnelByLocation($locationId = null) {
        $personnel = self::getPersonnel();
        if ($locationId === null) {
            return $personnel;
        }
        return array_values(array_filter($personnel, function($person) use ($locationId) {
            return !isset($person['location_id']) || $person['location_id'] === $locationId;
        }));
    }

    /**
     * Get single personnel by ID
     */
    public static function getPersonnelById($id) {
        $personnel = self::getPersonnel();
        foreach ($personnel as $person) {
            if ($person['id'] === $id) {
                return $person;
            }
        }
        return null;
    }

    /**
     * Create new personnel
     */
    public static function createPersonnel($data) {
        $newPerson = null;
        self::mutate('personnel.json', function ($personnel) use ($data, &$newPerson) {
            $newPerson = [
                'id' => 'pers_' . bin2hex(random_bytes(8)),
                'name' => $data['name'],
                'qualifications' => $data['qualifications'] ?? [],
                'leadership_roles' => $data['leadership_roles'] ?? [],
                'is_instructor' => $data['is_instructor'] ?? false,
                'location_id' => $data['location_id'] ?? null,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            $personnel[] = $newPerson;
            return $personnel;
        });
        self::audit('personnel.create', ['id' => $newPerson['id'] ?? null]);
        return $newPerson;
    }

    /**
     * Update personnel
     */
    public static function updatePersonnel($id, $data) {
        $updated = null;
        self::mutate('personnel.json', function ($personnel) use ($id, $data, &$updated) {
            foreach ($personnel as &$person) {
                if ($person['id'] === $id) {
                    if (isset($data['name'])) {
                        $person['name'] = $data['name'];
                    }
                    if (isset($data['qualifications'])) {
                        $person['qualifications'] = $data['qualifications'];
                    }
                    if (isset($data['leadership_roles'])) {
                        $person['leadership_roles'] = $data['leadership_roles'];
                    }
                    if (isset($data['is_instructor'])) {
                        $person['is_instructor'] = $data['is_instructor'];
                    }
                    if (isset($data['location_id'])) {
                        $person['location_id'] = $data['location_id'];
                    }
                    $person['updated_at'] = date('Y-m-d H:i:s');
                    $updated = $person;
                    break;
                }
            }
            unset($person);
            return $updated === null ? false : $personnel;
        });
        if ($updated) {
            self::audit('personnel.update', ['id' => $id]);
        }
        return $updated;
    }

    /**
     * Delete personnel
     */
    public static function deletePersonnel($id) {
        self::mutate('personnel.json', function ($personnel) use ($id) {
            return array_values(array_filter($personnel, function ($person) use ($id) {
                return $person['id'] !== $id;
            }));
        });
        self::audit('personnel.delete', ['id' => $id]);
        return true;
    }

    // ==================== Vehicle Management ====================

    /**
     * Get all vehicles
     */
    public static function getVehicles() {
        return self::load('vehicles.json');
    }

    /**
     * Get vehicles filtered by location (if locationId is null, returns all)
     */
    public static function getVehiclesByLocation($locationId = null) {
        $vehicles = self::getVehicles();
        if ($locationId === null) {
            return $vehicles;
        }
        return array_values(array_filter($vehicles, function($vehicle) use ($locationId) {
            return !isset($vehicle['location_id']) || $vehicle['location_id'] === $locationId;
        }));
    }

    /**
     * Get single vehicle by ID
     */
    public static function getVehicleById($id) {
        $vehicles = self::getVehicles();
        foreach ($vehicles as $vehicle) {
            if ($vehicle['id'] === $id) {
                return $vehicle;
            }
        }
        return null;
    }

    /**
     * Create new vehicle
     */
    public static function createVehicle($data) {
        $newVehicle = null;
        self::mutate('vehicles.json', function ($vehicles) use ($data, &$newVehicle) {
            $newVehicle = [
                'id' => 'veh_' . bin2hex(random_bytes(8)),
                'location' => $data['location'] ?? null, // Legacy field for backward compatibility
                'location_id' => $data['location_id'] ?? null, // New field - use this for filtering
                'type' => $data['type'],
                'radio_call_sign' => $data['radio_call_sign'],
                'crew_size' => $data['crew_size'] ?? null,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            $vehicles[] = $newVehicle;
            return $vehicles;
        });
        self::audit('vehicle.create', ['id' => $newVehicle['id'] ?? null]);
        return $newVehicle;
    }

    /**
     * Update vehicle
     */
    public static function updateVehicle($id, $data) {
        $updated = null;
        self::mutate('vehicles.json', function ($vehicles) use ($id, $data, &$updated) {
            foreach ($vehicles as &$vehicle) {
                if ($vehicle['id'] === $id) {
                    if (isset($data['location'])) {
                        $vehicle['location'] = $data['location'];
                    }
                    if (isset($data['location_id'])) {
                        $vehicle['location_id'] = $data['location_id'];
                    }
                    if (isset($data['type'])) {
                        $vehicle['type'] = $data['type'];
                    }
                    if (isset($data['radio_call_sign'])) {
                        $vehicle['radio_call_sign'] = $data['radio_call_sign'];
                    }
                    if (isset($data['crew_size'])) {
                        $vehicle['crew_size'] = $data['crew_size'];
                    }
                    $vehicle['updated_at'] = date('Y-m-d H:i:s');
                    $updated = $vehicle;
                    break;
                }
            }
            unset($vehicle);
            return $updated === null ? false : $vehicles;
        });
        if ($updated) {
            self::audit('vehicle.update', ['id' => $id]);
        }
        return $updated;
    }

    /**
     * Delete vehicle
     */
    public static function deleteVehicle($id) {
        self::mutate('vehicles.json', function ($vehicles) use ($id) {
            return array_values(array_filter($vehicles, function ($vehicle) use ($id) {
                return $vehicle['id'] !== $id;
            }));
        });
        self::audit('vehicle.delete', ['id' => $id]);
        return true;
    }

    // ==================== Locations Management ====================

    /**
     * Get all locations
     */
    public static function getLocations() {
        return self::load('locations.json');
    }

    /**
     * Get location name by ID
     * Helper function used in various pages
     */
    public static function getLocationNameById($locationId) {
        if (empty($locationId)) return null;
        
        $locations = self::getLocations();
        foreach ($locations as $location) {
            if ($location['id'] === $locationId) {
                return $location['name'];
            }
        }
        return null;
    }

    /**
     * Remove secrets before exposing a location via JSON API.
     */
    public static function locationForApi(array $location) {
        unset($location['ntfy_token']);
        return $location;
    }

    /**
     * Get single location by ID
     */
    public static function getLocationById($id) {
        $locations = self::getLocations();
        foreach ($locations as $location) {
            if ($location['id'] === $id) {
                return $location;
            }
        }
        return null;
    }

    /**
     * Create new location
     */
    public static function createLocation($data) {
        $newLocation = null;
        self::mutate('locations.json', function ($locations) use ($data, &$newLocation) {
            $newLocation = [
                'id' => 'loc_' . bin2hex(random_bytes(8)),
                'name' => $data['name'],
                'address' => $data['address'] ?? '',
                'email' => $data['email'] ?? '',
                'ntfy_url' => isset($data['ntfy_url']) ? trim((string)$data['ntfy_url']) : '',
                'ntfy_token' => (isset($data['ntfy_token']) && $data['ntfy_token'] !== null)
                    ? trim((string)$data['ntfy_token'])
                    : '',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            $locations[] = $newLocation;
            return $locations;
        });
        self::audit('location.create', ['id' => $newLocation['id'] ?? null]);
        return $newLocation;
    }

    /**
     * Update location
     */
    public static function updateLocation($id, $data) {
        $updated = null;
        self::mutate('locations.json', function ($locations) use ($id, $data, &$updated) {
            foreach ($locations as &$location) {
                if ($location['id'] === $id) {
                    if (isset($data['name'])) {
                        $location['name'] = $data['name'];
                    }
                    if (isset($data['address'])) {
                        $location['address'] = $data['address'];
                    }
                    if (isset($data['email'])) {
                        $location['email'] = $data['email'];
                    }
                    if (isset($data['ntfy_url'])) {
                        $location['ntfy_url'] = trim((string)$data['ntfy_url']);
                    }
                    if (array_key_exists('ntfy_token', $data)) {
                        if ($data['ntfy_token'] === null) {
                            $location['ntfy_token'] = '';
                        } elseif (is_string($data['ntfy_token']) && $data['ntfy_token'] !== '') {
                            $location['ntfy_token'] = trim($data['ntfy_token']);
                        }
                    }
                    $location['updated_at'] = date('Y-m-d H:i:s');
                    $updated = $location;
                    break;
                }
            }
            unset($location);
            return $updated === null ? false : $locations;
        });
        if ($updated) {
            self::audit('location.update', ['id' => $id]);
        }
        return $updated;
    }

    /**
     * Delete location
     */
    public static function deleteLocation($id) {
        self::mutate('locations.json', function ($locations) use ($id) {
            return array_values(array_filter($locations, function ($location) use ($id) {
                return $location['id'] !== $id;
            }));
        });
        self::audit('location.delete', ['id' => $id]);
        return true;
    }

    // ==================== Attendance Records ====================

    /**
     * Get all attendance records
     */
    public static function getAttendanceRecords() {
        return self::load('attendance.json');
    }

    /**
     * Get attendance records filtered by location (if locationId is null, returns all)
     */
    public static function getAttendanceRecordsByLocation($locationId = null) {
        $records = self::getAttendanceRecords();
        if ($locationId === null) {
            return $records;
        }
        return array_values(array_filter($records, function($record) use ($locationId) {
            return !isset($record['location_id']) || $record['location_id'] === $locationId;
        }));
    }

    /**
     * Get single attendance record by ID
     */
    public static function getAttendanceRecordById($id) {
        $records = self::getAttendanceRecords();
        foreach ($records as $record) {
            if ($record['id'] === $id) {
                return $record;
            }
        }
        return null;
    }

    /**
     * Create attendance record
     */
    public static function createAttendanceRecord($data) {
        $newRecord = null;
        self::mutate('attendance.json', function ($records) use ($data, &$newRecord) {
            // Preserve all data fields from the input
            $newRecord = array_merge($data, [
                'id' => $data['id'] ?? 'att_' . bin2hex(random_bytes(8)),
                'date' => $data['date'] ?? $data['datum'] ?? '',
                'type' => $data['type'] ?? 'training',
                'description' => $data['description'] ?? $data['thema'] ?? '',
                'duration_hours' => $data['duration_hours'] ?? 0,
                'attendees' => $data['attendees'] ?? [],
                'created_at' => $data['created_at'] ?? date('Y-m-d H:i:s'),
                'created_by' => $data['created_by'] ?? null
            ]);
            $records[] = $newRecord;
            return $records;
        });
        self::audit('attendance.create', ['id' => $newRecord['id'] ?? null]);
        return $newRecord;
    }

    /**
     * Update attendance record
     */
    public static function updateAttendanceRecord($id, $data) {
        $updated = null;
        self::mutate('attendance.json', function ($records) use ($id, $data, &$updated) {
            foreach ($records as &$record) {
                if ($record['id'] === $id) {
                    // Merge new data with existing record
                    $record = array_merge($record, $data);
                    $record['updated_at'] = date('Y-m-d H:i:s');
                    $updated = $record;
                    break;
                }
            }
            unset($record);
            return $updated === null ? false : $records;
        });
        if ($updated) {
            self::audit('attendance.update', ['id' => $id]);
        }
        return $updated;
    }

    /**
     * Delete attendance record
     */
    public static function deleteAttendanceRecord($id) {
        self::mutate('attendance.json', function ($records) use ($id) {
            return array_values(array_filter($records, function ($record) use ($id) {
                return $record['id'] !== $id;
            }));
        });
        self::audit('attendance.delete', ['id' => $id]);
        return true;
    }

    // ==================== Mission Reports ====================

    /**
     * Get all mission reports
     */
    public static function getMissionReports() {
        return self::load('missions.json');
    }

    /**
     * Get mission reports filtered by location (if locationId is null, returns all)
     */
    public static function getMissionReportsByLocation($locationId = null) {
        $reports = self::getMissionReports();
        if ($locationId === null) {
            return $reports;
        }
        return array_values(array_filter($reports, function($report) use ($locationId) {
            return !isset($report['location_id']) || $report['location_id'] === $locationId;
        }));
    }

    /**
     * Get single mission report by ID
     */
    public static function getMissionReportById($id) {
        $reports = self::getMissionReports();
        foreach ($reports as $report) {
            if ($report['id'] === $id) {
                return $report;
            }
        }
        return null;
    }

    /**
     * Create mission report
     */
    public static function createMissionReport($data) {
        $newReport = null;
        self::mutate('missions.json', function ($reports) use ($data, &$newReport) {
            // Preserve all data fields from the input (like createAttendanceRecord does).
            // The second array overrides any overlapping keys from $data, ensuring
            // canonical fields like 'id' and 'created_at' are always set correctly.
            $newReport = array_merge($data, [
                'id' => $data['id'] ?? 'mis_' . bin2hex(random_bytes(8)),
                'date' => $data['date'] ?? $data['einsatzdatum'] ?? '',
                'mission_type' => $data['mission_type'] ?? $data['einsatzgrund'] ?? '',
                'location' => $data['location'] ?? $data['einsatzort'] ?? '',
                'description' => $data['description'] ?? $data['einsatzlage'] ?? '',
                'participants' => $data['participants'] ?? [],
                'vehicles' => $data['vehicles'] ?? $data['eingesetzte_fahrzeuge'] ?? [],
                'duration_hours' => $data['duration_hours'] ?? 0,
                'location_id' => $data['location_id'] ?? null,
                'created_at' => date('Y-m-d H:i:s'),
                'created_by' => $data['created_by'] ?? null
            ]);
            $reports[] = $newReport;
            return $reports;
        });
        self::audit('mission.create', ['id' => $newReport['id'] ?? null]);
        return $newReport;
    }

    /**
     * Update mission report
     */
    public static function updateMissionReport($id, $data) {
        $updated = null;
        self::mutate('missions.json', function ($reports) use ($id, $data, &$updated) {
            foreach ($reports as &$report) {
                if ($report['id'] === $id) {
                    // Merge new data with existing report
                    $report = array_merge($report, $data);
                    $report['updated_at'] = date('Y-m-d H:i:s');
                    $updated = $report;
                    break;
                }
            }
            unset($report);
            return $updated === null ? false : $reports;
        });
        if ($updated) {
            self::audit('mission.update', ['id' => $id]);
        }
        return $updated;
    }

    /**
     * Delete mission report
     */
    public static function deleteMissionReport($id) {
        self::mutate('missions.json', function ($reports) use ($id) {
            return array_values(array_filter($reports, function ($report) use ($id) {
                return $report['id'] !== $id;
            }));
        });
        self::audit('mission.delete', ['id' => $id]);
        return true;
    }

    // ==================== Statistics ====================

    /**
     * Get statistics for a specific year
     */
    public static function getStatistics($year = null) {
        if (!$year) {
            $year = date('Y');
        }

        $attendance = self::loadFiltered('attendance.json', fn($r) => strpos($r['date'], $year) === 0);
        $missions   = self::loadFiltered('missions.json',   fn($r) => strpos($r['date'], $year) === 0);

        // Calculate overall statistics
        $totalTrainingHours = array_sum(array_column($attendance, 'duration_hours'));
        $totalMissions = count($missions);
        $totalMissionHours = array_sum(array_column($missions, 'duration_hours'));

        return [
            'year' => $year,
            'total_training_sessions' => count($attendance),
            'total_training_hours' => $totalTrainingHours,
            'total_missions' => $totalMissions,
            'total_mission_hours' => $totalMissionHours
        ];
    }

    /**
     * Get statistics for a specific person
     */
    public static function getPersonnelStatistics($personnelId, $year = null) {
        if (!$year) {
            $year = date('Y');
        }

        $personAttendance = self::loadFiltered('attendance.json', fn($r) =>
            strpos($r['date'], $year) === 0 && in_array($personnelId, $r['attendees'] ?? [])
        );
        $personMissions = self::loadFiltered('missions.json', fn($r) =>
            strpos($r['date'], $year) === 0 && in_array($personnelId, $r['participants'] ?? [])
        );

        $trainingHours = array_sum(array_column($personAttendance, 'duration_hours'));
        $missionHours  = array_sum(array_column($personMissions,   'duration_hours'));

        return [
            'personnel_id' => $personnelId,
            'year' => $year,
            'training_sessions' => count($personAttendance),
            'training_hours' => $trainingHours,
            'missions' => count($personMissions),
            'mission_hours' => $missionHours,
            'total_hours' => $trainingHours + $missionHours
        ];
    }

    /**
     * Get statistics for a specific location
     */
    public static function getLocationStatistics($locationId, $year = null) {
        if (!$year) {
            $year = date('Y');
        }

        $locationAttendance = self::loadFiltered('attendance.json', fn($r) =>
            strpos($r['date'], $year) === 0 &&
            isset($r['location_id']) && $r['location_id'] === $locationId
        );
        $locationMissions = self::loadFiltered('missions.json', fn($r) =>
            strpos($r['date'], $year) === 0 &&
            isset($r['location_id']) && $r['location_id'] === $locationId
        );

        $trainingHours = array_sum(array_column($locationAttendance, 'duration_hours'));
        $missionHours  = array_sum(array_column($locationMissions,   'duration_hours'));

        return [
            'location_id' => $locationId,
            'year' => $year,
            'total_training_sessions' => count($locationAttendance),
            'total_training_hours' => $trainingHours,
            'total_missions' => count($locationMissions),
            'total_mission_hours' => $missionHours
        ];
    }

    // ==================== Phone Numbers ====================

    /**
     * Get all phone numbers
     */
    public static function getPhoneNumbers() {
        $numbers = self::load('phone-numbers.json');
        // Sort by organization and name
        usort($numbers, function($a, $b) {
            $orgCmp = strcasecmp($a['organization'] ?? '', $b['organization'] ?? '');
            if ($orgCmp !== 0) return $orgCmp;
            return strcasecmp($a['name'] ?? '', $b['name'] ?? '');
        });
        return $numbers;
    }

    /**
     * Add phone number
     */
    public static function addPhoneNumber($data) {
        self::mutate('phone-numbers.json', function ($numbers) use ($data) {
            $numbers[] = $data;
            return $numbers;
        });
        self::audit('phone_number.create', ['id' => $data['id'] ?? null]);
        return $data;
    }

    /**
     * Update phone number
     */
    public static function updatePhoneNumber($id, $data) {
        self::mutate('phone-numbers.json', function ($numbers) use ($id, $data) {
            foreach ($numbers as &$number) {
                if ($number['id'] === $id) {
                    $number['name'] = $data['name'];
                    $number['organization'] = $data['organization'];
                    $number['role'] = $data['role'];
                    $number['phone'] = $data['phone'];
                    $number['updated'] = date('Y-m-d H:i:s');
                    break;
                }
            }
            unset($number);
            return $numbers;
        });
        self::audit('phone_number.update', ['id' => $id]);
        return true;
    }

    /**
     * Delete phone number
     */
    public static function deletePhoneNumber($id) {
        self::mutate('phone-numbers.json', function ($numbers) use ($id) {
            return array_values(array_filter($numbers, function ($number) use ($id) {
                return $number['id'] !== $id;
            }));
        });
        self::audit('phone_number.delete', ['id' => $id]);
        return true;
    }

    /**
     * Get settings
     */
    public static function getSettings() {
        $settings = self::load('settings.json');
        
        // Return default settings if none exist
        if (empty($settings)) {
            return [
                'fire_department_name' => 'Freiwillige Feuerwehr',
                'fire_department_city' => '',
                'logo_filename' => '',
                'contact_phone' => '',
                'address' => ''
            ];
        }
        
        return $settings;
    }

    /**
     * Update settings
     */
    public static function updateSettings($data) {
        $settings = null;
        self::mutate('settings.json', function ($stored) use ($data, &$settings) {
            if (empty($stored)) {
                $stored = [
                    'fire_department_name' => 'Freiwillige Feuerwehr',
                    'fire_department_city' => '',
                    'logo_filename' => '',
                    'contact_phone' => '',
                    'address' => ''
                ];
            }
            foreach ($data as $key => $value) {
                $stored[$key] = $value;
            }
            $stored['updated_at'] = date('Y-m-d H:i:s');
            $settings = $stored;
            return $stored;
        });
        self::audit('settings.update', []);
        return $settings;
    }

    // ==================== Email Settings ====================

    /**
     * Get email/SMTP settings from encrypted storage.
     * Falls back to config.php values so existing installs keep working
     * until settings are saved through the admin UI.
     */
    public static function getEmailSettings(): array {
        $stored = self::load('email_settings.json');

        if (!empty($stored)) {
            return $stored;
        }

        // Migration fallback: read from config.php if no encrypted file exists yet
        self::init();
        $config = self::$config;
        $legacy = $config['email'] ?? [];

        // Also check general settings for migrated email fields
        $generalSettings = self::load('settings.json') ?? [];

        return [
            'smtp_host'     => $legacy['smtp_host']     ?? '',
            'smtp_port'     => (int) ($legacy['smtp_port'] ?? 587),
            'smtp_auth'     => (bool) ($legacy['smtp_auth'] ?? false),
            'smtp_username' => $legacy['smtp_username'] ?? '',
            'smtp_password' => $legacy['smtp_password'] ?? '',
            'smtp_secure'   => $legacy['smtp_secure']   ?? 'tls',
            'from_address'  => $legacy['from_address']  ?? '',
            'from_name'     => $legacy['from_name']     ?? 'Feuerwehr Management System',
            // Migrate email_recipient from general settings as to_address
            'to_address'    => $legacy['to_address']    ?? $generalSettings['email_recipient'] ?? '',
            'contact_email' => $generalSettings['contact_email'] ?? '',
        ];
    }

    /**
     * Persist email/SMTP settings to the encrypted JSON store.
     */
    public static function updateEmailSettings(array $data): array {
        $settings = [
            'smtp_host'     => $data['smtp_host']     ?? '',
            'smtp_port'     => (int) ($data['smtp_port'] ?? 587),
            'smtp_auth'     => (bool) ($data['smtp_auth'] ?? false),
            'smtp_username' => $data['smtp_username'] ?? '',
            'smtp_password' => $data['smtp_password'] ?? '',
            'smtp_secure'   => $data['smtp_secure']   ?? 'tls',
            'from_address'  => $data['from_address']  ?? '',
            'from_name'     => $data['from_name']     ?? 'Feuerwehr Management System',
            'to_address'    => $data['to_address']    ?? '',
            'contact_email' => $data['contact_email'] ?? '',
            'updated_at'    => date('Y-m-d H:i:s'),
        ];

        self::mutate('email_settings.json', function () use ($settings) {
            return $settings;
        });
        self::audit('email_settings.update', []);
        return $settings;
    }

    /**
     * Get the default email recipient for form submissions.
     * Uses to_address if configured, falls back to from_address.
     */
    public static function getDefaultRecipient(): ?string {
        $settings = self::getEmailSettings();
        if (!empty($settings['to_address'])) {
            return $settings['to_address'];
        }
        return !empty($settings['from_address']) ? $settings['from_address'] : null;
    }

    /**
     * Remove logo from settings
     */
    public static function removeLogo() {
        $settings = self::getSettings();

        // Delete logo file if exists
        if (!empty($settings['logo_filename'])) {
            self::init();
            $logoPath = self::$dataDir . '/settings/' . $settings['logo_filename'];
            if (file_exists($logoPath)) {
                unlink($logoPath);
            }
        }

        self::mutate('settings.json', function ($stored) {
            if (empty($stored)) {
                $stored = [
                    'fire_department_name' => 'Freiwillige Feuerwehr',
                    'fire_department_city' => '',
                    'logo_filename' => '',
                    'contact_phone' => '',
                    'address' => ''
                ];
            }
            $stored['logo_filename'] = '';
            $stored['updated_at'] = date('Y-m-d H:i:s');
            return $stored;
        });
        self::audit('settings.remove_logo', []);
        return true;
    }
}
