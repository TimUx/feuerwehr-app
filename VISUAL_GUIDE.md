# Offline PWA - Visueller Leitfaden

## Neue UI-Komponenten

### 1. Online/Offline-Statusanzeige (unten rechts)

**Wenn OFFLINE:**
```
┌─────────────────────────┐
│  🔴  Offline            │
└─────────────────────────┘
```
- Erscheint unten rechts
- Roter Rahmen
- Rotes Cloud-Off-Icon
- Text: "Offline"

**Wenn ONLINE:**
```
┌─────────────────────────┐
│  ✅  Online             │
└─────────────────────────┘
```
- Verschwindet automatisch
- Grüner Rahmen
- Grünes Cloud-Done-Icon
- Text: "Online"

### 2. Sync-Button in der Kopfzeile

**Normal (keine ausstehenden Formulare):**
```
[🌙] [🔄] [🚪]
      ↑
   Versteckt
```

**Mit ausstehenden Formularen:**
```
[🌙] [🔄③] [🚪]
      ↑
   Badge mit Anzahl
```
- Erscheint rechts neben Theme-Toggle
- Badge pulsiert mit Anzahl
- Klick startet manuelle Synchronisation
- Rotiert während Sync

### 3. Offline-Banner in Formularen

**Anwesenheitsliste / Einsatzbericht (wenn offline):**
```
┌─────────────────────────────────────────────────────┐
│  ⚠️  Offline-Modus                                  │
│  Formulare können offline ausgefüllt werden und     │
│  werden automatisch gesendet, sobald Sie wieder     │
│  online sind.                                       │
└─────────────────────────────────────────────────────┘
```
- Gelber Hintergrund
- Erscheint oben im Formular
- Verschwindet wenn online

### 4. Benachrichtigungen

**Bei Offline-Speicherung:**
```
┌─────────────────────────────────────────────────────┐
│  ⚠️  Keine Internetverbindung. Anwesenheitsliste   │
│  wurde offline gespeichert und wird automatisch     │
│  gesendet, sobald Sie wieder online sind.          │
└─────────────────────────────────────────────────────┘
```

**Bei erfolgreicher Synchronisation:**
```
┌─────────────────────────────────────────────────────┐
│  ✅  2 Formular(e) erfolgreich synchronisiert      │
└─────────────────────────────────────────────────────┘
```

**Bei Sync-Fehler:**
```
┌─────────────────────────────────────────────────────┐
│  ❌  Synchronisierung fehlgeschlagen               │
└─────────────────────────────────────────────────────┘
```

## Benutzer-Workflows

### Szenario 1: Offline Formular ausfüllen

```
1. Benutzer öffnet Anwesenheitsliste
   └─► Offline-Banner erscheint (gelb)
   └─► Status-Indikator zeigt "Offline" (rot, unten rechts)

2. Benutzer füllt Formular aus
   └─► Alle Felder funktionieren normal
   └─► Datepicker, Dropdowns, etc. funktionieren

3. Benutzer klickt "Absenden"
   └─► Warnung erscheint (gelb):
       "Keine Internetverbindung. Anwesenheitsliste wurde
        offline gespeichert..."
   └─► Formular wird zurückgesetzt
   └─► Sync-Button erscheint mit Badge "1"

4. Benutzer kann weitermachen
   └─► Weitere Formulare ausfüllen
   └─► Badge erhöht sich: "2", "3", etc.
```

### Szenario 2: Automatische Synchronisation

```
1. Internet kehrt zurück
   └─► Status-Indikator wechselt zu "Online" (grün)
   └─► Automatische Sync startet (im Hintergrund)

2. Während Sync
   └─► Sync-Button rotiert
   └─► Badge bleibt sichtbar

3. Nach erfolgreicher Sync
   └─► Benachrichtigung erscheint (grün):
       "2 Formular(e) erfolgreich synchronisiert"
   └─► Badge verschwindet
   └─► Sync-Button verschwindet
   └─► Formulare wurden an Server gesendet
   └─► E-Mails wurden versendet
```

### Szenario 3: Manuelle Synchronisation

```
1. Benutzer ist online
   └─► Hat ausstehende Formulare (Badge "2")

2. Benutzer klickt Sync-Button
   └─► Button dreht sich
   └─► Formulare werden übermittelt

3. Nach Sync
   └─► Benachrichtigung zeigt Ergebnis
   └─► Badge wird aktualisiert oder verschwindet
```

## Browser-Entwicklertools

### IndexedDB anzeigen (Chrome)

```
F12 → Application Tab → IndexedDB
└─► FeuerwehrAppDB
    └─► pending-forms
        └─► Gespeicherte Formulare anzeigen
```

**Struktur eines gespeicherten Formulars:**
```json
{
  "id": 1,
  "type": "Anwesenheitsliste",
  "url": "/src/php/forms/submit_attendance.php",
  "data": FormData {},
  "timestamp": "2025-01-15T10:30:00.000Z",
  "status": "pending"
}
```

### Cache Storage anzeigen (Chrome)

```
F12 → Application Tab → Cache Storage
└─► feuerwehr-app-static-v2
    ├─► /, /index.php, /public/css/style.css
    ├─► /public/js/app.js
    └─► /public/icons/...
└─► feuerwehr-app-dynamic-v2
    └─► Seiteninhalte
└─► feuerwehr-app-api-v2
    └─► API-Responses
```

### Service Worker anzeigen (Chrome)

```
F12 → Application Tab → Service Workers
└─► sw.js
    └─► Status: activated and is running
    └─► Update on reload ☐
```

### Offline-Modus simulieren (Chrome)

```
F12 → Network Tab
└─► Online ▼
    ├─ Online
    ├─ Slow 3G
    ├─ Fast 3G
    └─ Offline  ◄─── Auswählen
```

### Konsolen-Logs

**Beim Laden der App:**
```
[SW] Installing service worker...
[SW] Caching static assets
[SW] Activating service worker...
[App] Offline support initialized
[OfflineStorage] Database opened successfully
[OfflineUI] Offline UI initialized
```

**Bei Offline-Formular:**
```
Form submission error: TypeError: Failed to fetch
[OfflineStorage] Form saved offline: Anwesenheitsliste 1
[OfflineUI] Pending count: 1
[OfflineStorage] Background sync registered
```

**Bei Sync:**
```
[SW] Background sync triggered
[SW] Found 2 pending forms to sync
[OfflineStorage] Submitting form: 1 Anwesenheitsliste
[SW] Successfully synced form: 1
[OfflineUI] Form synced by service worker: 1
[OfflineStorage] Form submitted successfully: 1
```

## Mobile Ansicht

### iPhone/Android Portrait

```
┌─────────────────────────┐
│ ☰ 🚒 Feuerwehr   🔄② 🌙 │  ← Header mit Sync-Badge
├─────────────────────────┤
│                         │
│ [Formularinhalt]        │
│                         │
│                         │
│                         │
│                         │
│                         │
├─────────────────────────┤
│        🔴 Offline       │  ← Status unten rechts
└─────────────────────────┘
```

## Farben

```css
/* Status-Indikator */
--offline-color: #f44336  /* Rot */
--online-color: #4caf50   /* Grün */

/* Banner */
--warning-color: #ff9800  /* Orange/Gelb */

/* Benachrichtigungen */
--success: #4caf50        /* Grün */
--error: #f44336          /* Rot */
--warning: #ff9800        /* Orange */
--info: #2196f3           /* Blau */
```

## Animationen

### Pulse (Badge)
```css
@keyframes pulse {
  0%, 100% { scale: 1; opacity: 1; }
  50% { scale: 1.1; opacity: 0.8; }
}
```

### Rotate (Sync-Button)
```css
@keyframes rotate {
  from { rotate: 0deg; }
  to { rotate: 360deg; }
}
```

### Slide-In (Benachrichtigungen)
```css
transform: translateX(100%) → translateX(0)
opacity: 0 → 1
```

## Responsive Design

### Desktop (>768px)
- Status-Indikator: unten rechts, 20px margin
- Benachrichtigungen: oben rechts, max-width 400px
- Sync-Button: normal size

### Mobile (<768px)
- Status-Indikator: unten rechts, 10px margin, kleinere Schrift
- Benachrichtigungen: oben, left/right 10px, volle Breite
- Sync-Button: touch-optimiert (größer)

## Testing-Checkliste

✅ **Visuell:**
- [ ] Status-Indikator erscheint offline
- [ ] Status-Indikator verschwindet online
- [ ] Sync-Button erscheint mit Badge
- [ ] Badge zeigt korrekte Anzahl
- [ ] Banner erscheint in Formularen
- [ ] Benachrichtigungen erscheinen
- [ ] Animationen funktionieren

✅ **Funktional:**
- [ ] Formular offline absenden
- [ ] In IndexedDB gespeichert
- [ ] Automatische Sync funktioniert
- [ ] Manuelle Sync funktioniert
- [ ] E-Mails werden nach Sync versendet
- [ ] Fehlerbehandlung funktioniert

✅ **Responsive:**
- [ ] Desktop-Ansicht korrekt
- [ ] Mobile-Ansicht korrekt
- [ ] Touch-Interaktion funktioniert

## Zusammenfassung

Alle UI-Komponenten sind:
✅ **Sichtbar** und **verständlich**
✅ **Funktional** und **zuverlässig**
✅ **Responsive** für alle Bildschirmgrößen
✅ **Animiert** für bessere UX
✅ **Barrierefrei** mit klaren Icons und Texten
