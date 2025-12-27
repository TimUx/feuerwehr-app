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
     * Get personnel filtered by location (if locationId is null, returns all)
     */
    public static function getPersonnelByLocation($locationId = null) {
        $personnel = self::getPersonnel();
        if ($locationId === null) {
            return $personnel;
        }
        return array_filter($personnel, function($person) use ($locationId) {
            return !isset($person['location_id']) || $person['location_id'] === $locationId;
        });
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
            'is_instructor' => $data['is_instructor'] ?? false,
            'location_id' => $data['location_id'] ?? null,
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
                if (isset($data['is_instructor'])) {
                    $person['is_instructor'] = $data['is_instructor'];
                }
                if (isset($data['location_id'])) {
                    $person['location_id'] = $data['location_id'];
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
     * Get vehicles filtered by location (if locationId is null, returns all)
     */
    public static function getVehiclesByLocation($locationId = null) {
        $vehicles = self::getVehicles();
        if ($locationId === null) {
            return $vehicles;
        }
        return array_filter($vehicles, function($vehicle) use ($locationId) {
            return !isset($vehicle['location_id']) || $vehicle['location_id'] === $locationId;
        });
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
            'location' => $data['location'] ?? null, // Legacy field for backward compatibility
            'location_id' => $data['location_id'] ?? null, // New field - use this for filtering
            'type' => $data['type'],
            'radio_call_sign' => $data['radio_call_sign'],
            'crew_size' => $data['crew_size'] ?? null,
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
        $locations = self::getLocations();
        
        $newLocation = [
            'id' => uniqid('loc_'),
            'name' => $data['name'],
            'address' => $data['address'] ?? '',
            'email' => $data['email'] ?? '',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $locations[] = $newLocation;
        self::save('locations.json', $locations);
        return $newLocation;
    }

    /**
     * Update location
     */
    public static function updateLocation($id, $data) {
        $locations = self::getLocations();
        
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
                $location['updated_at'] = date('Y-m-d H:i:s');
                
                self::save('locations.json', $locations);
                return $location;
            }
        }
        
        return null;
    }

    /**
     * Delete location
     */
    public static function deleteLocation($id) {
        $locations = self::getLocations();
        $locations = array_filter($locations, function($location) use ($id) {
            return $location['id'] !== $id;
        });
        
        self::save('locations.json', array_values($locations));
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
        return array_filter($records, function($record) use ($locationId) {
            return isset($record['location_id']) && $record['location_id'] === $locationId;
        });
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
        $records = self::getAttendanceRecords();
        
        // Preserve all data fields from the input
        $newRecord = array_merge($data, [
            'id' => $data['id'] ?? uniqid('att_'),
            'date' => $data['date'] ?? $data['datum'] ?? '',
            'type' => $data['type'] ?? 'training',
            'description' => $data['description'] ?? $data['thema'] ?? '',
            'duration_hours' => $data['duration_hours'] ?? 0,
            'attendees' => $data['attendees'] ?? [],
            'created_at' => $data['created_at'] ?? date('Y-m-d H:i:s'),
            'created_by' => $data['created_by'] ?? null
        ]);

        $records[] = $newRecord;
        self::save('attendance.json', $records);
        return $newRecord;
    }

    /**
     * Update attendance record
     */
    public static function updateAttendanceRecord($id, $data) {
        $records = self::getAttendanceRecords();
        
        foreach ($records as &$record) {
            if ($record['id'] === $id) {
                // Merge new data with existing record
                $record = array_merge($record, $data);
                $record['updated_at'] = date('Y-m-d H:i:s');
                
                self::save('attendance.json', $records);
                return $record;
            }
        }
        
        return null;
    }

    /**
     * Delete attendance record
     */
    public static function deleteAttendanceRecord($id) {
        $records = self::getAttendanceRecords();
        $records = array_filter($records, function($record) use ($id) {
            return $record['id'] !== $id;
        });
        
        self::save('attendance.json', array_values($records));
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
        return array_filter($reports, function($report) use ($locationId) {
            return isset($report['location_id']) && $report['location_id'] === $locationId;
        });
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
            'location_id' => $data['location_id'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'created_by' => $data['created_by'] ?? null
        ];

        $reports[] = $newReport;
        self::save('missions.json', $reports);
        return $newReport;
    }

    /**
     * Update mission report
     */
    public static function updateMissionReport($id, $data) {
        $reports = self::getMissionReports();
        
        foreach ($reports as &$report) {
            if ($report['id'] === $id) {
                // Merge new data with existing report
                $report = array_merge($report, $data);
                $report['updated_at'] = date('Y-m-d H:i:s');
                
                self::save('missions.json', $reports);
                return $report;
            }
        }
        
        return null;
    }

    /**
     * Delete mission report
     */
    public static function deleteMissionReport($id) {
        $reports = self::getMissionReports();
        $reports = array_filter($reports, function($report) use ($id) {
            return $report['id'] !== $id;
        });
        
        self::save('missions.json', array_values($reports));
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

    /**
     * Get statistics for a specific location
     */
    public static function getLocationStatistics($locationId, $year = null) {
        if (!$year) {
            $year = date('Y');
        }

        $attendance = self::getAttendanceRecords();
        $missions = self::getMissionReports();

        // Filter by year and location
        $locationAttendance = array_filter($attendance, function($record) use ($year, $locationId) {
            return strpos($record['date'], $year) === 0 && 
                   isset($record['location_id']) && $record['location_id'] === $locationId;
        });

        $locationMissions = array_filter($missions, function($report) use ($year, $locationId) {
            return strpos($report['date'], $year) === 0 && 
                   isset($report['location_id']) && $report['location_id'] === $locationId;
        });

        $trainingHours = array_sum(array_column($locationAttendance, 'duration_hours'));
        $missionHours = array_sum(array_column($locationMissions, 'duration_hours'));

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
                'email_recipient' => '',
                'contact_phone' => '',
                'contact_email' => '',
                'address' => ''
            ];
        }
        
        return $settings;
    }

    /**
     * Update settings
     */
    public static function updateSettings($data) {
        $settings = self::getSettings();
        
        // Update fields
        foreach ($data as $key => $value) {
            $settings[$key] = $value;
        }
        
        $settings['updated_at'] = date('Y-m-d H:i:s');
        
        self::save('settings.json', $settings);
        return $settings;
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
        
        // Update settings
        $settings['logo_filename'] = '';
        $settings['updated_at'] = date('Y-m-d H:i:s');
        
        self::save('settings.json', $settings);
        return true;
    }
}
