<?php
/**
 * Hazardous Materials API
 */

require_once __DIR__ . '/../auth.php';

Auth::requireOperator();

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET' && isset($_GET['un'])) {
    // Search by UN number
    $unNumber = $_GET['un'];
    $material = searchHazardousMaterial($unNumber);
    
    if ($material) {
        echo json_encode(['success' => true, 'data' => $material]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gefahrstoff nicht gefunden']);
    }
    exit;
}

function searchHazardousMaterial($unNumber) {
    // Comprehensive database of common hazardous materials
    $materials = [
        '1011' => [
            'un' => '1011',
            'name' => 'Butan',
            'class' => '2.1',
            'classLabel' => 'Entzündbare Gase',
            'dangers' => ['Hochentzündlich', 'Unter Druck stehend'],
            'hazardLabels' => ['2.1'],
            'packingGroup' => '-',
            'description' => 'Farbloses, brennbares Gas. Schwerer als Luft.',
            'firstAid' => 'Bei Einatmen: Person an die frische Luft bringen. Bei Bewusstlosigkeit: stabile Seitenlage.',
            'firefighting' => 'Kühlung der Behälter mit Wasser. Gasflamme nicht löschen, wenn nicht gefahrlos möglich.',
            'spillage' => 'Bereich absperren. Lüften. Zündquellen beseitigen.'
        ],
        '1202' => [
            'un' => '1202',
            'name' => 'Dieselkraftstoff',
            'class' => '3',
            'classLabel' => 'Entzündbare flüssige Stoffe',
            'dangers' => ['Entzündbar', 'Umweltgefährdend'],
            'hazardLabels' => ['3'],
            'packingGroup' => 'III',
            'description' => 'Leichtentzündliche Flüssigkeit. Kann Krebs erzeugen.',
            'firstAid' => 'Bei Hautkontakt: Mit viel Wasser und Seife waschen. Bei Einatmen: Frischluft.',
            'firefighting' => 'Schaum, Pulver, CO2. Kein Vollstrahl.',
            'spillage' => 'Mit Bindemittel aufnehmen. Nicht in Kanalisation gelangen lassen.'
        ],
        '1203' => [
            'un' => '1203',
            'name' => 'Benzin, Ottokraftstoff',
            'class' => '3',
            'classLabel' => 'Entzündbare flüssige Stoffe',
            'dangers' => ['Hochentzündlich', 'Gesundheitsschädlich', 'Umweltgefährdend'],
            'hazardLabels' => ['3'],
            'packingGroup' => 'II',
            'description' => 'Hochentzündliche Flüssigkeit und Dampf. Kann Krebs erzeugen.',
            'firstAid' => 'Bei Verschlucken: KEIN Erbrechen herbeiführen. Sofort Arzt konsultieren.',
            'firefighting' => 'Schaum, Pulver, CO2. Behälter kühlen.',
            'spillage' => 'Zündquellen beseitigen. Mit Bindemittel aufnehmen. Dämpfe niederschlagen.'
        ],
        '1005' => [
            'un' => '1005',
            'name' => 'Ammoniak, wasserfrei',
            'class' => '2.3',
            'classLabel' => 'Giftige Gase',
            'dangers' => ['Giftig', 'Ätzend', 'Umweltgefährdend'],
            'hazardLabels' => ['2.3', '8'],
            'packingGroup' => '-',
            'description' => 'Giftiges, ätzendes Gas. Stechender Geruch.',
            'firstAid' => 'Bei Einatmen: Frischluft. Bei Augenkontakt: 15 Min. mit Wasser spülen. Sofort Arzt!',
            'firefighting' => 'Mit Sprühstrahl kühlen. Umgebungsbrände mit geeigneten Löschmitteln bekämpfen.',
            'spillage' => 'Sofort großräumig absperren. Gaswolke mit Sprühstrahl niederschlagen.'
        ],
        '1965' => [
            'un' => '1965',
            'name' => 'Propan-Butan-Gemisch, verflüssigt',
            'class' => '2.1',
            'classLabel' => 'Entzündbare Gase',
            'dangers' => ['Hochentzündlich', 'Unter Druck'],
            'hazardLabels' => ['2.1'],
            'packingGroup' => '-',
            'description' => 'Flüssiggas, hochentzündlich. Schwerer als Luft, sammelt sich in Senken.',
            'firstAid' => 'Bei Erfrierungen durch Kontakt mit Gas: Nicht reiben. Mit lauwarmem Wasser spülen.',
            'firefighting' => 'Behälter kühlen. Gasflamme nicht löschen außer Leck kann geschlossen werden.',
            'spillage' => 'Zündquellen beseitigen. Bereich absperren und lüften.'
        ],
        '1230' => [
            'un' => '1230',
            'name' => 'Methanol',
            'class' => '3',
            'classLabel' => 'Entzündbare flüssige Stoffe',
            'dangers' => ['Hochentzündlich', 'Giftig'],
            'hazardLabels' => ['3', '6.1'],
            'packingGroup' => 'II',
            'description' => 'Hochentzündlich. Giftig bei Einatmen, Verschlucken und Hautkontakt.',
            'firstAid' => 'Bei Verschlucken: Sofort Arzt! KEIN Erbrechen. Bei Hautkontakt: Sofort ausziehen und abwaschen.',
            'firefighting' => 'Alkoholbeständiger Schaum, Pulver, CO2. Behälter kühlen.',
            'spillage' => 'Mit Bindemittel aufnehmen. Personen evakuieren. Dämpfe sind giftig!'
        ],
        '1824' => [
            'un' => '1824',
            'name' => 'Natriumhydroxid-Lösung (Natronlauge)',
            'class' => '8',
            'classLabel' => 'Ätzende Stoffe',
            'dangers' => ['Ätzend', 'Verursacht schwere Verätzungen'],
            'hazardLabels' => ['8'],
            'packingGroup' => 'II',
            'description' => 'Verursacht schwere Verätzungen der Haut und schwere Augenschäden.',
            'firstAid' => 'Bei Hautkontakt: Sofort mit viel Wasser abwaschen. Kontaminierte Kleidung entfernen.',
            'firefighting' => 'Nach Umgebung. Behälter kühlen. Vorsicht bei Reaktion mit Wasser.',
            'spillage' => 'Mit Sand oder Bindemittel aufnehmen. Neutralisieren mit Säure (Vorsicht!).'
        ],
        '1789' => [
            'un' => '1789',
            'name' => 'Salzsäure',
            'class' => '8',
            'classLabel' => 'Ätzende Stoffe',
            'dangers' => ['Ätzend', 'Reizt die Atemwege'],
            'hazardLabels' => ['8'],
            'packingGroup' => 'II',
            'description' => 'Verursacht schwere Verätzungen. Dämpfe sind ätzend.',
            'firstAid' => 'Bei Augenkontakt: 15 Min. mit Wasser spülen. Bei Verschlucken: Wasser trinken, KEIN Erbrechen.',
            'firefighting' => 'Nach Umgebung. Behälter kühlen. Vorsicht: Entwickelt giftige Dämpfe!',
            'spillage' => 'Mit Sand abstreuen, aufnehmen. Neutralisieren mit Kalkwasser oder Soda.'
        ],
        '1170' => [
            'un' => '1170',
            'name' => 'Ethanol, Ethylalkohol',
            'class' => '3',
            'classLabel' => 'Entzündbare flüssige Stoffe',
            'dangers' => ['Hochentzündlich'],
            'hazardLabels' => ['3'],
            'packingGroup' => 'II',
            'description' => 'Hochentzündliche Flüssigkeit und Dampf.',
            'firstAid' => 'Bei Einatmen: Frischluft. Bei Verschlucken: Kein Erbrechen herbeiführen.',
            'firefighting' => 'Alkoholbeständiger Schaum, Pulver, CO2.',
            'spillage' => 'Zündquellen beseitigen. Mit Bindemittel aufnehmen.'
        ],
        '1428' => [
            'un' => '1428',
            'name' => 'Natrium',
            'class' => '4.3',
            'classLabel' => 'Stoffe die mit Wasser entzündbare Gase entwickeln',
            'dangers' => ['Reagiert heftig mit Wasser', 'Entzündbar'],
            'hazardLabels' => ['4.3'],
            'packingGroup' => 'I',
            'description' => 'Entwickelt bei Berührung mit Wasser entzündbare Gase. Kann sich spontan entzünden.',
            'firstAid' => 'Bei Hautkontakt: Vorsichtig mit Öl entfernen, dann mit Wasser spülen. KEIN direktes Wasser!',
            'firefighting' => 'KEIN WASSER! Nur Metallbrandpulver (D-Pulver) oder trockener Sand.',
            'spillage' => 'Trocken aufnehmen. Von Wasser fernhalten. Nur speziell ausgebildete Kräfte.'
        ],
        '1950' => [
            'un' => '1950',
            'name' => 'Druckgaspackungen (Aerosole)',
            'class' => '2.1',
            'classLabel' => 'Entzündbare Gase',
            'dangers' => ['Entzündbar', 'Unter Druck', 'Behälter kann bei Erhitzung bersten'],
            'hazardLabels' => ['2.1'],
            'packingGroup' => '-',
            'description' => 'Behälter steht unter Druck. Kann bei Erhitzung bersten.',
            'firstAid' => 'Bei Einatmen: Frischluft. Bei Augenkontakt: Mit Wasser spülen.',
            'firefighting' => 'Behälter aus Gefahrenbereich entfernen. Mit Wasser kühlen. Explosionsgefahr!',
            'spillage' => 'Lüften. Zündquellen beseitigen.'
        ],
        '2031' => [
            'un' => '2031',
            'name' => 'Salpetersäure',
            'class' => '8',
            'classLabel' => 'Ätzende Stoffe',
            'dangers' => ['Ätzend', 'Oxidierend'],
            'hazardLabels' => ['8', '5.1'],
            'packingGroup' => 'II',
            'description' => 'Stark ätzend. Kann Brand verstärken. Entwickelt giftige Dämpfe.',
            'firstAid' => 'Bei Hautkontakt: Sofort 15 Min. mit Wasser spülen. Kontaminierte Kleidung entfernen.',
            'firefighting' => 'Nach Umgebung. Vorsicht: Verstärkt Verbrennung! Dämpfe sind giftig.',
            'spillage' => 'Mit viel Wasser verdünnen. Nicht in Kanalisation. Neutralisieren mit Kalk.'
        ],
        '1086' => [
            'un' => '1086',
            'name' => 'Vinylchlorid, stabilisiert',
            'class' => '2.1',
            'classLabel' => 'Entzündbare Gase',
            'dangers' => ['Hochentzündlich', 'Krebserzeugend', 'Unter Druck'],
            'hazardLabels' => ['2.1'],
            'packingGroup' => '-',
            'description' => 'Hochentzündliches Gas. Kann Krebs erzeugen. Kann explosive Atmosphäre bilden.',
            'firstAid' => 'Bei Einatmen: Sofort Frischluft. Person warm halten. Bei Erfrierungen: Mit lauwarmem Wasser.',
            'firefighting' => 'Behälter kühlen. Gasflamme nur löschen wenn Leck geschlossen werden kann.',
            'spillage' => 'Großräumig absperren. Zündquellen beseitigen. Dämpfe mit Sprühstrahl niederschlagen.'
        ],
        '1978' => [
            'un' => '1978',
            'name' => 'Propan',
            'class' => '2.1',
            'classLabel' => 'Entzündbare Gase',
            'dangers' => ['Hochentzündlich', 'Unter Druck', 'Erstickend'],
            'hazardLabels' => ['2.1'],
            'packingGroup' => '-',
            'description' => 'Hochentzündliches, unter Druck verflüssigtes Gas. Schwerer als Luft.',
            'firstAid' => 'Bei Erfrierung: Betroffene Stellen mit lauwarmem Wasser spülen, nicht reiben.',
            'firefighting' => 'Behälter kühlen. Gasflamme nicht löschen, außer Leck kann geschlossen werden.',
            'spillage' => 'Bereich absperren. Zündquellen beseitigen. Gas sammelt sich in Vertiefungen.'
        ],
        '1547' => [
            'un' => '1547',
            'name' => 'Anilin',
            'class' => '6.1',
            'classLabel' => 'Giftige Stoffe',
            'dangers' => ['Giftig', 'Umweltgefährdend', 'Krebserzeugend'],
            'hazardLabels' => ['6.1'],
            'packingGroup' => 'II',
            'description' => 'Giftig bei Einatmen, Verschlucken und Hautkontakt. Kann Krebs erzeugen.',
            'firstAid' => 'Bei Hautkontakt: Sofort mit Wasser und Seife waschen. Kontaminierte Kleidung entfernen.',
            'firefighting' => 'Schaum, Pulver, CO2. Vorsicht: Giftige Verbrennungsgase!',
            'spillage' => 'Mit Bindemittel aufnehmen. Nicht in Gewässer gelangen lassen. CSA und Handschuhe!'
        ]
    ];
    
    return $materials[$unNumber] ?? null;
}
