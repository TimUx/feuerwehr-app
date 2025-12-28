# Offline PWA Funktionalität

Diese Dokumentation beschreibt die implementierte Offline-Funktionalität für die Feuerwehr Management App.

## Übersicht

Die App unterstützt jetzt vollständige Offline-Funktionalität für kritische Features:

### ✅ Implementierte Features

1. **Erweiterte Service Worker Caching-Strategie**
   - Cache-First für statische Assets (CSS, JS, Icons, Fonts)
   - Network-First mit Cache-Fallback für API-Endpunkte
   - Dynamisches Caching für Seiteninhalte
   - Intelligentes Cache-Versioning und Cleanup

2. **IndexedDB für Offline-Formular-Speicherung**
   - Formulare können offline ausgefüllt werden
   - Daten werden lokal in IndexedDB gespeichert
   - Automatische Synchronisation bei Verbindungswiederherstellung

3. **Background Sync API**
   - Automatische Formular-Übermittlung im Hintergrund
   - Fallback auf manuelle Synchronisation
   - Service Worker Event-Handler für Sync-Events

4. **Benutzeroberfläche für Offline-Modus**
   - Online/Offline-Statusanzeige (unten rechts)
   - Sync-Button mit Badge für ausstehende Formulare
   - Benachrichtigungssystem für Sync-Feedback
   - Offline-Banner auf Formularseiten

## Verwendung

### Für Benutzer

#### Offline-Formulare ausfüllen

1. **Navigieren Sie zu einem Formular** (Anwesenheitsliste oder Einsatzbericht)
2. **Wenn offline:** Ein gelber Banner wird oben im Formular angezeigt
3. **Füllen Sie das Formular aus** wie gewohnt
4. **Klicken Sie auf "Absenden"**
5. **Das Formular wird lokal gespeichert** und zeigt eine Bestätigung

#### Synchronisation

**Automatisch:**
- Wenn die Verbindung wiederhergestellt wird, synchronisiert die App automatisch alle ausstehenden Formulare
- Eine Benachrichtigung bestätigt erfolgreiche Synchronisationen

**Manuell:**
- Klicken Sie auf das Sync-Symbol (🔄) in der Kopfzeile
- Das Badge zeigt die Anzahl der ausstehenden Formulare
- Nach dem Klicken werden alle ausstehenden Formulare sofort übermittelt

#### Offline-Status

- **Grünes Symbol:** Online und synchronisiert
- **Rotes Symbol:** Offline-Modus aktiv
- Der Status wird automatisch aktualisiert

## Technische Details

### Architektur

```
┌─────────────────┐
│   Service       │
│   Worker        │
│   (sw.js)       │
└────────┬────────┘
         │
         ├──► Cache Strategy
         │    ├─ Static Cache
         │    ├─ Dynamic Cache
         │    └─ API Cache
         │
         └──► Background Sync
              └─ IndexedDB Sync

┌─────────────────┐
│  Offline        │
│  Storage        │
│  (IndexedDB)    │
└────────┬────────┘
         │
         ├──► Save Forms
         ├──► Get Pending
         └──► Sync Forms

┌─────────────────┐
│  Offline UI     │
│  Manager        │
└────────┬────────┘
         │
         ├──► Status Indicator
         ├──► Sync Button
         └──► Notifications
```

### Unterstützte Formulare

- ✅ **Anwesenheitsliste** (`/src/php/forms/submit_attendance.php`)
- ✅ **Einsatzbericht** (`/src/php/forms/submit_mission_report.php`)

### Cache-Strategien

#### Cache-First (Statische Assets)
```
Request → Cache → Network (fallback)
```
Verwendet für:
- CSS-Dateien
- JavaScript-Dateien
- Bilder und Icons
- Fonts

#### Network-First (API & Pages)
```
Request → Network → Cache (fallback)
```
Verwendet für:
- API-Endpunkte (Personnel, Vehicles, Locations, etc.)
- Seiteninhalte
- Dynamische Daten

#### Network-Only (Formulare & Verwaltung)
```
Request → Network (no cache)
```
Verwendet für:
- Formular-Submissions (außer bei Offline)
- Admin-Funktionen
- Benutzer-Verwaltung

### Browser-Unterstützung

| Feature | Chrome/Edge | Firefox | Safari | Mobile |
|---------|------------|---------|--------|--------|
| Service Worker | ✅ | ✅ | ✅ | ✅ |
| IndexedDB | ✅ | ✅ | ✅ | ✅ |
| Background Sync | ✅ | ❌* | ❌* | ⚠️** |
| Cache API | ✅ | ✅ | ✅ | ✅ |

*Fallback auf manuelle Synchronisation verfügbar
**Teilweise unterstützt auf Android Chrome

### Datenspeicherung

Alle offline gespeicherten Formulare werden in IndexedDB gespeichert:

**Datenbank:** `FeuerwehrAppDB`
**Object Store:** `pending-forms`

**Gespeicherte Felder:**
- `id` - Auto-increment ID
- `type` - Formulartyp (Anwesenheitsliste/Einsatzbericht)
- `url` - Ziel-URL für Submission
- `data` - FormData-Objekt
- `timestamp` - Zeitstempel der Speicherung
- `status` - Status (pending/synced)

## Sicherheit

- ✅ Alle Daten werden nur lokal im Browser gespeichert
- ✅ Keine sensiblen Daten werden im Cache gespeichert
- ✅ Formulardaten werden nach erfolgreicher Synchronisation gelöscht
- ✅ HTTPS erforderlich für Service Worker in Produktion

## Bekannte Einschränkungen

1. **Datei-Uploads:** Datei-Uploads in der Anwesenheitsliste funktionieren offline, werden aber mit dem Formular gespeichert
2. **Browser-Storage-Limits:** IndexedDB hat Browser-abhängige Speichergrenzen (typisch 50-100MB)
3. **Background Sync:** Nicht in allen Browsern verfügbar (siehe Browser-Unterstützung)

## Fehlerbehebung

### Problem: Formulare werden nicht synchronisiert

**Lösung:**
1. Überprüfen Sie die Internetverbindung
2. Klicken Sie auf das Sync-Symbol in der Kopfzeile
3. Öffnen Sie die Browser-Konsole (F12) für Details

### Problem: Offline-Status wird nicht angezeigt

**Lösung:**
1. Stellen Sie sicher, dass JavaScript aktiviert ist
2. Löschen Sie den Browser-Cache und laden Sie die Seite neu
3. Überprüfen Sie, dass der Service Worker registriert ist (F12 → Application → Service Workers)

### Problem: Cache wird nicht aktualisiert

**Lösung:**
1. Die App verwendet Cache-Versionierung - alte Caches werden automatisch gelöscht
2. Bei Problemen: Browser-Cache manuell löschen
3. Service Worker-Update erzwingen: F12 → Application → Service Workers → "Update"

## Entwickler-Informationen

### Debugging

**Browser-Entwicklertools:**
```
F12 → Application Tab
├─ Service Workers: Status und Registrierung
├─ Cache Storage: Gecachte Ressourcen anzeigen
└─ IndexedDB: Offline-Formulare anzeigen
```

**Konsolen-Logs:**
- `[SW]` - Service Worker-Logs
- `[OfflineStorage]` - Offline Storage-Logs
- `[OfflineUI]` - UI Manager-Logs
- `[App]` - App-Logs

### Testing Offline-Modus

**Chrome DevTools:**
1. F12 → Network Tab
2. "Online" Dropdown → "Offline" wählen
3. Formular ausfüllen und testen

**Firefox DevTools:**
1. F12 → Network Tab
2. "No Throttling" Dropdown → "Offline" wählen

## Zukünftige Erweiterungen

- [ ] Offline-Support für weitere Formulare
- [ ] Konfliktlösung bei gleichzeitigen Änderungen
- [ ] Datenkompression für größere Formulare
- [ ] Erweiterte Sync-Strategien
- [ ] Push-Benachrichtigungen bei erfolgreicher Synchronisation

## Support

Bei Fragen oder Problemen:
1. Siehe README.md für allgemeine Informationen
2. GitHub Issues für Bug-Reports
3. Entwickler-Konsole für technische Details
