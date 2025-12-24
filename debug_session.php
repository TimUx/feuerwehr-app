<?php
/**
 * Debug script to test session handling
 */

require_once __DIR__ . '/src/php/session_init.php';

// Start session
initSecureSession();

echo "<h1>Session Debug</h1>";
echo "<h2>Session Status</h2>";
echo "Session ID: " . session_id() . "<br>";
echo "Session Status: " . session_status() . " (1=disabled, 2=active, 3=none)<br>";
echo "<br>";

// Check if we're setting or reading session
if (isset($_GET['set'])) {
    $_SESSION['test_user_id'] = 'test123';
    $_SESSION['test_username'] = 'testuser';
    $_SESSION['test_time'] = time();
    echo "<h2>Session Data Set</h2>";
    echo "Redirect in 2 seconds...<br>";
    echo "<script>setTimeout(() => window.location.href = 'debug_session.php?check=1', 2000);</script>";
} elseif (isset($_GET['check'])) {
    echo "<h2>Session Data Check</h2>";
    if (isset($_SESSION['test_user_id'])) {
        echo "✅ Session persisted successfully!<br>";
        echo "User ID: " . htmlspecialchars($_SESSION['test_user_id']) . "<br>";
        echo "Username: " . htmlspecialchars($_SESSION['test_username']) . "<br>";
        echo "Time set: " . htmlspecialchars($_SESSION['test_time']) . "<br>";
        echo "Time now: " . time() . "<br>";
        echo "Difference: " . (time() - $_SESSION['test_time']) . " seconds<br>";
    } else {
        echo "❌ Session NOT persisted!<br>";
        echo "This indicates a session cookie or storage issue.<br>";
    }
    echo "<br><a href='debug_session.php?set=1'>Try again</a> | <a href='debug_session.php?clear=1'>Clear session</a>";
} elseif (isset($_GET['clear'])) {
    session_unset();
    session_destroy();
    echo "<h2>Session Cleared</h2>";
    echo "<a href='debug_session.php?set=1'>Start test</a>";
} else {
    echo "<h2>Session Test</h2>";
    echo "<a href='debug_session.php?set=1'>Start session test</a>";
}

echo "<br><br>";
echo "<h2>Session Cookie Parameters</h2>";
$params = session_get_cookie_params();
echo "Lifetime: " . $params['lifetime'] . "<br>";
echo "Path: " . $params['path'] . "<br>";
echo "Domain: " . $params['domain'] . "<br>";
echo "Secure: " . ($params['secure'] ? 'Yes' : 'No') . "<br>";
echo "HttpOnly: " . ($params['httponly'] ? 'Yes' : 'No') . "<br>";
echo "SameSite: " . ($params['samesite'] ?? 'Not set') . "<br>";

echo "<br>";
echo "<h2>All Session Data</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<br>";
echo "<h2>Cookies</h2>";
echo "<pre>";
print_r($_COOKIE);
echo "</pre>";
?>
