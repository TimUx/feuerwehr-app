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

// Handle logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    Auth::logout();
    header('Location: /login.php');
    exit;
}

// Handle password reset page
if (isset($_GET['action']) && $_GET['action'] === 'reset-password') {
    $resetToken = $_GET['token'] ?? '';
    $page = 'reset-password';
}

// Check authentication status
$isAuthenticated = Auth::isAuthenticated();

// If not authenticated, redirect to login page (unless it's password reset)
$page = isset($page) ? $page : ($_GET['page'] ?? 'home');
if (!$isAuthenticated && $page !== 'reset-password') {
    header('Location: /login.php');
    exit;
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
    
    <!-- iOS Safari PWA Support -->
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Feuerwehr">
    
    <title>Feuerwehr Management</title>
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="/manifest.json">
    
    <!-- Icons -->
    <link rel="icon" type="image/png" sizes="192x192" href="/public/icons/icon-192x192.png">
    <link rel="apple-touch-icon" href="/public/icons/icon-192x192.png">
    <link rel="apple-touch-icon" sizes="152x152" href="/public/icons/icon-152x152.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/public/icons/icon-192x192.png">
    <link rel="apple-touch-icon" sizes="167x167" href="/public/icons/icon-192x192.png">
    
    <!-- Styles -->
    <link rel="stylesheet" href="/public/css/style.css">
    
    <!-- Material Icons & Fonts (local) -->
    <link rel="stylesheet" href="/public/fonts/material-icons.css">
    <link rel="stylesheet" href="/public/fonts/roboto.css">
</head>
<body>
    <?php if ($page === 'reset-password'): ?>
        <!-- Password Reset Page -->
        <div class="login-container">
            <div class="login-card">
                <div class="login-header">
                    <h1 class="login-title">🔒 Passwort zurücksetzen</h1>
                    <p class="login-subtitle">Neues Passwort festlegen</p>
                </div>
                
                <div id="reset-message"></div>
                
                <form id="reset-password-form">
                    <input type="hidden" id="reset-token" value="<?php echo htmlspecialchars($resetToken ?? ''); ?>">
                    
                    <div id="reset-username-display" style="display: none; margin-bottom: 20px; padding: 10px; background: rgba(76, 175, 80, 0.1); border-radius: 4px; text-align: center;">
                        <strong>Benutzername:</strong> <span id="username-value"></span>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="new-password">Neues Passwort</label>
                        <input type="password" id="new-password" name="password" class="form-input" required minlength="6">
                        <small style="color: var(--text-secondary); display: block; margin-top: 0.25rem;">
                            Mindestens 6 Zeichen
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="confirm-password">Passwort bestätigen</label>
                        <input type="password" id="confirm-password" name="confirm_password" class="form-input" required minlength="6">
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        <span class="material-icons">lock_reset</span>
                        Passwort ändern
                    </button>
                    
                    <div style="text-align: center; margin-top: 20px;">
                        <a href="/login.php" style="color: var(--primary-color); text-decoration: none; font-size: 14px;">
                            Zurück zum Login
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <script>
        // Verify token on page load
        (async function() {
            const token = document.getElementById('reset-token').value;
            
            if (!token) {
                showResetMessage('error', 'Kein Token gefunden. Bitte fordern Sie einen neuen Passwort-Reset-Link an.');
                document.getElementById('reset-password-form').style.display = 'none';
                return;
            }
            
            try {
                const response = await fetch('/src/php/api/password-reset.php?action=verify', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ token: token })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    document.getElementById('username-value').textContent = result.username;
                    document.getElementById('reset-username-display').style.display = 'block';
                } else {
                    showResetMessage('error', result.message || 'Ungültiger oder abgelaufener Token.');
                    document.getElementById('reset-password-form').style.display = 'none';
                }
            } catch (error) {
                showResetMessage('error', 'Fehler beim Überprüfen des Tokens.');
                document.getElementById('reset-password-form').style.display = 'none';
            }
        })();

        // Handle password reset form submission
        document.getElementById('reset-password-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const password = document.getElementById('new-password').value;
            const confirmPassword = document.getElementById('confirm-password').value;
            
            if (password !== confirmPassword) {
                showResetMessage('error', 'Die Passwörter stimmen nicht überein.');
                return;
            }
            
            const token = document.getElementById('reset-token').value;
            
            try {
                const response = await fetch('/src/php/api/password-reset.php?action=reset', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ token: token, password: password })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showResetMessage('success', result.message + ' Sie werden zum Login weitergeleitet...');
                    document.getElementById('reset-password-form').style.display = 'none';
                    setTimeout(() => {
                        window.location.href = '/login.php';
                    }, 2000);
                } else {
                    showResetMessage('error', result.message);
                }
            } catch (error) {
                showResetMessage('error', 'Fehler beim Zurücksetzen des Passworts: ' + error.message);
            }
        });

        function showResetMessage(type, message) {
            const messageDiv = document.getElementById('reset-message');
            messageDiv.className = 'alert alert-' + type;
            messageDiv.textContent = message;
            messageDiv.style.display = 'block';
        }
        </script>
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
                    <span>Startseite</span>
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
                    <span>Karte</span>
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
                
                <a href="#" class="nav-item" data-page="form-data">
                    <span class="material-icons">folder</span>
                    <span>Formulardaten</span>
                </a>
                
                <?php if (Auth::isAdmin()): ?>
                <div class="nav-section-title">Administration</div>
                
                <a href="#" class="nav-item" data-page="admin-locations">
                    <span class="material-icons">location_city</span>
                    <span>Standorte</span>
                </a>
                
                <a href="#" class="nav-item" data-page="admin-vehicles">
                    <span class="material-icons">local_shipping</span>
                    <span>Fahrzeuge</span>
                </a>
                
                <a href="#" class="nav-item" data-page="personnel">
                    <span class="material-icons">people</span>
                    <span>Einsatzkräfte</span>
                </a>
                
                <a href="#" class="nav-item" data-page="admin-phone-numbers">
                    <span class="material-icons">phone</span>
                    <span>Telefonnummern</span>
                </a>
                
                <a href="#" class="nav-item" data-page="users">
                    <span class="material-icons">admin_panel_settings</span>
                    <span>Benutzer</span>
                </a>
                
                <?php if (Auth::isGlobalAdmin()): ?>
                <a href="#" class="nav-item" data-page="admin-settings">
                    <span class="material-icons">settings</span>
                    <span>Allgemeine Einstellungen</span>
                </a>
                
                <a href="#" class="nav-item" data-page="email-settings">
                    <span class="material-icons">email</span>
                    <span>Email Einstellungen</span>
                </a>
                <?php endif; ?>
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
    <script src="/public/js/offline-utils.js"></script>
    <script src="/public/js/offline-storage.js"></script>
    <script src="/public/js/offline-ui.js"></script>
    <script src="/public/js/app.js"></script>
</body>
</html>
