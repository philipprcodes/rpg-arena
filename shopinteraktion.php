<pre>
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once('dbconfig.php');
include_once("funktionen.php");

$meldung = "";

// Fehlermeldung Check??
if (!isset($_SESSION['Inventar'])) $_SESSION['Inventar'] = [];
if (!isset($_SESSION['Shopinventar'])) $_SESSION['Shopinventar'] = [];

// Gold laden
$spielergold = $_SESSION['Inventar']['*Gold']['Anzahl'];
$shopgold = $_SESSION['Shopinventar']['*Gold']['Anzahl'];

$Itemname = $_POST['itemname'];
$aktion = $_POST['aktion'];

$Itemzwischen = array();

// === KAUFEN (Vom Shop zum Spieler) ===
if($aktion == 'kaufen'){
    
    if (isset($_SESSION['Shopinventar'][$Itemname])) {
        $Itemzwischen = $_SESSION['Shopinventar'][$Itemname];
        
        if ($Itemzwischen['Gold'] <= $spielergold){
            
            // 1. Aus dem Shop entfernen
            if ($_SESSION['Shopinventar'][$Itemname]['Anzahl'] > 1){
                $_SESSION['Shopinventar'][$Itemname]['Anzahl']--;
            } else {
                unset($_SESSION['Shopinventar'][$Itemname]);
            }

            // 2. Dem Spieler geben
            if(array_key_exists($Itemname, $_SESSION['Inventar'])){
                // Spieler hat es schon -> Anzahl erhöhen
                $_SESSION['Inventar'][$Itemname]['Anzahl']++;
            } else {
                // Spieler hat es noch nicht -> Neu anlegen
                $_SESSION['Inventar'][$Itemname] = $Itemzwischen;
                
                // WICHTIG: Die Anzahl auf 1 setzen, sonst übernimmt er die Shop-Menge!
                $_SESSION['Inventar'][$Itemname]['Anzahl'] = 1; 
            }
            
            // Gold verrechnen
            $spielergold -= $Itemzwischen['Gold'];
            $shopgold += $Itemzwischen['Gold'];
            
        } else {
            $meldung = "Nicht genügend Gold";
        }
    }
} 

// === VERKAUFEN (Vom Spieler zum Shop) ===
else { 
    
    if (isset($_SESSION['Inventar'][$Itemname])) {
        $Itemzwischen = $_SESSION['Inventar'][$Itemname];

        if ($Itemzwischen['Gold'] <= $shopgold){ // Händler braucht genug Gold (optional, aber realistisch)
            
            // 1. Vom Spieler entfernen
            if ($_SESSION['Inventar'][$Itemname]['Anzahl'] > 1){
                $_SESSION['Inventar'][$Itemname]['Anzahl']--;
            } else {
                unset($_SESSION['Inventar'][$Itemname]);
            }

            // 2. Dem Shop geben
            if(array_key_exists($Itemname, $_SESSION['Shopinventar'])){
                $_SESSION['Shopinventar'][$Itemname]['Anzahl']++;
            } else {
                $_SESSION['Shopinventar'][$Itemname] = $Itemzwischen;
                
                // WICHTIG: Auch hier Anzahl auf 1 setzen!
                $_SESSION['Shopinventar'][$Itemname]['Anzahl'] = 1;
            }
            
            // Gold verrechnen
            $shopgold -= $Itemzwischen['Gold']; // Händler zahlt
            $spielergold += $Itemzwischen['Gold']; // Spieler kriegt Gold

        } else {
            $meldung = "Der Händler hat nicht genug Gold!";
        }
    }
}

// Gold zurückspeichern

$_SESSION['Shopinventar']['*Gold']['Anzahl'] = $shopgold;
$_SESSION['Inventar']['*Gold']['Anzahl'] = $spielergold;

header("Location: maingame.php");
exit;
?>
</pre>