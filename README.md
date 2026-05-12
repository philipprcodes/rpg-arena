# ⚔️ RPG Arena Alpha

Ein rundbasiertes Browser-RPG, entwickelt als Schulprojekt während meiner Umschulung zum **Fachinformatiker Anwendungsentwicklung**.

🌐 **[Jetzt Alpha Version spielen auf philippcodes.de](https://www.philippcodes.de)**

---

## 🏆 Projekthintergrund

- **Bearbeitungszeit:** 3 Wochen (inkl. Erstellung einer Präsentation und 2 Präsentationstagen)
- **Zeitpunkt:** Nach nur 6 Monaten Umschulung
- **Bewertung:** sehr gut
- **Besonderheit:** Alleine entwickelt, ohne vorherige OOP-Kenntnisse – rein prozedurales PHP

Das Projekt entstand im Rahmen einer Webprojektarbeit mit PHP, HTML und CSS. Ziel war es, ein funktionsfähiges, datenbankgestütztes Webprojekt zu entwickeln. Da OOP zu diesem Zeitpunkt noch nicht Teil des Lehrplans war, wurde das gesamte Projekt prozedural umgesetzt.

---

## 🎮 Features

- **Rundenbasiertes Kampfsystem** mit Initiative, Statuseffekten und Kampflog
- **Statuseffekte** – Poison, Burn, Freeze, Bleed, Para, Stun
- **AoE & Single-Target Angriffe** mit verschiedenen Effekten
- **Execute-Mechanik** – Bonusschaden unter 20% HP
- **Taunt-System** – Gegner auf sich ziehen
- **Schattenbegleiter** – Creature Collector / Mehrdimensionales Kampfsystem
- **Charakter-Erstellung** mit Punkteverteilung (Stärke, Geschick, Intelligenz)
- **Level-System** mit XP und Level-Up Boni
- **Inventar & Ausrüstungssystem** mit verschiedenen Slots
- **Shop-System** mit Gold-Währung
- **Loot-System** nach gewonnenen Kämpfen
- **Mehrere Stages** mit steigendem Schwierigkeitsgrad
- **Benutzer-Authentifizierung** mit sicherem Login & Registrierung
---

## 🛠️ Technologien

| Technologie | Verwendung |
|---|---|
| PHP 8.x | Backend, Spiellogik, Sessions |
| MySQL 8.0 | Datenbank (Charaktere, Items, Monster) |
| HTML5 / CSS3 / | Frontend, Pixelart-Design |
| JavaScript | UI-Interaktionen |
| mysqli | Datenbankanbindung mit Prepared Statements |

---

## 🐛 Debugging

Zur Laufzeitanalyse wurde eine passwortgeschützte Debug-Seite eingesetzt, die Session-Variablen zur Überprüfung des Spielzustands ausgab.

---

## 🔒 Sicherheit

Obwohl es ein Lernprojekt ist, wurden bewusst Sicherheitsstandards eingehalten:

- Prepared Statements gegen SQL-Injection
- `password_hash` / `password_verify` für Passwörter
- Session-Validierung auf jeder Seite
- Reload-Schutz bei Kampfaktionen

---

## 🗄️ Datenbankstruktur

Das Projekt nutzt 12 Tabellen:

`anwender` · `charakterliste` · `charakterinventar` · `charakterausruestung` · `charakterschatten` · `monster` · `schattenmonster` · `attacken` · `attackenzuweisung` · `waffen` · `gegenstaende` · `loottable`

---

## 📁 Projektstruktur

```
PHPProjekt/
├── login.php               ← Benutzer-Login
├── registrieren.php        ← Account-Erstellung
├── charaktercreation.php   ← Charakter erstellen & Punkte verteilen
├── charakter.php           ← Charakteransicht & Statuswerte
├── maingame.php            ← Haupt-Hub (Navigation, Übersicht)
├── kampfstart.php          ← Kampf initialisieren
├── kampflogik.php          ← Kampfablauf & Rundenlogik
├── kampflog.php            ← Kampflog Darstellung
├── kampfinventar.php       ← Inventar im Kampf
├── kampfgewonnen.php       ← Siegesbildschirm & Loot
├── gameover.php            ← Niederlage-Bildschirm
├── levelup.php             ← Level-Up Anzeige
├── leveluplogik.php        ← Level-Up Berechnung
├── lootgenerieren.php      ← Loot-Generierung
├── inventar.php            ← Inventarverwaltung
├── ausruesten.php          ← Ausrüstungsverwaltung
├── entnahme.php            ← Items entnehmen
├── shop.php                ← Shop-Ansicht
├── shopinteraktion.php     ← Kauf-Logik
├── shopinventar.php        ← Shop-Inventar
├── Logout.php              ← Session beenden und Speichern
├── funktionen.php          ← Zentrale Spielfunktionen
├── backgroundlogik.php     ← Stage-Hintergründe
├── dbconfig.php            ← Datenbankverbindung
├── script.js               ← UI-Interaktionen
├── style_neu.css           ← Komplettes Styling
└── Images/
    ├── Backgrounds/        ← Stage-Hintergründe (WebP)
    ├── Character/          ← Charakter-Sprites
    └── Monsters/           ← Monster-Sprites & Shop-Bilder
```

---

## 💡 Was ich gelernt habe

- Prozedurale PHP-Entwicklung und Session-Management
- Datenbankdesign mit Fremdschlüsselbeziehungen
- Sicherheitskonzepte (SQL-Injection, Passwort-Hashing)
- Responsive CSS-Design für Mobile & Desktop
- Deployment auf Shared Hosting (FTP, phpMyAdmin, .htaccess)

---

## 📌 Hinweise zum Code

Der Code spiegelt bewusst den Lernstand nach 6 Monaten wider – prozedural, auf Deutsch benannte Variablen, wenig Abstraktion. Das ist gewollt: Es zeigt einen authentischen Entwicklungsstand und was in kurzer Zeit ohne OOP-Grundlagen möglich ist. Heute würde ich vieles anders angehen – objektorientiert, mit mehr Abstraktion und strikter Trennung von Logik und Darstellung.

---

## 🤖 KI-Unterstützung

Für Teile des HTML/CSS-Designs wurde Google Gemini als Hilfsmittel eingesetzt.
Die Spiellogik, Datenbankstruktur und das PHP-Backend wurden vollständig
eigenständig entwickelt.

---

*Entwickelt von Philipp | Umschulung Fachinformatiker Anwendungsentwicklung 2025/2026*
