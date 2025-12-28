<?php
/**
 * Session Diagnostic Script
 * 
 * This script helps diagnose session persistence issues by simulating
 * the login flow and checking if session data persists across requests.
 * 
 * Usage:
 * 1. Access this script in your browser: http://your-domain/test_session.php
 * 2. Click "Set Session" to create a test session
 * 3. The page will reload
 * 4. Check if the session data is still there
 */

// Start or resume session
session_start();

// Handle session test
if (isset($_GET['action']) && $_GET['action'] === 'set') {
    // Set test session data
    $_SESSION['test_data'] = 'Session is working! Time: ' . date('Y-m-d H:i:s');
    $_SESSION['test_counter'] = ($_SESSION['test_counter'] ?? 0) + 1;
    
    // Regenerate session ID (like in login)
    $oldId = session_id();
    session_regenerate_id(true);
    $newId = session_id();
    
    // Write and close session
    session_write_close();
    
    // Log the operation
    error_log("Test: Session regenerated from {$oldId} to {$newId}");
    
    // Redirect back to this script
    header('Location: /test_session.php');
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'clear') {
    // Clear session
    $_SESSION = [];
    session_destroy();
    header('Location: /test_session.php');
    exit;
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Session Diagnostic</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
        }
        .info {
            background: #e3f2fd;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin: 15px 0;
        }
        .success {
            background: #e8f5e9;
            border-left: 4px solid #4CAF50;
            padding: 15px;
            margin: 15px 0;
        }
        .warning {
            background: #fff3e0;
            border-left: 4px solid #ff9800;
            padding: 15px;
            margin: 15px 0;
        }
        .error {
            background: #ffebee;
            border-left: 4px solid #f44336;
            padding: 15px;
            margin: 15px 0;
        }
        button {
            background: #2196F3;
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 16px;
            cursor: pointer;
            margin: 5px;
            border-radius: 4px;
        }
        button:hover {
            background: #1976D2;
        }
        .clear-btn {
            background: #f44336;
        }
        .clear-btn:hover {
            background: #d32f2f;
        }
        pre {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <h1>🔍 Session Diagnostic Tool</h1>
    
    <?php if (isset($_SESSION['test_data'])): ?>
        <div class="success">
            <h2>✅ Session is Working!</h2>
            <p><strong>Session Data:</strong> <?php echo htmlspecialchars($_SESSION['test_data']); ?></p>
            <p><strong>Counter:</strong> <?php echo intval($_SESSION['test_counter']); ?></p>
            <p>This means session data is persisting correctly across requests.</p>
        </div>
    <?php else: ?>
        <div class="warning">
            <h2>⚠️ No Session Data Found</h2>
            <p>Click the button below to create a test session.</p>
        </div>
    <?php endif; ?>
    
    <div class="info">
        <h3>Session Information</h3>
        <pre><?php
            echo "Session ID: " . session_id() . "\n";
            echo "Session Name: " . session_name() . "\n";
            echo "Session Status: " . (session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Inactive') . "\n";
            echo "Session Save Path: " . session_save_path() . "\n";
            echo "\nCookie Parameters:\n";
            $params = session_get_cookie_params();
            echo "  Lifetime: " . $params['lifetime'] . " seconds\n";
            echo "  Path: " . $params['path'] . "\n";
            echo "  Domain: " . ($params['domain'] ?: '(current domain)') . "\n";
            echo "  Secure: " . ($params['secure'] ? 'Yes' : 'No') . "\n";
            echo "  HttpOnly: " . ($params['httponly'] ? 'Yes' : 'No') . "\n";
            echo "  SameSite: " . ($params['samesite'] ?: 'None') . "\n";
            
            echo "\nSession Data:\n";
            echo !empty($_SESSION) ? print_r($_SESSION, true) : "  (empty)\n";
        ?></pre>
    </div>
    
    <div class="info">
        <h3>Server Information</h3>
        <pre><?php
            echo "PHP Version: " . PHP_VERSION . "\n";
            echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "\n";
            echo "Protocol: " . (
                (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
                (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
                (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
                ? 'HTTPS' : 'HTTP'
            ) . "\n";
            echo "Request Method: " . $_SERVER['REQUEST_METHOD'] . "\n";
        ?></pre>
    </div>
    
    <div>
        <a href="?action=set"><button>Set Session & Redirect</button></a>
        <a href="?action=clear"><button class="clear-btn">Clear Session</button></a>
    </div>
    
    <div class="info" style="margin-top: 30px;">
        <h3>How to Use This Tool</h3>
        <ol>
            <li>Click "Set Session & Redirect" - this will create test session data and redirect</li>
            <li>After the redirect, check if the session data is still present</li>
            <li>If session data persists, your session handling is working correctly</li>
            <li>If not, there may be an issue with session persistence</li>
            <li>Check your PHP error log for detailed information</li>
        </ol>
        <p><strong>Note:</strong> This simulates the login flow: it sets session data, regenerates the session ID, writes and closes the session, then redirects.</p>
    </div>
    
    <div class="warning" style="margin-top: 30px;">
        <p><strong>⚠️ Security Note:</strong> Delete this file after testing! It's only for diagnostic purposes.</p>
    </div>
</body>
</html>
