<pre>
<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once('dbconfig.php');
include_once("funktionen.php");

// ---------------------------------------------------------
// Levelrange für Stage und Abfrage
// ---------------------------------------------------------

$level=$_SESSION['stage_level'];
if ($level<=3) {$minlevel=1;}
elseif ($level<=6) {$minlevel=4;}
elseif ($level<=9) {$minlevel=7;}
elseif ($level<=12) {$minlevel=10;}
else {$minlevel=1;}
$shopinventar=array();
$gold = 500*$level;    
    $shopinventar['*Gold'] = [
                    'Name' => "*Gold",
                    'WaffenID' => 0,
                    'GegenstandsID' => 999,
                    'SReq' => 0,
                    'GReq' => 0,
                    'IReq' => 0,
                    'Gegenstandsart' => "Waehrung",
                    'Staerke' => 0,
                    'Geschicklichkeit' => 0,
                    'Intelligenz' => 0,
                    'HP' => 0,
                    'Ausdauer' => 0,
                    'Mana' => 0,
                    'Gold' => 0,
                    'Anzahl' => $gold ,
                    'Attacken' => 0,
    ];
$sql="SELECT DISTINCT
                loottable.WaffenID, loottable.GegenstandsID, SReq, GReq, IReq, Gegenstandsart, Staerke, Geschicklichkeit, Intelligenz, HP, Ausdauer, Mana,               
                CASE 
                    WHEN loottable.WaffenID > 0 THEN waffen.Name 
                    WHEN loottable.GegenstandsID > 0 THEN gegenstaende.Name
                END AS Name,
                CASE 
                    WHEN loottable.WaffenID > 0 THEN waffen.Gold 
                    WHEN loottable.GegenstandsID > 0 THEN gegenstaende.Gold
                END AS Gold
            FROM loottable
            LEFT JOIN waffen ON loottable.WaffenID = waffen.WaffenID
            LEFT JOIN gegenstaende ON loottable.GegenstandsID = gegenstaende.GegenstandsID
            WHERE Level BETWEEN ? AND ?
            ORDER BY Name ASC";
    $stmt = mysqli_prepare($con, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $minlevel, $level);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

// Wweites Statement für Attacken VORBEREITEN 
$sql2 = "SELECT Name, MinSchaden, MaxSchaden, Ausdauerkosten, Manakosten, AoE, Taunt, Status, Dauer, Execute 
         FROM attacken
         INNER JOIN attackenzuweisung ON attacken.AttackenID = attackenzuweisung.AttackenID
         WHERE attackenzuweisung.WaffenID = ?";
    $stmtAttacken = mysqli_prepare($con, $sql2);    

        while ($wert = mysqli_fetch_assoc($result)){
        //Index bekommt namen und anzahl auf 5 erstmal
        $itemName = $wert['Name'];
        $shopinventar[$itemName] = $wert;
        $shopinventar[$itemName]['Anzahl'] = 5;
        // ---------------------------------------------------------
        // NUR WENN ES EINE WAFFE IST: ATTACKEN LADEN und Requirements setzen
        // ---------------------------------------------------------
        $attackenliste = array();
        if ($wert['WaffenID'] > 0) {
            $WaffenID = $wert['WaffenID'];
            $neueArt = ReqCheck($wert['SReq'], $wert['GReq'], $wert['IReq']);
            $shopinventar[$itemName]['Gegenstandsart'] = $neueArt;
            
            mysqli_stmt_bind_param($stmtAttacken, "i", $waffenID);
            mysqli_stmt_execute($stmtAttacken);
            $resAttacken = mysqli_stmt_get_result($stmtAttacken);
            
            while ($attacken = mysqli_fetch_assoc($resAttacken)) {
                $attackenliste[] = $attacken;
            }
        }
        $shopinventar[$itemName]['Attacken']=$attackenliste;
        }
    
    
    $_SESSION['Shopinventar']=$shopinventar;

// print_r($shopinventar);

?>
</pre>