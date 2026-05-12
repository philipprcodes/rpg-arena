<?php
// ============================================================
// DATENBANK KONFIGURATION - BEISPIELDATEI
// ============================================================
// 1. Diese Datei kopieren und als "dbconfig.php" speichern
// 2. Die Platzhalter mit deinen echten Zugangsdaten ersetzen
// 3. dbconfig.php NIEMALS in Git committen!
// ============================================================

$server  = "localhost";          // Datenbankserver (meist "localhost")
$user    = "DEIN_DB_BENUTZER";   // Datenbankbenutzer
$passwort = "DEIN_DB_PASSWORT";  // Datenbankpasswort
$db      = "DEIN_DB_NAME";       // Datenbankname

// Verbindung aufbauen
$con = mysqli_connect($server, $user, $passwort, $db);

// Verbindungsprüfung
if (!$con) {
    die("Verbindung zur Datenbank fehlgeschlagen. Bitte Administrator kontaktieren.");
}

// Zeichensatz
mysqli_set_charset($con, "utf8mb4");
?>
