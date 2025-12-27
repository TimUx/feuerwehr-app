<?php
/**
 * API endpoint to generate and download PDFs for forms (attendance or mission reports)
 */

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
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Ungültige Anfrage']);
        exit;
    }
    
    $type = $input['type'];
    $id = $input['id'];
    
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
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Anwesenheitsliste nicht gefunden']);
            exit;
        }
        
        // Generate HTML
        $html = EmailPDF::generateAttendanceHTML($record);
        
        // Generate PDF
        $pdf = EmailPDF::generatePDF($html);
        
        // Check if PDF generation was successful
        if ($pdf === false || empty($pdf)) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Fehler beim Generieren des PDFs. Bitte überprüfen Sie die Serverprotokolle.']);
            exit;
        }
        
        // Sanitize filename
        $datumSafe = preg_replace('/[^a-zA-Z0-9\-_]/', '_', $record['datum'] ?? date('Y-m-d'));
        
        // Output PDF
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="Anwesenheitsliste_' . $datumSafe . '.pdf"');
        header('Content-Length: ' . strlen($pdf));
        echo $pdf;
        exit;
        
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
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Einsatzbericht nicht gefunden']);
            exit;
        }
        
        // Generate HTML
        $html = EmailPDF::generateMissionReportHTML($report);
        
        // Generate PDF
        $pdf = EmailPDF::generatePDF($html);
        
        // Check if PDF generation was successful
        if ($pdf === false || empty($pdf)) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Fehler beim Generieren des PDFs. Bitte überprüfen Sie die Serverprotokolle.']);
            exit;
        }
        
        // Sanitize filename
        $datumSafe = preg_replace('/[^a-zA-Z0-9\-_]/', '_', $report['einsatzdatum'] ?? date('Y-m-d'));
        
        // Output PDF
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="Einsatzbericht_' . $datumSafe . '.pdf"');
        header('Content-Length: ' . strlen($pdf));
        echo $pdf;
        exit;
        
    } else {
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Ungültiger Typ']);
        exit;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Fehler beim Generieren des PDFs: ' . $e->getMessage()
    ]);
}
