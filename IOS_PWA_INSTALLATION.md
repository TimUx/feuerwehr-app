# PWA Installation auf iOS (iPhone/iPad)

## 📱 Installation der Feuerwehr-App auf iOS Geräten

Die Feuerwehr-App kann als Progressive Web App (PWA) auf iOS-Geräten installiert werden. Dies ermöglicht die Nutzung der App wie eine native App mit eigenem Icon auf dem Home-Bildschirm.

### ⚠️ Wichtiger Hinweis für iOS

Im Gegensatz zu Android-Geräten unterstützt iOS Safari **keine automatische Installationsaufforderung** für PWAs. Die Installation muss manuell durchgeführt werden.

---

## 📲 Schritt-für-Schritt Anleitung

### Schritt 1: App in Safari öffnen

1. Öffnen Sie **Safari** auf Ihrem iPhone oder iPad
2. Navigieren Sie zur App-URL: `https://ihre-domain.de`
3. Melden Sie sich an (optional: "Angemeldet bleiben" aktivieren)

> **Wichtig:** Die Installation funktioniert nur in **Safari**, nicht in anderen Browsern wie Chrome, Firefox oder DuckDuckGo!

---

### Schritt 2: Teilen-Menü öffnen

Tippen Sie auf das **Teilen-Symbol** in der Safari-Leiste:

```
┌─────────────────────────┐
│                         │
│    Safari Adressleiste  │
│                         │
└─────────────────────────┘
          │
          ▼
     [Teilen-Symbol]
     (Pfeil nach oben)
```

Das Teilen-Symbol befindet sich:
- **iPhone:** Unten in der Mitte der Safari-Leiste
- **iPad:** Oben rechts neben der Adressleiste

---

### Schritt 3: "Zum Home-Bildschirm" wählen

1. Im Teilen-Menü nach unten scrollen
2. **"Zum Home-Bildschirm"** antippen

```
┌─────────────────────────┐
│  Teilen-Menü            │
├─────────────────────────┤
│  Lesezeichen hinzufügen │
│  Leseliste hinzufügen   │
│  ↓ Nach unten scrollen  │
│  ...                    │
│  ► Zum Home-Bildschirm  │ ← Hier!
│  ...                    │
└─────────────────────────┘
```

---

### Schritt 4: Installation bestätigen

1. App-Name wird angezeigt: **"Feuerwehr"**
2. Icon wird angezeigt (Feuerwehr-Logo)
3. Auf **"Hinzufügen"** tippen

```
┌─────────────────────────┐
│  Zum Home-Bildschirm    │
├─────────────────────────┤
│  [Icon]                 │
│                         │
│  Name: Feuerwehr        │
│  URL: ihre-domain.de    │
│                         │
│  [Hinzufügen]           │ ← Hier!
└─────────────────────────┘
```

---

### Schritt 5: App nutzen

Die App erscheint jetzt als **Icon auf dem Home-Bildschirm** Ihres iOS-Geräts!

- Tippen Sie auf das Icon zum Öffnen
- Die App startet im Vollbild-Modus
- Keine Browser-Leiste sichtbar
- Funktioniert wie eine native App

---

## 🔍 Alternative Browser (DuckDuckGo, Chrome, Firefox)

### Problem

Andere Browser als Safari unterstützen **keine PWA-Installation** auf iOS:
- DuckDuckGo Browser ❌
- Chrome ❌
- Firefox ❌
- Edge ❌

### Lösung

**Option 1: Safari verwenden (empfohlen)**
1. URL in Safari öffnen
2. Anleitung oben befolgen

**Option 2: Lesezeichen erstellen**
1. Im alternativen Browser Lesezeichen hinzufügen
2. Schneller Zugriff über Browser-Lesezeichen
3. Kein App-Icon auf Home-Bildschirm

---

## ❓ Häufig gestellte Fragen

### Wird die App automatisch aktualisiert?

✅ **Ja!** Bei jedem Öffnen prüft die App automatisch auf Updates. Sie nutzen immer die neueste Version.

### Funktioniert die App offline?

⚠️ **Teilweise.** 
- Basis-Funktionen: ✅ Verfügbar
- Kartenfunktion: ❌ Benötigt Internet
- Formulare: ⚠️ Können ausgefüllt, aber erst online gesendet werden

### Kann ich mich automatisch anmelden?

✅ **Ja!** Aktivieren Sie beim Login **"Angemeldet bleiben"**. Sie bleiben dann 30 Tage lang angemeldet.

### Wie aktualisiere ich die App?

Die App aktualisiert sich automatisch. Sie müssen nichts tun!

Falls Sie manuell aktualisieren möchten:
1. App schließen
2. Safari öffnen
3. Zur App-URL navigieren
4. Einmal neu laden (↻)
5. App über Home-Bildschirm neu öffnen

### Wie deinstalliere ich die App?

1. Halten Sie das App-Icon gedrückt
2. Wählen Sie **"App entfernen"**
3. Bestätigen Sie mit **"Vom Home-Bildschirm entfernen"**

> **Hinweis:** Dies entfernt nur das Icon. Ihre Anmeldedaten und Cache bleiben in Safari gespeichert.

### Warum sehe ich keine Installationsaufforderung?

iOS Safari zeigt **keine automatische Aufforderung** für PWA-Installation. Dies ist ein Unterschied zu Android/Chrome, wo automatisch ein Banner erscheint.

**Lösung:** Manuelle Installation über Teilen-Menü (siehe Anleitung oben)

### Funktioniert die Benachrichtigungs-Funktion?

❌ **Nein.** iOS unterstützt aktuell keine Push-Benachrichtigungen für PWAs. Dies ist eine Einschränkung von Apple.

### Kann ich mehrere Accounts nutzen?

✅ **Ja, aber:**
- Pro iOS-Gerät nur eine Installation möglich
- Abmelden und mit anderem Account anmelden
- Oder: Safari im privaten Modus nutzen (ohne Installation)

---

## 🔒 Sicherheit & Datenschutz

### Wird "Angemeldet bleiben" empfohlen?

✅ **Sicher auf persönlichen Geräten**
- Token ist verschlüsselt
- Gültig für 30 Tage
- Wird bei Abmeldung gelöscht

⚠️ **Nicht auf geteilten Geräten**
- Auf gemeinsam genutzten iPads/iPhones nicht aktivieren
- Immer manuell abmelden nach der Nutzung

### Wo werden meine Daten gespeichert?

- **Login-Token:** Sicher verschlüsselt auf dem Server
- **Cache:** Lokal in Safari (nur für Offline-Funktionen)
- **Formulare:** Auf dem Server (AES-256 verschlüsselt)

---

## 🛠️ Troubleshooting

### "Zum Home-Bildschirm" fehlt im Menü

**Mögliche Ursachen:**
1. Nicht in Safari geöffnet → Safari verwenden
2. iOS-Version zu alt → iOS 11.3 oder neuer erforderlich
3. Browser im privaten Modus → Normalen Modus verwenden

**Lösung:**
1. Safari schließen
2. Safari im normalen Modus öffnen
3. Zur App navigieren
4. Erneut versuchen

### App-Icon erscheint nicht

**Mögliche Ursachen:**
1. Home-Bildschirm voll → Freien Platz schaffen
2. Installation abgebrochen → Erneut versuchen

**Lösung:**
1. Prüfen Sie, ob Icon auf anderem Home-Bildschirm vorhanden ist
2. Wischen Sie nach rechts durch alle Screens
3. Falls nicht gefunden: Installation wiederholen

### App startet nicht richtig

**Symptome:**
- Weißer Bildschirm
- Sofortiger Absturz
- Lädt nicht

**Lösung:**
1. **Hard Refresh:**
   - Safari öffnen
   - Zur App-URL navigieren
   - Seite neu laden (↻)
   - Schließen und über Icon öffnen

2. **Cache löschen:**
   - Einstellungen → Safari → Verlauf und Websitedaten löschen
   - App neu installieren

3. **iOS neu starten:**
   - iPhone/iPad neu starten
   - App erneut öffnen

### "Angemeldet bleiben" funktioniert nicht

**Mögliche Ursachen:**
1. Cookies blockiert
2. Safari Tracking-Schutz
3. Privater Modus

**Lösung:**
```
Einstellungen → Safari
├─ Cross-Site-Tracking verhindern: Aus
├─ Alle Cookies blockieren: Aus
└─ Betrugswarnung: An (kann an bleiben)
```

---

## 📱 Unterstützte iOS-Versionen

| iOS Version | PWA Support | Empfohlen |
|-------------|-------------|-----------|
| iOS 11.3+   | ✅ Basis    | ⚠️        |
| iOS 12.x    | ✅ Gut      | ✅        |
| iOS 13.x    | ✅ Gut      | ✅        |
| iOS 14.x    | ✅ Sehr gut | ✅        |
| iOS 15.x    | ✅ Sehr gut | ✅✅      |
| iOS 16.x+   | ✅ Sehr gut | ✅✅      |

**Mindestanforderung:** iOS 11.3 oder neuer

---

## 🆚 Vergleich: iOS vs. Android

| Feature                    | iOS Safari | Android Chrome |
|----------------------------|------------|----------------|
| Auto-Install-Aufforderung  | ❌         | ✅             |
| Manuelle Installation      | ✅         | ✅             |
| Offline-Funktionen         | ✅         | ✅             |
| Push-Benachrichtigungen    | ❌         | ✅             |
| Vollbild-Modus             | ✅         | ✅             |
| App-Update                 | ✅ Auto    | ✅ Auto        |
| Angemeldet bleiben         | ✅         | ✅             |

---

## 🎯 Best Practices

### Für Benutzer

1. ✅ **Safari verwenden** für Installation
2. ✅ **"Angemeldet bleiben"** aktivieren (auf persönlichen Geräten)
3. ✅ **WLAN nutzen** beim ersten Laden (schneller)
4. ✅ **iOS aktuell halten** (für beste Performance)
5. ❌ **Nicht im privaten Modus** installieren

### Für Administratoren

1. ✅ **HTTPS aktivieren** (für alle PWA-Features)
2. ✅ **Icons bereitstellen** (alle Größen)
3. ✅ **manifest.json konfigurieren**
4. ✅ **Service Worker optimieren**
5. ✅ **Benutzer schulen** (manuelle Installation)

---

## 📚 Weitere Ressourcen

- [Apple Developer: Configuring Web Applications](https://developer.apple.com/library/archive/documentation/AppleApplications/Reference/SafariWebContent/ConfiguringWebApplications/ConfiguringWebApplications.html)
- [MDN: Progressive Web Apps](https://developer.mozilla.org/en-US/docs/Web/Progressive_web_apps)
- [App Manifest Generator](https://www.simicart.com/manifest-generator.html/)

---

## 📞 Support

Bei Problemen:
1. Diese Anleitung vollständig durchlesen
2. Troubleshooting-Schritte ausprobieren
3. GitHub Issue erstellen mit:
   - iOS Version
   - Safari Version
   - Genaue Fehlerbeschreibung
   - Screenshots

---

**Entwickelt für die Freiwillige Feuerwehr Willingshausen** 🚒

Made with ❤️ in Germany
