<?php
/**
 * Feuerwehr Management App - Login Page
 * Dedicated login page to handle authentication separately from main app
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

// If already authenticated, redirect to main app
if (Auth::isAuthenticated()) {
    header('Location: /index.php');
    exit;
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $rememberMe = isset($_POST['remember_me']) && $_POST['remember_me'] === '1';
    
    if (Auth::login($username, $password, $rememberMe)) {
        // Login successful - session was already written and closed in Auth::login()
        // Redirect to main app
        header('Location: /index.php');
        exit;
    } else {
        $loginError = 'Ungültiger Benutzername oder Passwort';
    }
}

// Try auto-login with remember me token
Auth::tryAutoLogin();

// Check again after auto-login
if (Auth::isAuthenticated()) {
    header('Location: /index.php');
    exit;
}

// Handle password reset page redirect
if (isset($_GET['action']) && $_GET['action'] === 'reset-password') {
    $resetToken = $_GET['token'] ?? '';
    header('Location: /index.php?action=reset-password&token=' . urlencode($resetToken));
    exit;
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Feuerwehr Management App - Login">
    <meta name="theme-color" content="#d32f2f">
    
    <!-- iOS Safari PWA Support -->
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Feuerwehr">
    
    <title>Login - Feuerwehr Management</title>
    
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
            
            <form method="POST" action="/login.php">
                <div class="form-group">
                    <label class="form-label" for="username">Benutzername</label>
                    <input type="text" id="username" name="username" class="form-input" required autofocus>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="password">Passwort</label>
                    <input type="password" id="password" name="password" class="form-input" required>
                </div>
                
                <div class="form-group" style="display: flex; align-items: center; gap: 8px; margin-bottom: 20px;">
                    <input type="checkbox" id="remember_me" name="remember_me" value="1" style="width: auto; height: 18px; margin: 0;">
                    <label for="remember_me" style="margin: 0; font-weight: normal; cursor: pointer;">Angemeldet bleiben</label>
                </div>
                
                <div style="text-align: center; margin-bottom: 20px;">
                    <a href="#" onclick="showForgotPassword(); return false;" style="color: var(--primary-color); text-decoration: none; font-size: 14px;">
                        Passwort vergessen?
                    </a>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    <span class="material-icons">login</span>
                    Anmelden
                </button>
            </form>
        </div>
    </div>

    <!-- Forgot Password Modal -->
    <div id="forgot-password-modal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h2 class="modal-title">Passwort vergessen</h2>
                <button class="modal-close" onclick="closeForgotPassword()">&times;</button>
            </div>
            <form id="forgot-password-form">
                <p style="margin-bottom: 20px; color: var(--text-secondary);">
                    Geben Sie Ihren Benutzernamen ein. Falls eine E-Mail-Adresse hinterlegt ist, erhalten Sie einen Link zur Passwort-Wiederherstellung.
                </p>
                
                <div class="form-group">
                    <label class="form-label" for="reset-username">Benutzername</label>
                    <input type="text" id="reset-username" name="username" class="form-input" required autofocus>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeForgotPassword()">Abbrechen</button>
                    <button type="submit" class="btn btn-primary">
                        <span class="material-icons">send</span>
                        Link senden
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function showForgotPassword() {
        document.getElementById('forgot-password-modal').classList.add('show');
    }

    function closeForgotPassword() {
        document.getElementById('forgot-password-modal').classList.remove('show');
        document.getElementById('forgot-password-form').reset();
    }

    // Handle forgot password form submission
    document.getElementById('forgot-password-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const formData = new FormData(e.target);
        const username = formData.get('username');
        
        try {
            const response = await fetch('/src/php/api/password-reset.php?action=request', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ username: username })
            });
            
            const result = await response.json();
            
            if (result.success) {
                alert('✅ ' + result.message);
                closeForgotPassword();
            } else {
                alert('❌ ' + result.message);
            }
        } catch (error) {
            alert('❌ Fehler beim Senden der Anfrage: ' + error.message);
        }
    });
    </script>
</body>
</html>
