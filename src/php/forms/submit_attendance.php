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
    // Handle file upload
    $uploadedFile = null;
    if (isset($_FILES['datei']) && $_FILES['datei']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../../data/uploads/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0700, true);
        }
        
        $fileName = uniqid() . '_' . basename($_FILES['datei']['name']);
        $uploadPath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['datei']['tmp_name'], $uploadPath)) {
            $uploadedFile = $fileName;
        }
    }
    
    // Get form data
    $leadersSelect = $_POST['uebungsleiter_select'] ?? [];
    $leadersOther = $_POST['uebungsleiter_andere'] ?? '';
    $standortId = $_POST['standort'] ?? '';
    $recordId = $_POST['record_id'] ?? null; // Check if we're updating an existing record
    
    // Combine leaders from select and text field
    $allLeaders = $leadersSelect;
    if (!empty($leadersOther)) {
        // Split by comma and trim
        $otherLeaders = array_map('trim', explode(',', $leadersOther));
        $allLeaders = array_merge($allLeaders, $otherLeaders);
    }
    
    $data = [
        'datum' => $_POST['datum'] ?? '',
        'von' => $_POST['von'] ?? '',
        'bis' => $_POST['bis'] ?? '',
        'dauer' => $_POST['dauer'] ?? 0,
        'thema' => $_POST['thema'] ?? '',
        'anmerkungen' => $_POST['anmerkungen'] ?? '',
        'datei' => $uploadedFile,
        'uebungsleiter' => $allLeaders,
        'teilnehmer' => $_POST['teilnehmer'] ?? [],
        'standort' => $_POST['standort'] ?? ''
    ];
    
    // Validate required fields
    if (empty($data['datum']) || empty($data['von']) || empty($data['bis']) || empty($data['thema'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Bitte füllen Sie alle Pflichtfelder aus']);
        exit;
    }
    
    if (empty($data['uebungsleiter'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Bitte wählen Sie mindestens einen Übungsleiter aus oder geben Sie einen Namen ein']);
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
    
    // Calculate total participant count
    $totalParticipants = count($data['uebungsleiter']) + count($data['teilnehmer']);
    
    // Save to datastore with all data including file reference
    $user = Auth::getUser();
    
    if ($recordId) {
        // Update existing record
        $attendanceData = array_merge($data, [
            'date' => $data['datum'],
            'type' => 'training',
            'description' => $data['thema'],
            'duration_hours' => $durationHours,
            'attendees' => $data['teilnehmer'],
            'total_participants' => $totalParticipants,
            'location_id' => $data['standort'],
            'updated_by' => $user['id'],
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        $attendance = DataStore::updateAttendanceRecord($recordId, $attendanceData);
        $successMessage = 'Anwesenheitsliste wurde erfolgreich aktualisiert';
    } else {
        // Create new record
        $attendanceData = array_merge($data, [
            'id' => uniqid('att_', true),
            'date' => $data['datum'],
            'type' => 'training',
            'description' => $data['thema'],
            'duration_hours' => $durationHours,
            'attendees' => $data['teilnehmer'],
            'total_participants' => $totalParticipants,
            'location_id' => $data['standort'],
            'created_by' => $user['id'],
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        $attendance = DataStore::createAttendanceRecord($attendanceData);
        $successMessage = 'Anwesenheitsliste wurde erfolgreich gespeichert und versendet';
    }
    
    // Generate HTML for email
    $html = EmailPDF::generateAttendanceHTML($data);
    
    // Generate PDF
    $pdf = EmailPDF::generatePDF($html);
    
    // Get configuration and location for email recipients
    $config = require __DIR__ . '/../../../config/config.php';
    $ccAddress = $config['email']['from_address'] ?? null; // CC to general settings email
    
    // Get location email address as primary recipient
    $recipient = null;
    if (!empty($standortId)) {
        $location = DataStore::getLocationById($standortId);
        if ($location && !empty($location['email'])) {
            $recipient = $location['email'];
        }
    }
    
    // If no location email, fall back to the general email
    if (empty($recipient)) {
        $recipient = $ccAddress;
        $ccAddress = null; // No CC needed if using fallback
    }
    
    // Ensure we have a recipient
    if (empty($recipient)) {
        echo json_encode([
            'success' => true,
            'message' => 'Anwesenheitsliste wurde gespeichert, aber es ist keine E-Mail-Adresse konfiguriert',
            'attendance_id' => $attendance['id']
        ]);
        exit;
    }
    
    // Prepare file attachment if uploaded
    $fileAttachment = null;
    $fileAttachmentName = null;
    if ($uploadedFile) {
        $filePath = __DIR__ . '/../../data/uploads/' . $uploadedFile;
        if (file_exists($filePath)) {
            $fileAttachment = file_get_contents($filePath);
            $fileAttachmentName = $uploadedFile;
        }
    }
    
    // Send email with PDF and optional file attachment
    $subject = "Anwesenheitsliste - {$data['thema']} - {$data['datum']}";
    $emailSent = EmailPDF::sendEmailWithAttachments(
        $recipient,
        $subject,
        $html,
        $pdf,
        "Anwesenheitsliste_{$data['datum']}.pdf",
        $fileAttachment,
        $fileAttachmentName,
        $ccAddress
    );
    
    if ($emailSent) {
        echo json_encode([
            'success' => true,
            'message' => $successMessage,
            'attendance_id' => $attendance['id']
        ]);
    } else {
        // Still success if saved, but note email issue
        echo json_encode([
            'success' => true,
            'message' => ($recordId ? 'Anwesenheitsliste wurde aktualisiert' : 'Anwesenheitsliste wurde gespeichert') . ', aber E-Mail konnte nicht versendet werden',
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
