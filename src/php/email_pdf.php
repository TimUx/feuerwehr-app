<?php
/**
 * Email and PDF Generation Helper
 * Uses native PHP 8 SMTP or PHPMailer for email and mPDF for PDF generation
 */

// Load native SMTP client
require_once __DIR__ . '/smtp_client.php';

// Try to load PHPMailer if available (fallback)
if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
    require_once __DIR__ . '/../../vendor/autoload.php';
}

class EmailPDF {
    private static $config;
    
    private static function init() {
        if (!self::$config) {
            self::$config = require __DIR__ . '/../../config/config.php';
        }
    }
    
    /**
     * Check if PHPMailer is available
     */
    private static function isPhpMailerAvailable(): bool {
        return class_exists('PHPMailer\PHPMailer\PHPMailer');
    }
    
    /**
     * Send email with HTML content and multiple attachments
     * Uses native PHP 8 SMTP by default, falls back to PHPMailer if configured
     */
    public static function sendEmailWithAttachments($to, $subject, $htmlBody, $pdfContent = null, $pdfFilename = 'document.pdf', $extraFileContent = null, $extraFileName = null) {
        self::init();
        
        $emailConfig = self::$config['email'] ?? [];
        
        // Validate email addresses
        $fromAddress = $emailConfig['from_address'] ?? 'noreply@feuerwehr.local';
        if (!filter_var($fromAddress, FILTER_VALIDATE_EMAIL)) {
            error_log("Invalid from address: {$fromAddress}");
            return false;
        }
        
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            error_log("Invalid recipient address: {$to}");
            return false;
        }
        
        // Check if user prefers PHPMailer (use_phpmailer config option)
        $usePhpMailer = $emailConfig['use_phpmailer'] ?? false;
        
        // Use PHPMailer if explicitly requested AND available
        if ($usePhpMailer && self::isPhpMailerAvailable()) {
            return self::sendWithPhpMailer($to, $subject, $htmlBody, $pdfContent, $pdfFilename, $extraFileContent, $extraFileName);
        }
        
        // Use native PHP 8 SMTP (default)
        return self::sendWithNativeSMTP($to, $subject, $htmlBody, $pdfContent, $pdfFilename, $extraFileContent, $extraFileName);
    }
    
    /**
     * Send email using native PHP 8 SMTP client
     */
    private static function sendWithNativeSMTP($to, $subject, $htmlBody, $pdfContent, $pdfFilename, $extraFileContent, $extraFileName) {
        $emailConfig = self::$config['email'] ?? [];
        
        // Check if SMTP is configured
        if (empty($emailConfig['smtp_host'])) {
            // Fall back to PHP mail() function
            return self::sendWithPhpMail($to, $subject, $htmlBody, $pdfContent, $pdfFilename, $extraFileContent, $extraFileName);
        }
        
        // Validate authentication configuration
        if (!empty($emailConfig['smtp_auth'])) {
            if (empty($emailConfig['smtp_username']) || empty($emailConfig['smtp_password'])) {
                throw new Exception("SMTP-Authentifizierung ist aktiviert, aber Benutzername oder Passwort fehlt in der Konfiguration.");
            }
        }
        
        try {
            $smtp = new SMTPClient(
                $emailConfig['smtp_host'],
                $emailConfig['smtp_port'] ?? 587,
                $emailConfig['smtp_secure'] ?? 'tls',
                $emailConfig['smtp_username'] ?? '',
                $emailConfig['smtp_password'] ?? ''
            );
            
            // Prepare attachments
            $attachments = [];
            if ($pdfContent) {
                $attachments[$pdfFilename] = $pdfContent;
            }
            if ($extraFileContent && $extraFileName) {
                $attachments[$extraFileName] = $extraFileContent;
            }
            
            $result = $smtp->sendEmail(
                $emailConfig['from_address'] ?? 'noreply@feuerwehr.local',
                $emailConfig['from_name'] ?? 'Feuerwehr Management System',
                $to,
                $subject,
                $htmlBody,
                true, // HTML email
                $attachments
            );
            
            if (!$result) {
                throw new Exception("SMTP sendEmail returned false. Last response: " . $smtp->getLastResponse());
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log("Native SMTP failed: {$e->getMessage()}");
            // Re-throw the exception so caller can handle it
            throw new Exception("SMTP-Fehler: " . $e->getMessage());
        }
    }
    
    /**
     * Send email using PHPMailer (fallback)
     */
    private static function sendWithPhpMailer($to, $subject, $htmlBody, $pdfContent, $pdfFilename, $extraFileContent, $extraFileName) {
        if (!self::isPhpMailerAvailable()) {
            error_log("PHPMailer not available");
            return false;
        }
        
        $emailConfig = self::$config['email'] ?? [];
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        try {
            // Server settings
            if (!empty($emailConfig['smtp_host'])) {
                $mail->isSMTP();
                $mail->Host = $emailConfig['smtp_host'];
                $mail->Port = $emailConfig['smtp_port'] ?? 587;
                
                if (!empty($emailConfig['smtp_auth'])) {
                    $mail->SMTPAuth = true;
                    $mail->Username = $emailConfig['smtp_username'] ?? '';
                    $mail->Password = $emailConfig['smtp_password'] ?? '';
                }
                
                if (!empty($emailConfig['smtp_secure'])) {
                    if ($emailConfig['smtp_secure'] === 'tls') {
                        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                    } elseif ($emailConfig['smtp_secure'] === 'ssl') {
                        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
                    }
                }
            } else {
                $mail->isMail();
            }
            
            $mail->setFrom($emailConfig['from_address'] ?? 'noreply@feuerwehr.local', $emailConfig['from_name'] ?? 'Feuerwehr Management System');
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;
            $mail->AltBody = strip_tags($htmlBody);
            
            if ($pdfContent) {
                $mail->addStringAttachment($pdfContent, $pdfFilename, 'base64', 'application/pdf');
            }
            
            if ($extraFileContent && $extraFileName) {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mimeType = $finfo->buffer($extraFileContent);
                $mail->addStringAttachment($extraFileContent, $extraFileName, 'base64', $mimeType);
            }
            
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("PHPMailer failed: {$mail->ErrorInfo}");
            return false;
        }
    }
    
    /**
     * Send email using PHP mail() function (last resort fallback)
     */
    private static function sendWithPhpMail($to, $subject, $htmlBody, $pdfContent, $pdfFilename, $extraFileContent, $extraFileName) {
        $emailConfig = self::$config['email'] ?? [];
        
        $from = $emailConfig['from_address'] ?? 'noreply@feuerwehr.local';
        $fromName = $emailConfig['from_name'] ?? 'Feuerwehr Management System';
        
        $boundary = md5(time());
        $headers = [];
        $headers[] = "From: {$fromName} <{$from}>";
        $headers[] = "MIME-Version: 1.0";
        $headers[] = "Content-Type: multipart/mixed; boundary=\"{$boundary}\"";
        
        $message = "--{$boundary}\r\n";
        $message .= "Content-Type: text/html; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $message .= $htmlBody . "\r\n\r\n";
        
        if ($pdfContent) {
            $message .= "--{$boundary}\r\n";
            $message .= "Content-Type: application/pdf; name=\"{$pdfFilename}\"\r\n";
            $message .= "Content-Transfer-Encoding: base64\r\n";
            $message .= "Content-Disposition: attachment; filename=\"{$pdfFilename}\"\r\n\r\n";
            $message .= chunk_split(base64_encode($pdfContent)) . "\r\n";
        }
        
        if ($extraFileContent && $extraFileName) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->buffer($extraFileContent);
            
            $message .= "--{$boundary}\r\n";
            $message .= "Content-Type: {$mimeType}; name=\"{$extraFileName}\"\r\n";
            $message .= "Content-Transfer-Encoding: base64\r\n";
            $message .= "Content-Disposition: attachment; filename=\"{$extraFileName}\"\r\n\r\n";
            $message .= chunk_split(base64_encode($extraFileContent)) . "\r\n";
        }
        
        $message .= "--{$boundary}--";
        
        return mail($to, $subject, $message, implode("\r\n", $headers));
    }
    
    /**
     * Send email with HTML content and optional PDF attachment
     */
    public static function sendEmail($to, $subject, $htmlBody, $pdfContent = null, $pdfFilename = 'document.pdf') {
        return self::sendEmailWithAttachments($to, $subject, $htmlBody, $pdfContent, $pdfFilename, null, null);
    }
    
    /**
     * Generate PDF from HTML content
     * Simple implementation using wkhtmltopdf if available, or HTML2PDF
     */
    public static function generatePDF($html) {
        // Try to use wkhtmltopdf if available
        if (self::isCommandAvailable('wkhtmltopdf')) {
            return self::generatePDFWithWkhtmltopdf($html);
        }
        
        // Fallback: Return HTML content with note
        // In production, you would use a library like mPDF, TCPDF, or Dompdf
        return self::generateSimplePDF($html);
    }
    
    /**
     * Generate PDF using wkhtmltopdf
     */
    private static function generatePDFWithWkhtmltopdf($html) {
        $tmpHtml = tempnam(sys_get_temp_dir(), 'html_');
        $tmpPdf = tempnam(sys_get_temp_dir(), 'pdf_');
        
        file_put_contents($tmpHtml, $html);
        
        // Use escapeshellarg for safe command execution
        $command = 'wkhtmltopdf ' . escapeshellarg($tmpHtml) . ' ' . escapeshellarg($tmpPdf) . ' 2>&1';
        exec($command, $output, $returnVar);
        
        if ($returnVar === 0 && file_exists($tmpPdf)) {
            $pdfContent = file_get_contents($tmpPdf);
            unlink($tmpHtml);
            unlink($tmpPdf);
            return $pdfContent;
        }
        
        unlink($tmpHtml);
        if (file_exists($tmpPdf)) {
            unlink($tmpPdf);
        }
        
        return false;
    }
    
    /**
     * Simple PDF generation fallback
     * Creates a basic PDF structure
     */
    private static function generateSimplePDF($html) {
        // This is a very basic PDF implementation
        // For production, use a proper library like mPDF, TCPDF, or Dompdf
        
        // Convert HTML to plain text for basic PDF
        $text = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $html));
        
        // Basic PDF structure
        $pdf = "%PDF-1.4\n";
        $pdf .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        $pdf .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
        $pdf .= "3 0 obj\n<< /Type /Page /Parent 2 0 R /Resources 4 0 R /MediaBox [0 0 612 792] /Contents 5 0 R >>\nendobj\n";
        $pdf .= "4 0 obj\n<< /Font << /F1 << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> >> >>\nendobj\n";
        
        // Content stream
        $content = "BT\n/F1 12 Tf\n50 750 Td\n";
        $lines = explode("\n", $text);
        $y = 0;
        foreach ($lines as $line) {
            if ($y > 700) break; // Simple page limit
            $content .= "(" . addcslashes($line, '()\\') . ") Tj\n0 -15 Td\n";
            $y += 15;
        }
        $content .= "ET";
        
        $length = strlen($content);
        $pdf .= "5 0 obj\n<< /Length {$length} >>\nstream\n{$content}\nendstream\nendobj\n";
        
        // Cross-reference table
        $pdf .= "xref\n0 6\n0000000000 65535 f\n0000000009 00000 n\n0000000058 00000 n\n0000000115 00000 n\n0000000214 00000 n\n0000000308 00000 n\n";
        $pdf .= "trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n" . (strlen($pdf)) . "\n%%EOF";
        
        return $pdf;
    }
    
    /**
     * Check if a command is available
     */
    private static function isCommandAvailable($command) {
        // Whitelist of allowed commands to check
        $allowedCommands = ['wkhtmltopdf'];
        
        if (!in_array($command, $allowedCommands)) {
            return false;
        }
        
        // Use escapeshellarg for safe command execution
        $result = shell_exec('which ' . escapeshellarg($command));
        return !empty($result);
    }
    
    /**
     * Create mission report HTML from template
     */
    public static function generateMissionReportHTML($data) {
        require_once __DIR__ . '/datastore.php';
        $settings = DataStore::getSettings();
        
        $logo = self::getLogoPathFromSettings($settings);
        $fireDepartmentName = htmlspecialchars($settings['fire_department_name'] ?? 'Freiwillige Feuerwehr');
        $fireDepartmentCity = !empty($settings['fire_department_city']) ? '<br>' . htmlspecialchars($settings['fire_department_city']) : '';
        
        // Format dates
        $einsatzdatum = date('d.m.Y', strtotime($data['einsatzdatum']));
        $beginn = date('d.m.Y - H:i', strtotime($data['beginn']));
        $ende = date('d.m.Y - H:i', strtotime($data['ende']));
        
        // Calculate duration in minutes
        $start = strtotime($data['beginn']);
        $end = strtotime($data['ende']);
        $dauer = round(($end - $start) / 60);
        
        // Get vehicles as HTML
        $fahrzeuge = isset($data['eingesetzte_fahrzeuge']) ? implode('<br>', array_map('htmlspecialchars', $data['eingesetzte_fahrzeuge'])) : '-';
        
        $html = '<!DOCTYPE html>
<html>
<head>
    <style>
        table {
            width: 600px;
            border-collapse: collapse;
            font-family: Arial, sans-serif;
        }

        th {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
            vertical-align: top;
            font-size: 16px;
            background-color: #F0F0F0;
        }

        td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
            vertical-align: top;
            font-size: 16px;
            font-weight: normal;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        tr:nth-child(odd) {
            background-color: #ffffff;
        }

        .header-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .header-container h1 {
            margin: 0;
            font-size: 22px;
            font-weight: bold;
            font-style: italic;
            text-align: left;
        }

        .header-container img {
            max-height: 75px;
            height: auto;
            width: auto;
        }

        .header-line {
            border: none;
            border-top: 2px solid red;
            margin: 0;
        }

        h2 {
            margin-top: 12px;
            margin-bottom: 12px;
            font-size: 20px;
            text-align: left;
        }

        h3 {
            margin-top: 12px;
            margin-bottom: 12px;
            font-size: 18px;
            text-align: left;
        }
        
        .vehicle-section {
            margin-top: 15px;
            margin-bottom: 15px;
        }
        
        .vehicle-title {
            background-color: #e3f2fd;
            padding: 8px;
            font-weight: bold;
            border-left: 4px solid #1976d2;
            margin-bottom: 8px;
        }
    </style>
</head>
<body>
    <div class="header-container">
        <h1>' . $fireDepartmentName . $fireDepartmentCity . '</h1>
        ' . ($logo ? '<img src="' . $logo . '" alt="Logo ' . $fireDepartmentName . '">' : '') . '
    </div>
    <hr class="header-line">
    <h2>Einsatzbericht</h2>
    <table>
    <tbody>
        <tr>
            <th>Einsatzdatum</th>
            <td>' . htmlspecialchars($einsatzdatum) . '</td>
        </tr>
        <tr>
            <th>Einsatzgrund</th>
            <td>' . htmlspecialchars($data['einsatzgrund']) . '</td>
        </tr>
        <tr>
            <th>Einsatzort</th>
            <td>' . htmlspecialchars($data['einsatzort']) . '</td>
        </tr>
        <tr>
            <th>Einsatzleiter</th>
            <td>' . htmlspecialchars($data['einsatzleiter']) . '</td>
        </tr>
        <tr>
            <th>Einsatzbeginn</th>
            <td>' . htmlspecialchars($beginn) . '</td>
        </tr>
        <tr>
            <th>Einsatzende</th>
            <td>' . htmlspecialchars($ende) . '</td>
        </tr>
        <tr>
            <th>Einsatzdauer</th>
            <td>' . htmlspecialchars($dauer) . ' Minuten</td>
        </tr>
        <tr>
            <th>Einsatzlage</th>
            <td>' . nl2br(htmlspecialchars($data['einsatzlage'] ?? '')) . '</td>
        </tr>
        <tr>
            <th>Tätigkeiten der Feuerwehr</th>
            <td>' . nl2br(htmlspecialchars($data['tatigkeiten_der_feuerwehr'] ?? '')) . '</td>
        </tr>
        <tr>
            <th>Verbrauchte Materialien</th>
            <td>' . nl2br(htmlspecialchars($data['verbrauchte_mittel'] ?? '')) . '</td>
        </tr>
        <tr>
            <th>Besondere Vorkommnisse</th>
            <td>' . nl2br(htmlspecialchars($data['besondere_vorkommnisse'] ?? '')) . '</td>
        </tr>
        <tr>
            <th>Einsatz kostenpflichtig?</th>
            <td>' . htmlspecialchars($data['einsatz_kostenpflichtig']) . '</td>
        </tr>
        <tr>
            <th>Eingesetzte Fahrzeuge</th>
            <td>' . $fahrzeuge . '</td>
        </tr>
    </tbody>
    </table>
    <h3>Fahrzeugbesatzung</h3>
    ' . self::generateCrewByVehicle($data['fahrzeugbesatzung'] ?? [], $data['eingesetzte_fahrzeuge'] ?? []) . '
    <h3>Beteiligte Personen</h3>
    ' . self::generateInvolvedPersonsTable($data['beteiligte_personen'] ?? []) . '
</body>
</html>';
        
        return $html;
    }
    
    /**
     * Create attendance list HTML from template
     */
    public static function generateAttendanceHTML($data) {
        $logo = self::getLogoPath();
        
        // Format date
        $datum = date('d.m.Y', strtotime($data['datum']));
        
        // Handle leaders - they might be names directly or IDs
        $uebungsleiter = [];
        if (isset($data['uebungsleiter']) && is_array($data['uebungsleiter'])) {
            foreach ($data['uebungsleiter'] as $leader) {
                // Check if it's an ID (starts with 'pers_') or a direct name
                if (strpos($leader, 'pers_') === 0) {
                    // It's an ID, get the name
                    $person = DataStore::getPersonnelById($leader);
                    if ($person) {
                        $uebungsleiter[] = $person['name'];
                    }
                } else {
                    // It's already a name (from dropdown or text field)
                    $uebungsleiter[] = $leader;
                }
            }
        }
        
        // Get attendees names (these are IDs)
        $teilnehmer = self::getPersonnelNames($data['teilnehmer'] ?? []);
        $anzahl = count($data['teilnehmer'] ?? []);
        
        // Get duration
        $dauer = $data['dauer'] ?? 0;
        
        $html = '<!DOCTYPE html>
<html>
<head>
    <style>
        table {
            width: 600px;
            border-collapse: collapse;
            font-family: Arial, sans-serif;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
            vertical-align: top;
            font-size: 16px;
        }

        th {
            background-color: #F0F0F0;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        tr:nth-child(odd) {
            background-color: #ffffff;
        }

        .header-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .header-container h1 {
            margin: 0;
            font-size: 22px;
            font-weight: bold;
            font-style: italic;
            text-align: left;
        }

        .header-container img {
            max-height: 75px;
            height: auto;
            width: auto;
        }

        .header-line {
            border: none;
            border-top: 2px solid red;
            margin: 0;
        }

        h2 {
            margin-top: 12px;
            margin-bottom: 12px;
            font-size: 20px;
            text-align: left;
        }

        h3 {
            margin-top: 12px;
            margin-bottom: 12px;
            font-size: 18px;
            text-align: left;
        }
    </style>
</head>
<body>
    <div class="header-container">
        <h1>Freiwillige Feuerwehr<br>Willingshausen</h1>
        ' . ($logo ? '<img src="' . $logo . '" alt="Logo Feuerwehr Willingshausen">' : '') . '
    </div>
    <hr class="header-line">
    <h2>Anwesenheitsliste</h2>
    <table>
        <tr>
            <th>Datum</th>
            <td>' . htmlspecialchars($datum) . '</td>
        </tr>
        <tr>
            <th>Zeitraum</th>
            <td>' . htmlspecialchars($data['von']) . ' - ' . htmlspecialchars($data['bis']) . '</td>
        </tr>
        <tr>
            <th>Dauer</th>
            <td>' . htmlspecialchars($dauer) . ' Minuten</td>
        </tr>
        <tr>
            <th>Thema</th>
            <td>' . htmlspecialchars($data['thema']) . '</td>
        </tr>
        <tr>
            <th>Übungsleiter</th>
            <td>' . implode('<br>', array_map('htmlspecialchars', $uebungsleiter)) . '</td>
        </tr>
        <tr>
            <th>Teilnehmeranzahl</th>
            <td>' . $anzahl . '</td>
        </tr>
        <tr>
            <th>Teilnehmer</th>
            <td>' . implode('<br>', array_map('htmlspecialchars', $teilnehmer)) . '</td>
        </tr>
        <tr>
            <th>Anmerkungen</th>
            <td>' . nl2br(htmlspecialchars($data['anmerkungen'] ?? '')) . '</td>
        </tr>
    </table>
</body>
</html>';
        
        return $html;
    }
    
    /**
     * Generate personnel table HTML
     */
    private static function generatePersonnelTable($personnelIds) {
        if (empty($personnelIds)) {
            return '<p>Keine Personen ausgewählt</p>';
        }
        
        require_once __DIR__ . '/datastore.php';
        
        $html = '<table style="width: 600px; border-collapse: collapse; font-family: Arial, sans-serif;">';
        $html .= '<thead><tr><th style="border: 1px solid #ddd; padding: 10px; background-color: #F0F0F0;">Name</th></tr></thead>';
        $html .= '<tbody>';
        
        foreach ($personnelIds as $id) {
            $person = DataStore::getPersonnelById($id);
            if ($person) {
                $html .= '<tr><td style="border: 1px solid #ddd; padding: 10px;">' . htmlspecialchars($person['name']) . '</td></tr>';
            }
        }
        
        $html .= '</tbody></table>';
        return $html;
    }
    
    /**
     * Get personnel names from IDs
     */
    private static function getPersonnelNames($personnelIds) {
        if (empty($personnelIds)) {
            return [];
        }
        
        require_once __DIR__ . '/datastore.php';
        
        $names = [];
        foreach ($personnelIds as $id) {
            $person = DataStore::getPersonnelById($id);
            if ($person) {
                $names[] = $person['name'];
            }
        }
        
        return $names;
    }
    
    /**
     * Get logo path for embedding in HTML
     */
    private static function getLogoPath() {
        $logoPath = __DIR__ . '/../../public/assets/logo.png';
        if (file_exists($logoPath)) {
            // Convert to base64 for embedding
            $imageData = base64_encode(file_get_contents($logoPath));
            return 'data:image/png;base64,' . $imageData;
        }
        return null;
    }
    
    /**
     * Get logo path from settings
     */
    private static function getLogoPathFromSettings($settings) {
        if (!empty($settings['logo_filename'])) {
            $logoPath = __DIR__ . '/../../data/settings/' . $settings['logo_filename'];
            if (file_exists($logoPath)) {
                // Convert to base64 for embedding
                $imageData = base64_encode(file_get_contents($logoPath));
                $extension = pathinfo($settings['logo_filename'], PATHINFO_EXTENSION);
                $mimeType = $extension === 'svg' ? 'image/svg+xml' : 'image/' . $extension;
                return 'data:' . $mimeType . ';base64,' . $imageData;
            }
        }
        // Fallback to old logo
        return self::getLogoPath();
    }
    
    /**
     * Generate crew table grouped by vehicle
     */
    private static function generateCrewByVehicle($crewData, $vehicles) {
        if (empty($crewData)) {
            return '<p>Keine Fahrzeugbesatzung angegeben</p>';
        }
        
        // Group crew by vehicle
        $groupedCrew = [];
        foreach ($crewData as $member) {
            $vehicle = $member['fahrzeug'] ?? 'Unbekannt';
            if (!isset($groupedCrew[$vehicle])) {
                $groupedCrew[$vehicle] = [];
            }
            $groupedCrew[$vehicle][] = $member;
        }
        
        $html = '';
        foreach ($groupedCrew as $vehicle => $members) {
            $html .= '<div class="vehicle-section">';
            $html .= '<div class="vehicle-title">' . htmlspecialchars($vehicle) . '</div>';
            $html .= '<table><tbody>';
            $html .= '<tr><th>Funktion</th><th>Name</th><th>Verdienstausfall</th></tr>';
            
            foreach ($members as $member) {
                if (!empty($member['name']) || !empty($member['funktion'])) {
                    $html .= '<tr>';
                    $html .= '<td>' . htmlspecialchars($member['funktion'] ?? '-') . '</td>';
                    $html .= '<td>' . htmlspecialchars($member['name'] ?? '-') . '</td>';
                    $html .= '<td>' . (isset($member['verdienstausfall']) && $member['verdienstausfall'] === 'ja' ? 'Ja' : 'Nein') . '</td>';
                    $html .= '</tr>';
                }
            }
            
            $html .= '</tbody></table>';
            $html .= '</div>';
        }
        
        return $html;
    }
    
    /**
     * Generate table for involved persons
     */
    private static function generateInvolvedPersonsTable($persons) {
        if (empty($persons)) {
            return '<p>Keine beteiligten Personen angegeben</p>';
        }
        
        $html = '<table><tbody>';
        $html .= '<tr><th>Beteiligungsart</th><th>Name</th><th>Telefonnummer</th><th>Adresse</th><th>KFZ-Kennzeichen</th></tr>';
        
        foreach ($persons as $person) {
            if (!empty($person['name']) || !empty($person['beteiligungsart'])) {
                $html .= '<tr>';
                $html .= '<td>' . htmlspecialchars($person['beteiligungsart'] ?? '-') . '</td>';
                $html .= '<td>' . htmlspecialchars($person['name'] ?? '-') . '</td>';
                $html .= '<td>' . htmlspecialchars($person['telefonnummer'] ?? '-') . '</td>';
                $html .= '<td>' . nl2br(htmlspecialchars($person['adresse'] ?? '-')) . '</td>';
                $html .= '<td>' . htmlspecialchars($person['kfz_kennzeichen'] ?? '-') . '</td>';
                $html .= '</tr>';
            }
        }
        
        $html .= '</tbody></table>';
        return $html;
    }
}
