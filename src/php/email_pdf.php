<?php
/**
 * Email and PDF Generation Helper
 * Uses PHPMailer for email and mPDF for PDF generation
 */

class EmailPDF {
    private static $config;
    
    private static function init() {
        if (!self::$config) {
            self::$config = require __DIR__ . '/../../config/config.php';
        }
    }
    
    /**
     * Send email with HTML content and optional PDF attachment
     */
    public static function sendEmail($to, $subject, $htmlBody, $pdfContent = null, $pdfFilename = 'document.pdf') {
        self::init();
        
        $from = self::$config['email']['from_address'];
        $fromName = self::$config['email']['from_name'];
        
        // Set up headers
        $headers = [];
        $headers[] = "MIME-Version: 1.0";
        $headers[] = "From: {$fromName} <{$from}>";
        
        // If we have a PDF attachment, create multipart message
        if ($pdfContent) {
            $boundary = md5(time());
            $headers[] = "Content-Type: multipart/mixed; boundary=\"{$boundary}\"";
            
            $message = "--{$boundary}\r\n";
            $message .= "Content-Type: text/html; charset=UTF-8\r\n";
            $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
            $message .= $htmlBody . "\r\n\r\n";
            
            // Attach PDF
            $message .= "--{$boundary}\r\n";
            $message .= "Content-Type: application/pdf; name=\"{$pdfFilename}\"\r\n";
            $message .= "Content-Transfer-Encoding: base64\r\n";
            $message .= "Content-Disposition: attachment; filename=\"{$pdfFilename}\"\r\n\r\n";
            $message .= chunk_split(base64_encode($pdfContent)) . "\r\n";
            $message .= "--{$boundary}--";
        } else {
            $headers[] = "Content-Type: text/html; charset=UTF-8";
            $message = $htmlBody;
        }
        
        // Send email
        return mail($to, $subject, $message, implode("\r\n", $headers));
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
        $logo = self::getLogoPath();
        
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
    </style>
</head>
<body>
    <div class="header-container">
        <h1>Freiwillige Feuerwehr<br>Willingshausen</h1>
        ' . ($logo ? '<img src="' . $logo . '" alt="Logo Feuerwehr Willingshausen">' : '') . '
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
    ' . self::generatePersonnelTable($data['fahrzeugbesatzung'] ?? []) . '
    <h3>Beteiligte Personen</h3>
    ' . self::generatePersonnelTable($data['beteiligte_personen'] ?? []) . '
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
        
        // Get leaders and attendees names
        $uebungsleiter = self::getPersonnelNames($data['uebungsleiter'] ?? []);
        $teilnehmer = self::getPersonnelNames($data['teilnehmer'] ?? []);
        $anzahl = count($data['teilnehmer'] ?? []);
        
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
}
