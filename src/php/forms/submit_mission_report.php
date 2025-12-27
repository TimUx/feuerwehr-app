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
    $standortId = $_POST['standort'] ?? '';
    $recordId = $_POST['record_id'] ?? null; // Check if we're updating an existing record
    $data = [
        'einsatzgrund' => $_POST['einsatzgrund'] ?? '',
        'einsatzdatum' => $_POST['einsatzdatum'] ?? '',
        'beginn' => $_POST['beginn'] ?? '',
        'ende' => $_POST['ende'] ?? '',
        'dauer' => $_POST['dauer'] ?? 0,
        'einsatzort' => $_POST['einsatzort'] ?? '',
        'einsatzleiter' => $_POST['einsatzleiter'] ?? '',
        'einsatzlage' => $_POST['einsatzlage'] ?? '',
        'tatigkeiten_der_feuerwehr' => $_POST['tatigkeiten_der_feuerwehr'] ?? '',
        'verbrauchte_mittel' => $_POST['verbrauchte_mittel'] ?? '',
        'besondere_vorkommnisse' => $_POST['besondere_vorkommnisse'] ?? '',
        'einsatz_kostenpflichtig' => $_POST['einsatz_kostenpflichtig'] ?? 'nein',
        'eingesetzte_fahrzeuge' => $_POST['eingesetzte_fahrzeuge'] ?? [],
        'eingesetzte_fahrzeuge_custom' => $_POST['eingesetzte_fahrzeuge_custom'] ?? '',
        'anzahl_einsatzkrafte' => $_POST['anzahl_einsatzkrafte'] ?? 1,
        'fahrzeugbesatzung' => $_POST['fahrzeugbesatzung'] ?? [],
        'anzahl_beteiligter_personen' => $_POST['anzahl_beteiligter_personen'] ?? 0,
        'beteiligte_personen' => $_POST['beteiligte_personen'] ?? []
    ];
    
    // Add custom vehicle to list if provided
    if (!empty($data['eingesetzte_fahrzeuge_custom']) && is_array($data['eingesetzte_fahrzeuge'])) {
        $data['eingesetzte_fahrzeuge'][] = $data['eingesetzte_fahrzeuge_custom'];
    }
    
    // Validate required fields
    $requiredFields = ['einsatzgrund', 'einsatzdatum', 'beginn', 'ende', 'einsatzort', 'einsatzleiter', 'einsatzlage', 'tatigkeiten_der_feuerwehr'];
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "Pflichtfeld fehlt: {$field}"]);
            exit;
        }
    }
    
    // Validate at least one vehicle
    if (empty($data['eingesetzte_fahrzeuge']) || !is_array($data['eingesetzte_fahrzeuge']) || count($data['eingesetzte_fahrzeuge']) === 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Bitte wählen Sie mindestens ein Fahrzeug aus']);
        exit;
    }
    
    // Calculate duration in hours
    $start = strtotime($data['beginn']);
    $end = strtotime($data['ende']);
    $durationHours = ($end - $start) / 3600;
    
    // Save to datastore
    $user = Auth::getUser();
    
    if ($recordId) {
        // Update existing record
        $reportData = array_merge($data, [
            'date' => $data['einsatzdatum'],
            'mission_type' => $data['einsatzgrund'],
            'location' => $data['einsatzort'],
            'description' => $data['einsatzlage'],
            'participants' => array_merge($data['fahrzeugbesatzung'], $data['beteiligte_personen']),
            'vehicles' => $data['eingesetzte_fahrzeuge'],
            'duration_hours' => $durationHours,
            'updated_by' => $user['id'],
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        $report = DataStore::updateMissionReport($recordId, $reportData);
        $successMessage = 'Einsatzbericht wurde erfolgreich aktualisiert';
    } else {
        // Create new record
        $reportData = array_merge($data, [
            'id' => uniqid('mis_', true),
            'date' => $data['einsatzdatum'],
            'mission_type' => $data['einsatzgrund'],
            'location' => $data['einsatzort'],
            'description' => $data['einsatzlage'],
            'participants' => array_merge($data['fahrzeugbesatzung'], $data['beteiligte_personen']),
            'vehicles' => $data['eingesetzte_fahrzeuge'],
            'duration_hours' => $durationHours,
            'created_by' => $user['id'],
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        $report = DataStore::createMissionReport($reportData);
        $successMessage = 'Einsatzbericht wurde erfolgreich gespeichert und versendet';
    }
    
    // Generate HTML for email
    $html = EmailPDF::generateMissionReportHTML($data);
    
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
    
    // Ensure we have a recipient - if not configured, log error but continue
    $emailWillBeSent = !empty($recipient);
    if (!$emailWillBeSent) {
        error_log("Warning: No email recipient configured for mission report. Location ID: {$standortId}");
    }
    
    // Send email only if recipient is configured
    if ($emailWillBeSent) {
        // Send email with PDF attachment
        $subject = "Einsatzbericht - {$data['einsatzgrund']} - {$data['einsatzdatum']}";
        $emailSent = EmailPDF::sendEmail($recipient, $subject, $html, $pdf, "Einsatzbericht_{$data['einsatzdatum']}.pdf", $ccAddress);
        
        if ($emailSent) {
            echo json_encode([
                'success' => true,
                'message' => $successMessage,
                'report_id' => $report['id']
            ]);
        } else {
            // Still success if saved, but note email issue
            $errorMsg = EmailPDF::getLastError();
            echo json_encode([
                'success' => true,
                'message' => ($recordId ? 'Einsatzbericht wurde aktualisiert' : 'Einsatzbericht wurde gespeichert') . ', aber E-Mail konnte nicht versendet werden' . ($errorMsg ? ': ' . $errorMsg : ''),
                'report_id' => $report['id']
            ]);
        }
    } else {
        // No email configured
        echo json_encode([
            'success' => true,
            'message' => ($recordId ? 'Einsatzbericht wurde aktualisiert' : 'Einsatzbericht wurde gespeichert') . ', aber keine E-Mail-Adresse ist konfiguriert',
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
