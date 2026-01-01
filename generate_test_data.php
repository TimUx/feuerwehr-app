<?php
/**
 * Generate test data for screenshots
 */

require_once __DIR__ . '/src/php/datastore.php';
require_once __DIR__ . '/src/php/auth.php';
require_once __DIR__ . '/src/php/encryption.php';

// Initialize
$config = require __DIR__ . '/config/config.php';

echo "Generating test data...\n\n";

// 1. Create locations first
echo "Creating locations...\n";
$locations = [
    ['name' => 'Willingshausen', 'address' => 'Hauptstraße 1, 34628 Willingshausen', 'email' => 'feuerwehr@willingshausen.de'],
    ['name' => 'Leimbach', 'address' => 'Dorfstraße 12, 34628 Willingshausen', 'email' => 'feuerwehr@leimbach.de'],
    ['name' => 'Ransbach', 'address' => 'Bergstraße 8, 34628 Willingshausen', 'email' => 'feuerwehr@ransbach.de']
];
foreach ($locations as $location) {
    DataStore::createLocation($location);
}
echo "✓ Created " . count($locations) . " locations\n";

// 2. Create users
echo "Creating users...\n";
$users = [
    ['username' => 'admin', 'password' => 'admin123', 'role' => 'global_admin', 'location_id' => null, 'email' => 'admin@feuerwehr-willingshausen.de'],
    ['username' => 'standort_admin', 'password' => 'admin123', 'role' => 'location_admin', 'location_id' => 1, 'email' => 'standort@feuerwehr-willingshausen.de'],
    ['username' => 'operator', 'password' => 'operator123', 'role' => 'operator', 'location_id' => 1, 'email' => 'operator@feuerwehr-willingshausen.de']
];
foreach ($users as $user) {
    Auth::createUser($user['username'], $user['password'], $user['role'], $user['location_id'], $user['email']);
}
echo "✓ Created " . count($users) . " users\n";

// 3. Create personnel
echo "Creating personnel...\n";
$personnel = [
    ['name' => 'Max Mustermann', 'location_id' => 1, 'qualifications' => ['agt', 'machinist'], 'roles' => ['truppfuehrer', 'gruppenfuehrer'], 'trainer' => true],
    ['name' => 'Anna Schmidt', 'location_id' => 1, 'qualifications' => ['agt', 'paramedic'], 'roles' => ['truppfuehrer'], 'trainer' => true],
    ['name' => 'Thomas Weber', 'location_id' => 1, 'qualifications' => ['machinist'], 'roles' => ['gruppenfuehrer', 'zugfuehrer'], 'trainer' => false],
    ['name' => 'Sarah Fischer', 'location_id' => 1, 'qualifications' => ['agt'], 'roles' => ['truppfuehrer'], 'trainer' => false],
    ['name' => 'Michael Becker', 'location_id' => 2, 'qualifications' => ['agt', 'machinist', 'paramedic'], 'roles' => ['gruppenfuehrer'], 'trainer' => true],
    ['name' => 'Laura Hoffmann', 'location_id' => 2, 'qualifications' => ['agt'], 'roles' => ['truppfuehrer'], 'trainer' => false],
    ['name' => 'Daniel Klein', 'location_id' => 3, 'qualifications' => ['machinist'], 'roles' => ['truppfuehrer', 'gruppenfuehrer'], 'trainer' => false],
    ['name' => 'Julia Schneider', 'location_id' => 1, 'qualifications' => ['agt', 'paramedic'], 'roles' => ['truppfuehrer'], 'trainer' => false]
];
foreach ($personnel as $person) {
    DataStore::createPersonnel($person);
}
echo "✓ Created " . count($personnel) . " personnel\n";

// 4. Create vehicles
echo "Creating vehicles...\n";
$vehicles = [
    ['location_id' => 1, 'type' => 'TSF-W', 'radio_call_sign' => 'Florian Willingshausen 1/44'],
    ['location_id' => 1, 'type' => 'LF 16', 'radio_call_sign' => 'Florian Willingshausen 1/43'],
    ['location_id' => 2, 'type' => 'TSF', 'radio_call_sign' => 'Florian Willingshausen 2/44'],
    ['location_id' => 3, 'type' => 'MTW', 'radio_call_sign' => 'Florian Willingshausen 3/19'],
    ['location_id' => 1, 'type' => 'DLK 23', 'radio_call_sign' => 'Florian Willingshausen 1/33']
];
foreach ($vehicles as $vehicle) {
    DataStore::createVehicle($vehicle);
}
echo "✓ Created " . count($vehicles) . " vehicles\n";

// 5. Create phone numbers
echo "Creating phone numbers...\n";
$phoneNumbers = [
    ['id' => uniqid('phone_', true), 'name' => 'Leitstelle Schwalm-Eder', 'organization' => 'Kreisfeuerwehrverband', 'role' => 'Notruf', 'phone' => '112', 'created' => date('Y-m-d H:i:s')],
    ['id' => uniqid('phone_', true), 'name' => 'Wehrführer', 'organization' => 'Feuerwehr Willingshausen', 'role' => 'Leitung', 'phone' => '+49 6691 1234567', 'created' => date('Y-m-d H:i:s')],
    ['id' => uniqid('phone_', true), 'name' => 'Kreisbrandinspektor', 'organization' => 'Kreisfeuerwehrverband', 'role' => 'KBI', 'phone' => '+49 6691 7654321', 'created' => date('Y-m-d H:i:s')],
    ['id' => uniqid('phone_', true), 'name' => 'THW Ortsverband', 'organization' => 'THW', 'role' => 'Technische Hilfeleistung', 'phone' => '+49 6691 9876543', 'created' => date('Y-m-d H:i:s')],
    ['id' => uniqid('phone_', true), 'name' => 'Polizei', 'organization' => 'Polizeistation Schwalmstadt', 'role' => 'Notruf', 'phone' => '110', 'created' => date('Y-m-d H:i:s')]
];
foreach ($phoneNumbers as $phoneNumber) {
    DataStore::addPhoneNumber($phoneNumber);
}
echo "✓ Created " . count($phoneNumbers) . " phone numbers\n";

// 6. Create sample attendance records
echo "Creating attendance records...\n";
$attendance = [
    [
        'date' => '2025-01-15',
        'start_time' => '19:00',
        'end_time' => '21:00',
        'duration' => 2.0,
        'topic' => 'Atemschutzübung',
        'instructor' => 'Max Mustermann',
        'participants' => ['Max Mustermann', 'Anna Schmidt', 'Thomas Weber', 'Sarah Fischer', 'Julia Schneider'],
        'participant_count' => 5,
        'notes' => 'Gute Teilnahme, alle Übungsziele erreicht',
        'location_id' => 1
    ],
    [
        'date' => '2025-01-22',
        'start_time' => '19:00',
        'end_time' => '20:30',
        'duration' => 1.5,
        'topic' => 'Erste Hilfe Auffrischung',
        'instructor' => 'Anna Schmidt',
        'participants' => ['Anna Schmidt', 'Sarah Fischer', 'Julia Schneider'],
        'participant_count' => 3,
        'notes' => '',
        'location_id' => 1
    ]
];
foreach ($attendance as $record) {
    DataStore::createAttendanceRecord($record);
}
echo "✓ Created " . count($attendance) . " attendance records\n";

// 7. Create sample mission reports
echo "Creating mission reports...\n";
$missions = [
    [
        'mission_type' => 'Brand in Wohngebäude',
        'reason' => 'Brand in Wohngebäude',
        'date' => '2025-01-20',
        'start_time' => '14:30',
        'end_time' => '17:45',
        'duration' => 3.25,
        'location' => 'Hauptstraße 45, Willingshausen',
        'commander' => 'Thomas Weber',
        'situation' => 'Küchenbrand im Erdgeschoss eines Einfamilienhauses',
        'activities' => 'Brandbekämpfung mit Atemschutz, Belüftung, Nachkontrolle',
        'resources_used' => '2 Atemschutzgeräte, 2 C-Rohre, Überdruckbelüfter',
        'special_incidents' => 'Keine',
        'chargeable' => false,
        'vehicles' => [
            ['name' => 'Florian Willingshausen 1/43', 'other' => ''],
            ['name' => 'Florian Willingshausen 1/44', 'other' => '']
        ],
        'crew' => [
            ['function' => 'Fahrzeugführer', 'name' => 'Max Mustermann', 'vehicle' => 'Florian Willingshausen 1/43', 'loss_of_earnings' => true],
            ['function' => 'Maschinist', 'name' => 'Thomas Weber', 'vehicle' => 'Florian Willingshausen 1/43', 'loss_of_earnings' => true],
            ['function' => 'Angriffstrupp Führer', 'name' => 'Anna Schmidt', 'vehicle' => 'Florian Willingshausen 1/43', 'loss_of_earnings' => false],
            ['function' => 'Angriffstrupp Mann', 'name' => 'Sarah Fischer', 'vehicle' => 'Florian Willingshausen 1/44', 'loss_of_earnings' => false]
        ],
        'involved_persons' => [
            ['type' => 'Geschädigter', 'name' => 'Familie Müller', 'phone' => '+49 6691 123456', 'address' => 'Hauptstraße 45, Willingshausen', 'license_plate' => '']
        ],
        'location_id' => 1
    ]
];
foreach ($missions as $mission) {
    DataStore::createMissionReport($mission);
}
echo "✓ Created " . count($missions) . " mission reports\n";

echo "\n✅ All test data generated successfully!\n";
echo "\nYou can now log in with:\n";
echo "  Username: admin\n";
echo "  Password: admin123\n";
