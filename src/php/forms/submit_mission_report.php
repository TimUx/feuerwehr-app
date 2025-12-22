<?php
/**
 * Submit Mission Report Form
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
        'einsatzdatum' => $_POST['einsatzdatum'] ?? '',
        'einsatzgrund' => $_POST['einsatzgrund'] ?? '',
        'einsatzort' => $_POST['einsatzort'] ?? '',
        'einsatzleiter' => $_POST['einsatzleiter'] ?? '',
        'beginn' => $_POST['beginn'] ?? '',
        'ende' => $_POST['ende'] ?? '',
        'einsatzlage' => $_POST['einsatzlage'] ?? '',
        'tatigkeiten_der_feuerwehr' => $_POST['tatigkeiten_der_feuerwehr'] ?? '',
        'verbrauchte_mittel' => $_POST['verbrauchte_mittel'] ?? '',
        'besondere_vorkommnisse' => $_POST['besondere_vorkommnisse'] ?? '',
        'einsatz_kostenpflichtig' => $_POST['einsatz_kostenpflichtig'] ?? 'Nein',
        'eingesetzte_fahrzeuge' => $_POST['eingesetzte_fahrzeuge'] ?? [],
        'fahrzeugbesatzung' => $_POST['fahrzeugbesatzung'] ?? [],
        'beteiligte_personen' => $_POST['beteiligte_personen'] ?? []
    ];
    
    // Validate required fields
    if (empty($data['einsatzdatum']) || empty($data['einsatzgrund']) || empty($data['einsatzort'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Bitte füllen Sie alle Pflichtfelder aus']);
        exit;
    }
    
    // Calculate duration
    $start = strtotime($data['beginn']);
    $end = strtotime($data['ende']);
    $durationHours = ($end - $start) / 3600;
    
    // Save to datastore
    $user = Auth::getUser();
    $reportData = array_merge($data, [
        'date' => $data['einsatzdatum'],
        'mission_type' => $data['einsatzgrund'],
        'location' => $data['einsatzort'],
        'description' => $data['einsatzlage'],
        'participants' => array_merge($data['fahrzeugbesatzung'], $data['beteiligte_personen']),
        'vehicles' => $data['eingesetzte_fahrzeuge'],
        'duration_hours' => $durationHours,
        'created_by' => $user['id']
    ]);
    
    $report = DataStore::createMissionReport($reportData);
    
    // Generate HTML for email
    $html = EmailPDF::generateMissionReportHTML($data);
    
    // Generate PDF
    $pdf = EmailPDF::generatePDF($html);
    
    // Get configuration for email recipient
    $config = require __DIR__ . '/../../../config/config.php';
    $recipient = $config['email']['from_address']; // Send to configured address
    
    // Send email with PDF attachment
    $subject = "Einsatzbericht - {$data['einsatzgrund']} - {$data['einsatzdatum']}";
    $emailSent = EmailPDF::sendEmail($recipient, $subject, $html, $pdf, "Einsatzbericht_{$data['einsatzdatum']}.pdf");
    
    if ($emailSent) {
        echo json_encode([
            'success' => true,
            'message' => 'Einsatzbericht wurde erfolgreich gespeichert und versendet',
            'report_id' => $report['id']
        ]);
    } else {
        // Still success if saved, but note email issue
        echo json_encode([
            'success' => true,
            'message' => 'Einsatzbericht wurde gespeichert, aber E-Mail konnte nicht versendet werden',
            'report_id' => $report['id']
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Fehler beim Verarbeiten des Berichts: ' . $e->getMessage()
    ]);
}
