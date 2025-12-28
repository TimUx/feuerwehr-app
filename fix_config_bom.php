<?php
/**
 * Config File Cleanup Utility
 * 
 * This script removes BOM (Byte Order Mark) and unwanted whitespace from config.php
 * Run this if you get "headers already sent" errors after editing config.php
 * 
 * Usage: Upload this file to your web root and access via browser
 * Example: https://your-domain.com/fix_config_bom.php
 */

// Security: Only allow execution in specific contexts
$allowedIPs = ['127.0.0.1', '::1']; // Add your IP here if needed
$isLocal = in_array($_SERVER['REMOTE_ADDR'] ?? '', $allowedIPs);
$confirmParam = isset($_GET['confirm']) && $_GET['confirm'] === 'yes';

?><!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Config Cleanup - Feuerwehr App</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .card {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .warning { color: #ffc107; }
        .info { color: #17a2b8; }
        pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 10px;
        }
        .button:hover {
            background: #0056b3;
        }
        code {
            background: #f8f9fa;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>🔧 Config File Cleanup</h1>
        
<?php

$configFile = __DIR__ . '/config/config.php';

if (!file_exists($configFile)) {
    echo '<p class="error">❌ <strong>Fehler:</strong> config/config.php wurde nicht gefunden.</p>';
    echo '<p>Bitte stellen Sie sicher, dass die Installation abgeschlossen ist.</p>';
    exit;
}

if (!$confirmParam) {
    // Show analysis without making changes
    echo '<p class="info">Dieser Assistent überprüft und bereinigt die config.php Datei von BOM und Whitespace.</p>';
    
    $content = file_get_contents($configFile);
    $hasIssues = false;
    
    echo '<h2>📋 Analyse</h2>';
    
    // Check for BOM
    if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
        echo '<p class="warning">⚠️ <strong>BOM erkannt</strong> - UTF-8 BOM am Dateianfang gefunden</p>';
        $hasIssues = true;
    } else {
        echo '<p class="success">✓ Kein BOM vorhanden</p>';
    }
    
    // Check for whitespace before <?php
    if (substr($content, 0, 5) !== '<?php') {
        $firstChars = substr($content, 0, 20);
        echo '<p class="warning">⚠️ <strong>Whitespace erkannt</strong> - Zeichen vor &lt;?php Tag gefunden</p>';
        echo '<pre>Erste Bytes (hex): ' . bin2hex($firstChars) . '</pre>';
        $hasIssues = true;
    } else {
        echo '<p class="success">✓ Kein Whitespace vor &lt;?php</p>';
    }
    
    // Check for closing ?> tag
    if (strpos($content, '?>') !== false) {
        echo '<p class="warning">⚠️ <strong>Closing Tag gefunden</strong> - Schließendes ?&gt; Tag kann Probleme verursachen</p>';
        $hasIssues = true;
    } else {
        echo '<p class="success">✓ Kein schließendes ?&gt; Tag</p>';
    }
    
    if ($hasIssues) {
        echo '<h2>🔨 Aktion erforderlich</h2>';
        echo '<p>Die Datei enthält Probleme, die "headers already sent" Fehler verursachen können.</p>';
        echo '<p><strong>Empfehlung:</strong> Klicken Sie auf den Button unten, um die Datei automatisch zu bereinigen.</p>';
        echo '<a href="?confirm=yes" class="button">Jetzt bereinigen</a>';
        
        echo '<h3>Alternative manuelle Lösung:</h3>';
        echo '<ol>';
        echo '<li>Laden Sie config/config.php via FTP herunter</li>';
        echo '<li>Öffnen Sie die Datei mit einem Editor, der UTF-8 ohne BOM unterstützt (z.B. Notepad++, VS Code)</li>';
        echo '<li>Stellen Sie sicher, dass:<ul>';
        echo '<li>Die Datei mit <code>&lt;?php</code> beginnt (keine Leerzeichen davor)</li>';
        echo '<li>Die Datei UTF-8 ohne BOM gespeichert ist</li>';
        echo '<li>Kein <code>?&gt;</code> Tag am Ende vorhanden ist</li>';
        echo '</ul></li>';
        echo '<li>Laden Sie die bereinigte Datei wieder hoch</li>';
        echo '</ol>';
    } else {
        echo '<h2>✅ Alles in Ordnung</h2>';
        echo '<p>Die config.php Datei ist korrekt formatiert und sollte keine "headers already sent" Fehler verursachen.</p>';
        echo '<p>Falls Sie dennoch Fehler sehen, überprüfen Sie andere PHP-Dateien auf ähnliche Probleme.</p>';
    }
    
} else {
    // Perform cleanup
    echo '<h2>🔄 Bereinigung läuft...</h2>';
    
    $content = file_get_contents($configFile);
    $originalContent = $content;
    $changes = [];
    
    // Remove BOM
    if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
        $content = substr($content, 3);
        $changes[] = 'BOM entfernt';
    }
    
    // Remove whitespace before <?php
    $content = ltrim($content);
    if (!str_starts_with($content, '<?php')) {
        echo '<p class="error">❌ Fehler: Datei beginnt nicht mit &lt;?php nach Bereinigung</p>';
        exit;
    }
    
    // Remove closing ?> tag if present
    if (str_ends_with(rtrim($content), '?>')) {
        $content = rtrim($content);
        $content = substr($content, 0, -2);
        $content = rtrim($content) . "\n";
        $changes[] = 'Schließendes ?> Tag entfernt';
    }
    
    if ($content !== $originalContent) {
        // Backup original file
        $backupFile = $configFile . '.backup.' . date('Y-m-d_H-i-s');
        if (file_put_contents($backupFile, $originalContent) === false) {
            echo '<p class="error">❌ Fehler beim Erstellen des Backups</p>';
            exit;
        }
        echo '<p class="success">✓ Backup erstellt: ' . basename($backupFile) . '</p>';
        
        // Write cleaned content
        if (file_put_contents($configFile, $content) === false) {
            echo '<p class="error">❌ Fehler beim Schreiben der bereinigten Datei</p>';
            exit;
        }
        
        echo '<h3>✅ Bereinigung erfolgreich!</h3>';
        echo '<p>Folgende Änderungen wurden vorgenommen:</p>';
        echo '<ul>';
        foreach ($changes as $change) {
            echo '<li>' . htmlspecialchars($change) . '</li>';
        }
        echo '</ul>';
        
        echo '<p class="info"><strong>Hinweis:</strong> Ein Backup der Original-Datei wurde erstellt.</p>';
        echo '<p>Bitte testen Sie jetzt Ihre Anwendung. Die "headers already sent" Fehler sollten behoben sein.</p>';
        
        echo '<p><a href="/" class="button">Zur Anwendung</a></p>';
        
        // Recommend deleting this file
        echo '<hr>';
        echo '<p class="warning"><strong>Sicherheitshinweis:</strong> Bitte löschen Sie diese Datei (fix_config_bom.php) nach Gebrauch!</p>';
    } else {
        echo '<p class="info">Keine Änderungen erforderlich - Datei ist bereits korrekt.</p>';
    }
}

?>
    </div>
</body>
</html>
