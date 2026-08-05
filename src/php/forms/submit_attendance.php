<?php
/**
 * Submit Attendance Form
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../datastore.php';
require_once __DIR__ . '/../email_pdf.php';

Auth::init();
sendSecurityHeaders();
Auth::requireOperator();
Auth::requireCsrfToken();

try {
    // Handle file upload (validated MIME + size; random safe filename)
    $uploadedFile = null;
    if (isset($_FILES['datei']) && $_FILES['datei']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['datei']['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Datei-Upload fehlgeschlagen']);
            exit;
        }

        $maxBytes = 10 * 1024 * 1024; // 10 MB
        if (($_FILES['datei']['size'] ?? 0) > $maxBytes) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Datei ist zu groß (max. 10 MB)']);
            exit;
        }

        $allowedMimeToExt = [
            'application/pdf' => 'pdf',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/vnd.oasis.opendocument.text' => 'odt',
            'text/plain' => 'txt',
        ];

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($_FILES['datei']['tmp_name']);
        if (!isset($allowedMimeToExt[$mimeType])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Ungültiger Dateityp. Erlaubt: PDF, Bilder, DOC/DOCX, ODT, TXT']);
            exit;
        }

        $uploadDir = __DIR__ . '/../../data/uploads/';
        if (!file_exists($uploadDir)) {
            if (!@mkdir($uploadDir, 0700, true)) {
                error_log("Failed to create upload directory: " . $uploadDir);
                throw new Exception('upload_dir');
            }
        }

        $fileName = 'att_' . bin2hex(random_bytes(16)) . '.' . $allowedMimeToExt[$mimeType];
        $uploadPath = $uploadDir . $fileName;

        if (!move_uploaded_file($_FILES['datei']['tmp_name'], $uploadPath)) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Datei konnte nicht gespeichert werden']);
            exit;
        }
        chmod($uploadPath, 0600);
        $uploadedFile = $fileName;
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
    if ($vonTime === false || $bisTime === false) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Ungültige Zeitangaben']);
        exit;
    }
    // Overnight sessions (e.g. 22:00–02:00)
    if ($bisTime < $vonTime) {
        $bisTime += 24 * 3600;
    }
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
    
    // Get email configuration and location for email recipients
    $generalEmail = DataStore::getDefaultRecipient();
    
    // Get location email address as primary recipient
    $recipient = null;
    $ccAddress = null;
    
    if (!empty($standortId)) {
        $location = DataStore::getLocationById($standortId);
        if ($location && !empty($location['email'])) {
            // Location has email - send only to location (no CC)
            $recipient = $location['email'];
        }
    }
    
    // If no location email, fall back to the general email
    if (empty($recipient)) {
        $recipient = $generalEmail;
    }
    
    // Ensure we have a recipient - if not configured, log error but continue
    $emailWillBeSent = !empty($recipient);
    if (!$emailWillBeSent) {
        error_log("Warning: No email recipient configured for attendance list. Location ID: {$standortId}");
    }
    
    // Send email only if recipient is configured
    if ($emailWillBeSent) {
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
            $errorMsg = EmailPDF::getLastError();
            echo json_encode([
                'success' => true,
                'message' => ($recordId ? 'Anwesenheitsliste wurde aktualisiert' : 'Anwesenheitsliste wurde gespeichert') . ', aber E-Mail konnte nicht versendet werden' . ($errorMsg ? ': ' . $errorMsg : ''),
                'attendance_id' => $attendance['id']
            ]);
        }
    } else {
        // No email configured
        echo json_encode([
            'success' => true,
            'message' => ($recordId ? 'Anwesenheitsliste wurde aktualisiert' : 'Anwesenheitsliste wurde gespeichert') . ', aber keine E-Mail-Adresse ist konfiguriert',
            'attendance_id' => $attendance['id']
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    error_log('Attendance submit failed: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Fehler beim Verarbeiten der Liste. Bitte versuchen Sie es erneut.'
    ]);
}
