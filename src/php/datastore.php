<?php
/**
 * Data storage class for managing encrypted JSON files
 */

require_once __DIR__ . '/encryption.php';

class DataStore {
    private static $config;
    private static $dataDir;

    private static function init() {
        if (!self::$config) {
            self::$config = require __DIR__ . '/../../config/config.php';
            self::$dataDir = self::$config['data_dir'];
            
            // Ensure data directory exists
            if (!file_exists(self::$dataDir)) {
                mkdir(self::$dataDir, 0700, true);
            }
        }
    }

    /**
     * Load data from encrypted JSON file
     */
    private static function load($filename) {
        self::init();
        $filepath = self::$dataDir . '/' . $filename;
        
        if (!file_exists($filepath)) {
            return [];
        }

        $encrypted = file_get_contents($filepath);
        $decrypted = Encryption::decrypt($encrypted);
        return json_decode($decrypted, true) ?: [];
    }

    /**
     * Save data to encrypted JSON file
     */
    private static function save($filename, $data) {
        self::init();
        $filepath = self::$dataDir . '/' . $filename;
        $json = json_encode($data, JSON_PRETTY_PRINT);
        $encrypted = Encryption::encrypt($json);
        file_put_contents($filepath, $encrypted);
        chmod($filepath, 0600);
    }

    // ==================== Personnel Management ====================

    /**
     * Get all personnel
     */
    public static function getPersonnel() {
        return self::load('personnel.json');
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
        $personnel = self::getPersonnel();
        
        $newPerson = [
            'id' => uniqid('pers_'),
            'name' => $data['name'],
            'qualifications' => $data['qualifications'] ?? [],
            'leadership_roles' => $data['leadership_roles'] ?? [],
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $personnel[] = $newPerson;
        self::save('personnel.json', $personnel);
        return $newPerson;
    }

    /**
     * Update personnel
     */
    public static function updatePersonnel($id, $data) {
        $personnel = self::getPersonnel();
        
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
                $person['updated_at'] = date('Y-m-d H:i:s');
                
                self::save('personnel.json', $personnel);
                return $person;
            }
        }
        
        return null;
    }

    /**
     * Delete personnel
     */
    public static function deletePersonnel($id) {
        $personnel = self::getPersonnel();
        $personnel = array_filter($personnel, function($person) use ($id) {
            return $person['id'] !== $id;
        });
        
        self::save('personnel.json', array_values($personnel));
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
        $vehicles = self::getVehicles();
        
        $newVehicle = [
            'id' => uniqid('veh_'),
            'location' => $data['location'],
            'type' => $data['type'],
            'radio_call_sign' => $data['radio_call_sign'],
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $vehicles[] = $newVehicle;
        self::save('vehicles.json', $vehicles);
        return $newVehicle;
    }

    /**
     * Update vehicle
     */
    public static function updateVehicle($id, $data) {
        $vehicles = self::getVehicles();
        
        foreach ($vehicles as &$vehicle) {
            if ($vehicle['id'] === $id) {
                if (isset($data['location'])) {
                    $vehicle['location'] = $data['location'];
                }
                if (isset($data['type'])) {
                    $vehicle['type'] = $data['type'];
                }
                if (isset($data['radio_call_sign'])) {
                    $vehicle['radio_call_sign'] = $data['radio_call_sign'];
                }
                $vehicle['updated_at'] = date('Y-m-d H:i:s');
                
                self::save('vehicles.json', $vehicles);
                return $vehicle;
            }
        }
        
        return null;
    }

    /**
     * Delete vehicle
     */
    public static function deleteVehicle($id) {
        $vehicles = self::getVehicles();
        $vehicles = array_filter($vehicles, function($vehicle) use ($id) {
            return $vehicle['id'] !== $id;
        });
        
        self::save('vehicles.json', array_values($vehicles));
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
     * Create attendance record
     */
    public static function createAttendanceRecord($data) {
        $records = self::getAttendanceRecords();
        
        $newRecord = [
            'id' => uniqid('att_'),
            'date' => $data['date'],
            'type' => $data['type'], // training, meeting, etc.
            'description' => $data['description'] ?? '',
            'duration_hours' => $data['duration_hours'] ?? 0,
            'attendees' => $data['attendees'] ?? [],
            'created_at' => date('Y-m-d H:i:s'),
            'created_by' => $data['created_by'] ?? null
        ];

        $records[] = $newRecord;
        self::save('attendance.json', $records);
        return $newRecord;
    }

    // ==================== Mission Reports ====================

    /**
     * Get all mission reports
     */
    public static function getMissionReports() {
        return self::load('missions.json');
    }

    /**
     * Create mission report
     */
    public static function createMissionReport($data) {
        $reports = self::getMissionReports();
        
        $newReport = [
            'id' => uniqid('mis_'),
            'date' => $data['date'],
            'mission_type' => $data['mission_type'],
            'location' => $data['location'] ?? '',
            'description' => $data['description'] ?? '',
            'participants' => $data['participants'] ?? [],
            'vehicles' => $data['vehicles'] ?? [],
            'duration_hours' => $data['duration_hours'] ?? 0,
            'created_at' => date('Y-m-d H:i:s'),
            'created_by' => $data['created_by'] ?? null
        ];

        $reports[] = $newReport;
        self::save('missions.json', $reports);
        return $newReport;
    }

    // ==================== Statistics ====================

    /**
     * Get statistics for a specific year
     */
    public static function getStatistics($year = null) {
        if (!$year) {
            $year = date('Y');
        }

        $attendance = self::getAttendanceRecords();
        $missions = self::getMissionReports();

        // Filter by year
        $attendance = array_filter($attendance, function($record) use ($year) {
            return strpos($record['date'], $year) === 0;
        });

        $missions = array_filter($missions, function($report) use ($year) {
            return strpos($report['date'], $year) === 0;
        });

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

        $attendance = self::getAttendanceRecords();
        $missions = self::getMissionReports();

        // Filter by year and person
        $personAttendance = array_filter($attendance, function($record) use ($year, $personnelId) {
            return strpos($record['date'], $year) === 0 && 
                   in_array($personnelId, $record['attendees']);
        });

        $personMissions = array_filter($missions, function($report) use ($year, $personnelId) {
            return strpos($report['date'], $year) === 0 && 
                   in_array($personnelId, $report['participants']);
        });

        $trainingHours = 0;
        foreach ($personAttendance as $record) {
            $trainingHours += $record['duration_hours'];
        }

        $missionHours = 0;
        foreach ($personMissions as $report) {
            $missionHours += $report['duration_hours'];
        }

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

    // ==================== Phone Numbers ====================

    /**
     * Get all phone numbers
     */
    public static function getPhoneNumbers() {
        $numbers = self::load('phone-numbers.json');
        // Sort by organization and name
        usort($numbers, function($a, $b) {
            $orgCmp = strcasecmp($a['organization'], $b['organization']);
            if ($orgCmp !== 0) return $orgCmp;
            return strcasecmp($a['name'], $b['name']);
        });
        return $numbers;
    }

    /**
     * Add phone number
     */
    public static function addPhoneNumber($data) {
        $numbers = self::getPhoneNumbers();
        $numbers[] = $data;
        self::save('phone-numbers.json', $numbers);
        return $data;
    }

    /**
     * Update phone number
     */
    public static function updatePhoneNumber($id, $data) {
        $numbers = self::getPhoneNumbers();
        
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
        
        self::save('phone-numbers.json', $numbers);
        return true;
    }

    /**
     * Delete phone number
     */
    public static function deletePhoneNumber($id) {
        $numbers = self::getPhoneNumbers();
        $numbers = array_filter($numbers, function($number) use ($id) {
            return $number['id'] !== $id;
        });
        
        self::save('phone-numbers.json', array_values($numbers));
        return true;
    }
}
