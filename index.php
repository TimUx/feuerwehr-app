<?php
/**
 * Feuerwehr Management App - Main Entry Point
 */

require_once __DIR__ . '/src/php/auth.php';
require_once __DIR__ . '/src/php/encryption.php';

// Check if configuration exists
if (!file_exists(__DIR__ . '/config/config.php')) {
    header('Location: install.php');
    exit;
}

// Initialize session
Auth::init();

// Helper function to build safe redirect URL
function getSafeRedirectUrl($path) {
    // Validate and sanitize HTTP_HOST to prevent Host header injection
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    // Remove any potentially malicious characters (allow alphanumeric, dots, hyphens, colons for port)
    // Hyphen at the end of character class to avoid being interpreted as range
    $host = preg_replace('/[^a-zA-Z0-9.:-]/', '', $host);
    // Ensure the host is reasonable (basic validation)
    if (empty($host) || strlen($host) > 255) {
        $host = $_SERVER['SERVER_NAME'] ?? 'localhost';
    }
    
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    return "{$protocol}://{$host}{$path}";
}

// Handle logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    Auth::logout();
    header('Location: ' . getSafeRedirectUrl('/index.php'));
    exit;
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (Auth::login($username, $password)) {
        // Login successful - redirect to home
        header('Location: ' . getSafeRedirectUrl('/index.php'));
        exit;
    } else {
        $loginError = 'Ungültiger Benutzername oder Passwort';
    }
}

// Check authentication status
$isAuthenticated = Auth::isAuthenticated();

// Determine which page to show
$page = $_GET['page'] ?? 'home';

// If not authenticated, show login page
if (!$isAuthenticated) {
    $page = 'login';
}

$user = Auth::getUser();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Feuerwehr Management App">
    <meta name="theme-color" content="#d32f2f">
    <title>Feuerwehr Management</title>
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="/manifest.json">
    
    <!-- Icons -->
    <link rel="icon" type="image/png" sizes="192x192" href="/public/icons/icon-192x192.png">
    <link rel="apple-touch-icon" href="/public/icons/icon-192x192.png">
    
    <!-- Styles -->
    <link rel="stylesheet" href="/public/css/style.css">
    
    <!-- Material Icons -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
</head>
<body>
    <?php if ($page === 'login'): ?>
        <!-- Login Page -->
        <div class="login-container">
            <div class="login-card">
                <div class="login-header">
                    <h1 class="login-title">🚒 Feuerwehr Management</h1>
                    <p class="login-subtitle">Bitte melden Sie sich an</p>
                </div>
                
                <?php if (isset($loginError)): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($loginError); ?></div>
                <?php endif; ?>
                
                <form method="POST" action="index.php">
                    <div class="form-group">
                        <label class="form-label" for="username">Benutzername</label>
                        <input type="text" id="username" name="username" class="form-input" required autofocus>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="password">Passwort</label>
                        <input type="password" id="password" name="password" class="form-input" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        <span class="material-icons">login</span>
                        Anmelden
                    </button>
                </form>
            </div>
        </div>
    <?php else: ?>
        <!-- Main App -->
        <div class="app-container">
            <!-- Header -->
            <header class="app-header">
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <button id="menu-toggle" class="icon-btn">
                        <span class="material-icons">menu</span>
                    </button>
                    <div class="app-title">
                        <span class="material-icons">local_fire_department</span>
                        Feuerwehr Management
                    </div>
                </div>
                
                <div class="app-actions">
                    <button id="install-btn" class="icon-btn" title="App installieren" style="display: none;">
                        <span class="material-icons">get_app</span>
                    </button>
                    <button id="theme-toggle" class="icon-btn" title="Design umschalten">
                        <span class="material-icons">dark_mode</span>
                    </button>
                    <button class="icon-btn" onclick="window.location.href='index.php?action=logout'" title="Abmelden">
                        <span class="material-icons">logout</span>
                    </button>
                </div>
            </header>

            <!-- Navigation Drawer -->
            <nav id="nav-drawer" class="nav-drawer">
                <a href="#" class="nav-item active" data-page="home">
                    <span class="material-icons">home</span>
                    <span>Hauptmenü</span>
                </a>
                
                <div class="nav-section-title">Funktionen</div>
                
                <a href="#" class="nav-item" data-page="attendance">
                    <span class="material-icons">fact_check</span>
                    <span>Anwesenheitsliste</span>
                </a>
                
                <a href="#" class="nav-item" data-page="mission-report">
                    <span class="material-icons">description</span>
                    <span>Einsatzbericht</span>
                </a>
                
                <a href="#" class="nav-item" data-page="vehicles">
                    <span class="material-icons">local_shipping</span>
                    <span>Fahrzeuge</span>
                </a>
                
                <a href="#" class="nav-item" data-page="phone-numbers">
                    <span class="material-icons">phone</span>
                    <span>Telefonnummern</span>
                </a>
                
                <a href="#" class="nav-item" data-page="map">
                    <span class="material-icons">map</span>
                    <span>Online Karte</span>
                </a>
                
                <a href="#" class="nav-item" data-page="hazard-matrix">
                    <span class="material-icons">warning</span>
                    <span>Gefahrenmatrix</span>
                </a>
                
                <a href="#" class="nav-item" data-page="hazmat">
                    <span class="material-icons">science</span>
                    <span>Gefahrstoffe</span>
                </a>
                
                <a href="#" class="nav-item" data-page="statistics">
                    <span class="material-icons">bar_chart</span>
                    <span>Statistiken</span>
                </a>
                
                <?php if (Auth::isAdmin()): ?>
                <div class="nav-section-title">Administration</div>
                
                <a href="#" class="nav-item" data-page="admin-vehicles">
                    <span class="material-icons">local_shipping</span>
                    <span>Fahrzeugverwaltung</span>
                </a>
                
                <a href="#" class="nav-item" data-page="admin-phone-numbers">
                    <span class="material-icons">phone</span>
                    <span>Telefonnummernverwaltung</span>
                </a>
                
                <a href="#" class="nav-item" data-page="personnel">
                    <span class="material-icons">people</span>
                    <span>Einsatzkräfte</span>
                </a>
                
                <a href="#" class="nav-item" data-page="email-settings">
                    <span class="material-icons">email</span>
                    <span>Email Settings</span>
                </a>
                
                <a href="#" class="nav-item" data-page="users">
                    <span class="material-icons">admin_panel_settings</span>
                    <span>Benutzerverwaltung</span>
                </a>
                <?php endif; ?>
            </nav>
            
            <div id="nav-drawer-overlay" class="nav-drawer-overlay"></div>

            <!-- Main Content -->
            <main id="main-content" class="main-content">
                <!-- Content loaded dynamically via JavaScript -->
                <div class="card">
                    <div class="card-header">Willkommen</div>
                    <div class="card-content">
                        Willkommen, <strong><?php echo htmlspecialchars($user['username']); ?></strong>!
                        <br>
                        Rolle: <span class="badge badge-primary"><?php echo htmlspecialchars($user['role']); ?></span>
                    </div>
                </div>
            </main>
        </div>
    <?php endif; ?>

    <!-- JavaScript -->
    <script src="/public/js/app.js"></script>
</body>
</html>
