<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once('dbconfig.php');
include_once("funktionen.php");

// Test Test Fehlermeldung weg?
if (!isset($_SESSION['Kampf']['loot'])) {
    $_SESSION['Kampf']['loot'] = []; 
}

$gegnerListe = $_SESSION['Kampf']['gegner'];
$gesamterLoot = array();  
    
// =======================================================================
// Prepared Statement VOR der Schleife vorbereiten
// Schnelle Mehrfachabfrage
// =======================================================================

$sql = "SELECT WaffenID, GegenstandsID, Wahrscheinlichkeit FROM loottable WHERE MonsterID = ?";
$stmt = mysqli_prepare($con, $sql);

foreach ($gegnerListe as $monster) {
    $ID = $monster['MonsterID']; 
    
    mysqli_stmt_bind_param($stmt, "i", $ID);
    mysqli_stmt_execute($stmt);
    $abfrage = mysqli_stmt_get_result($stmt);

    while ($wert = mysqli_fetch_assoc($abfrage)){
        
        $wurf = rand(1, 100);
        $chance = $wert['Wahrscheinlichkeit'];

        if ($wurf <= $chance) {
            
            $WaffenID = isset($wert['WaffenID']) ? $wert['WaffenID'] : 0;
            $GegenstandsID = $wert['GegenstandsID'];

            // Werte über Funktion holen
            $Item = Lootwerte($con, $WaffenID, $GegenstandsID);
            
            // Sicherheits-Check: Nur ins Array schieben, wenn es kein Fehler war
            if ($Item) {
                $gesamterLoot[] = $Item;
            }
        }
    }
}

// =======================================================================
// Gold Generierung
// =======================================================================
// Fallback
$stage = isset($_SESSION['stage_level']) ? $_SESSION['stage_level'] : 1;
$goldMenge = 50 * $stage;

$goldItem = array(
    'GegenstandsID' => 999,      // ID 999 für Gold
    'WaffenID' => 0,
    'Name' => '*Gold', 
    'Anzahl' => $goldMenge,     
    'Gegenstandsart' => 'Währung',
    'GOLD' => 0
);
$gesamterLoot[] = $goldItem;

// Loot in Session speichern
$_SESSION['Kampf']['loot'] = $gesamterLoot;
?>