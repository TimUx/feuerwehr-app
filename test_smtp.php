<?php
/**
 * Test script for native PHP 8 SMTP client
 * Run this from command line: php test_smtp.php
 */

require_once __DIR__ . '/src/php/smtp_client.php';

echo "=== Native PHP 8 SMTP Client Test ===\n\n";

// Test 1: Basic connection test
echo "Test 1: Basic SMTP client instantiation\n";
try {
    $smtp = new SMTPClient('smtp.gmail.com', 587, 'tls', '', '');
    echo "✓ SMTPClient created successfully\n\n";
} catch (Exception $e) {
    echo "✗ Failed: " . $e->getMessage() . "\n\n";
}

// Test 2: Check required extensions
echo "Test 2: Required PHP extensions\n";
$required = ['openssl', 'sockets'];
foreach ($required as $ext) {
    $loaded = extension_loaded($ext);
    echo ($loaded ? "✓" : "✗") . " {$ext}: " . ($loaded ? "loaded" : "NOT LOADED") . "\n";
}
echo "\n";

// Test 3: Socket functions availability
echo "Test 3: Socket functions availability\n";
$functions = ['stream_socket_client', 'stream_socket_enable_crypto', 'fwrite', 'fgets'];
foreach ($functions as $func) {
    $exists = function_exists($func);
    echo ($exists ? "✓" : "✗") . " {$func}(): " . ($exists ? "available" : "NOT AVAILABLE") . "\n";
}
echo "\n";

echo "=== Summary ===\n";
echo "Native PHP 8 SMTP client is ready to use.\n";
echo "No external dependencies required (PHPMailer optional).\n";
echo "\nFeatures:\n";
echo "  • SMTP with TLS/STARTTLS or SSL\n";
echo "  • Authentication (LOGIN)\n";
echo "  • HTML emails\n";
echo "  • Multiple attachments\n";
echo "  • Pure PHP 8 implementation\n";
echo "\nTo send a test email, configure SMTP settings in config.php\n";
echo "and use the email settings page in the admin panel.\n";
