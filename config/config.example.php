<?php
/**
 * Configuration file for Feuerwehr App
 * Copy this file to config.php and update with your settings
 */

return [
    // Application settings
    'app_name' => 'Feuerwehr Management',
    'app_version' => '1.0.0',
    
    // Security settings
    'encryption_key' => 'CHANGE_THIS_TO_A_RANDOM_32_CHARACTER_KEY',
    'session_lifetime' => 3600, // 1 hour in seconds
    
    // Email settings for form submissions
    'email' => [
        'from_address' => 'noreply@feuerwehr.local',
        'from_name' => 'Feuerwehr Management System',
        'to_address' => '', // Default recipient for all form submissions
        'smtp_host' => 'localhost',
        'smtp_port' => 25,
        'smtp_auth' => false,
        'smtp_username' => '',
        'smtp_password' => '',
        'smtp_secure' => '', // 'tls' or 'ssl'
    ],
    
    // Data directory paths
    'data_dir' => __DIR__ . '/../data',
    'backup_dir' => __DIR__ . '/../data/backups',
    
    // Default admin credentials (change on first login!)
    'default_admin' => [
        'username' => 'admin',
        'password' => 'admin123', // CHANGE THIS IMMEDIATELY
    ],
];
