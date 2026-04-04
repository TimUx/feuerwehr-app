<?php
/**
 * Shared data-directory initialisation helper.
 *
 * Both Auth and DataStore use the same logic to ensure that the data
 * directory exists and is writable. This function centralises that logic
 * so it only lives in one place.
 *
 * The write-test is performed at most once per PHP process (tracked via a
 * static flag). On the very first call the directory is verified; every
 * subsequent call within the same request returns immediately.
 */

function initDataDirectory(string $dataDir): void {
    static $verified = [];

    // Already verified this directory path during the current request
    if (isset($verified[$dataDir])) {
        return;
    }

    clearstatcache(true, $dataDir);

    if (!is_dir($dataDir)) {
        if (!@mkdir($dataDir, 0755, true)) {
            clearstatcache(true, $dataDir);
            if (!is_dir($dataDir)) {
                error_log("Failed to create data directory: {$dataDir}. Please ensure the web server has write permissions to the parent directory.");
                die("Configuration Error: Unable to create data directory. Please contact your system administrator or check file permissions.");
            }
        }
    }

    // Actual write-test (is_writable() can be unreliable under PHP-FPM)
    $testFile = $dataDir . '/.write_test_' . bin2hex(random_bytes(8));
    error_clear_last();
    $writeResult = @file_put_contents($testFile, 'test');

    if ($writeResult !== false) {
        @unlink($testFile);
    } else {
        $lastError = error_get_last();
        $errorMsg  = $lastError ? $lastError['message'] : 'Unknown error';
        error_log("Data directory exists but is not writable: {$dataDir}. Write test error: {$errorMsg}");
        die("Configuration Error: Data directory is not writable. Please contact your system administrator or check file permissions.");
    }

    $verified[$dataDir] = true;
}
