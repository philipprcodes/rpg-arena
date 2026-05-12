<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ------------------------------------------------------------------
// Sicherheitscheck
// ------------------------------------------------------------------
if (!isset($_SESSION['ID']) || !isset($_POST['save_stats']) || !isset($_SESSION['levelup'])) {
    header("Location: maingame.php");
    exit;
}

require_once('dbconfig.php');
include_once("funktionen.php");

$id = $_SESSION['ID'];
$punkteProLevel = 5;

//  Aktuelle Werte aus der DB holen (Sicherheitscheck)
$sql = "SELECT Staerke, Geschicklichkeit, Intelligenz, Level FROM charakterliste WHERE AnwenderID = ?";
$stmt = mysqli_prepare($con, $sql);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$abfrage = mysqli_stmt_get_result($stmt);
$spieler = mysqli_fetch_assoc($abfrage);

$baseStr = (int)$spieler['Staerke'];
$baseDex = (int)$spieler['Geschicklichkeit'];
$baseInt = (int)$spieler['Intelligenz'];
$oldLevel = (int)$spieler['Level'];

//  Eingaben vom Formular holen
$newStr = (int)$_POST['staerke'];
$newDex = (int)$_POST['geschick'];
$newInt = (int)$_POST['int'];

//  Berechnung prüfen
$summeAlt = $baseStr + $baseDex + $baseInt;
$summeNeu = $newStr + $newDex + $newInt;
$differenz = $summeNeu - $summeAlt;

// Punktecheck
if ($differenz < $punkteProLevel) {
    $_SESSION['levelup_error'] = "Du hast noch Punkte übrig!";
    header("Location: maingame.php"); 
    exit;
}
if ($differenz > $punkteProLevel || $newStr < $baseStr || $newDex < $baseDex || $newInt < $baseInt) {
    $_SESSION['levelup_error'] = "Ungültige Verteilung (Zu viele Punkte oder Cheat-Versuch)!";
    header("Location: maingame.php");
    exit;
}

$hpMax = 50 + $newStr * 5;
$ausdauerMax = 50 + $newDex * 5;
$manaMax = 50 + $newInt * 5;
$newLevel = $oldLevel + 1;

// Datenbank einitragen und alle wert füllen.
$updateSql = "UPDATE charakterliste SET 
              Staerke = ?,
              Geschicklichkeit = ?,
              Intelligenz = ?,
              HPmax = ?, HP = ?,
              Ausdauermax = ?, Ausdauer = ?,
              Manamax = ?, Mana = ?,
              Level = ?
              WHERE AnwenderID = ?";

$updateStmt = mysqli_prepare($con, $updateSql);
mysqli_stmt_bind_param($updateStmt, "iiiiiiiiiii", 
    $newStr, $newDex, $newInt, 
    $hpMax, $hpMax, $ausdauerMax, $ausdauerMax, $manaMax, $manaMax, 
    $newLevel, 
    $id
);              

if (mysqli_stmt_execute($updateStmt)) {
    // Aufräumen & Weiterleiten
    unset($_SESSION['levelup']); // Level Up Screen ausblenden
    unset($_SESSION['Kampf']);   // Kampfdaten löschen 
    
    // Neue Spielerdaten in die Session laden
    loadplayer($con, $id);  
    
    $_SESSION['view'] = 'hub';   // Zurück zum Hub
    header("Location: maingame.php");
    exit;
} else {
    // DB Fehler
    $_SESSION['levelup_error'] = "Datenbankfehler! ";
    header("Location: maingame.php");
    exit;
}
?>