<?php
/**
 * API endpoint to resend form emails (attendance or mission reports)
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../datastore.php';
require_once __DIR__ . '/../email_pdf.php';

// Initialize authentication
Auth::init();

// Check authentication
if (!Auth::isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Nicht authentifiziert']);
    exit;
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['type']) || !isset($input['id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Ungültige Anfrage']);
        exit;
    }
    
    $type = $input['type'];
    $id = $input['id'];
    
    // Get configuration for email recipient
    $config = require __DIR__ . '/../../../config/config.php';
    $recipient = $config['email']['from_address'];
    
    if ($type === 'attendance') {
        // Get attendance record
        $records = DataStore::getAttendanceRecords();
        $record = null;
        
        foreach ($records as $r) {
            if ($r['id'] === $id) {
                $record = $r;
                break;
            }
        }
        
        if (!$record) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Anwesenheitsliste nicht gefunden']);
            exit;
        }
        
        // Generate HTML for email
        $html = EmailPDF::generateAttendanceHTML($record);
        
        // Generate PDF
        $pdf = EmailPDF::generatePDF($html);
        
        // Prepare file attachment if exists
        $fileAttachment = null;
        $fileAttachmentName = null;
        if (!empty($record['datei'])) {
            $filePath = __DIR__ . '/../../data/uploads/' . $record['datei'];
            if (file_exists($filePath)) {
                $fileAttachment = file_get_contents($filePath);
                $fileAttachmentName = $record['datei'];
            }
        }
        
        // Send email with PDF and optional file attachment
        $thema = !empty($record['thema']) ? $record['thema'] : 'Anwesenheitsliste';
        $datum = !empty($record['datum']) ? $record['datum'] : date('Y-m-d');
        $subject = "Anwesenheitsliste - {$thema} - {$datum}";
        $emailSent = EmailPDF::sendEmailWithAttachments(
            $recipient,
            $subject,
            $html,
            $pdf,
            "Anwesenheitsliste_{$record['datum']}.pdf",
            $fileAttachment,
            $fileAttachmentName
        );
        
        if ($emailSent) {
            echo json_encode([
                'success' => true,
                'message' => 'E-Mail wurde erfolgreich versendet'
            ]);
        } else {
            $errorMsg = EmailPDF::getLastError();
            echo json_encode([
                'success' => false,
                'message' => 'E-Mail konnte nicht versendet werden' . ($errorMsg ? ': ' . $errorMsg : '')
            ]);
        }
        
    } elseif ($type === 'mission') {
        // Get mission report
        $reports = DataStore::getMissionReports();
        $report = null;
        
        foreach ($reports as $r) {
            if ($r['id'] === $id) {
                $report = $r;
                break;
            }
        }
        
        if (!$report) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Einsatzbericht nicht gefunden']);
            exit;
        }
        
        // Generate HTML for email
        $html = EmailPDF::generateMissionReportHTML($report);
        
        // Generate PDF
        $pdf = EmailPDF::generatePDF($html);
        
        // Send email with PDF
        $einsatzgrund = !empty($report['einsatzgrund']) ? $report['einsatzgrund'] : 'Einsatzbericht';
        $einsatzdatum = !empty($report['einsatzdatum']) ? $report['einsatzdatum'] : date('Y-m-d');
        $subject = "Einsatzbericht - {$einsatzgrund} - {$einsatzdatum}";
        $emailSent = EmailPDF::sendEmail($recipient, $subject, $html, $pdf, "Einsatzbericht_{$report['einsatzdatum']}.pdf");
        
        if ($emailSent) {
            echo json_encode([
                'success' => true,
                'message' => 'E-Mail wurde erfolgreich versendet'
            ]);
        } else {
            $errorMsg = EmailPDF::getLastError();
            echo json_encode([
                'success' => false,
                'message' => 'E-Mail konnte nicht versendet werden' . ($errorMsg ? ': ' . $errorMsg : '')
            ]);
        }
        
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Ungültiger Typ']);
        exit;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Fehler beim Versenden der E-Mail: ' . $e->getMessage()
    ]);
}
