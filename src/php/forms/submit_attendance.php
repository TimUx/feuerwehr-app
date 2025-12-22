<?php
/**
 * Submit Attendance Form
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
    // Get form data
    $data = [
        'datum' => $_POST['datum'] ?? '',
        'von' => $_POST['von'] ?? '',
        'bis' => $_POST['bis'] ?? '',
        'thema' => $_POST['thema'] ?? '',
        'anmerkungen' => $_POST['anmerkungen'] ?? '',
        'uebungsleiter' => $_POST['uebungsleiter'] ?? [],
        'teilnehmer' => $_POST['teilnehmer'] ?? []
    ];
    
    // Validate required fields
    if (empty($data['datum']) || empty($data['von']) || empty($data['bis']) || empty($data['thema'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Bitte füllen Sie alle Pflichtfelder aus']);
        exit;
    }
    
    if (empty($data['uebungsleiter'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Bitte wählen Sie mindestens einen Übungsleiter aus']);
        exit;
    }
    
    if (empty($data['teilnehmer'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Bitte wählen Sie mindestens einen Teilnehmer aus']);
        exit;
    }
    
    // Calculate duration
    $vonTime = strtotime($data['datum'] . ' ' . $data['von']);
    $bisTime = strtotime($data['datum'] . ' ' . $data['bis']);
    $durationHours = ($bisTime - $vonTime) / 3600;
    
    // Save to datastore
    $user = Auth::getUser();
    $attendanceData = [
        'date' => $data['datum'],
        'type' => 'training',
        'description' => $data['thema'],
        'duration_hours' => $durationHours,
        'attendees' => $data['teilnehmer'],
        'created_by' => $user['id']
    ];
    
    $attendance = DataStore::createAttendanceRecord($attendanceData);
    
    // Generate HTML for email
    $html = EmailPDF::generateAttendanceHTML($data);
    
    // Generate PDF
    $pdf = EmailPDF::generatePDF($html);
    
    // Get configuration for email recipient
    $config = require __DIR__ . '/../../../config/config.php';
    $recipient = $config['email']['from_address']; // Send to configured address
    
    // Send email with PDF attachment
    $subject = "Anwesenheitsliste - {$data['thema']} - {$data['datum']}";
    $emailSent = EmailPDF::sendEmail($recipient, $subject, $html, $pdf, "Anwesenheitsliste_{$data['datum']}.pdf");
    
    if ($emailSent) {
        echo json_encode([
            'success' => true,
            'message' => 'Anwesenheitsliste wurde erfolgreich gespeichert und versendet',
            'attendance_id' => $attendance['id']
        ]);
    } else {
        // Still success if saved, but note email issue
        echo json_encode([
            'success' => true,
            'message' => 'Anwesenheitsliste wurde gespeichert, aber E-Mail konnte nicht versendet werden',
            'attendance_id' => $attendance['id']
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Fehler beim Verarbeiten der Liste: ' . $e->getMessage()
    ]);
}
