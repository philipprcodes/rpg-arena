<pre>
<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ------------------------------------------------------------------
// Eingeloggt Check
// ------------------------------------------------------------------
if (!isset($_SESSION["ID"])) {
    header("Location: login.php");
    exit;
}
// Datenbankverbindung laden
require_once('dbconfig.php');

// Altes Kampf-Array löschen
if(isset($_SESSION['Kampf'])) {
    unset($_SESSION['Kampf']);
}

// ------------------------------------------------------------------
// 1. SPIELER DATEN (Direkt aus Session)
// ------------------------------------------------------------------
$SpielerWerte = $_SESSION['Spieler']; 
$SpielerWerte['taunt'] = false; // Taunt Status resetten
$SpielerWerte['freeze']      = false;
$SpielerWerte['freezecount']  = 0;
$SpielerWerte['burn']        = false;
$SpielerWerte['burncount']    = 0;
$SpielerWerte['bleed']       = false;
$SpielerWerte['bleedcount']   = 0;
$SpielerWerte['para']        = false;
$SpielerWerte['paracount']    = 0;
$SpielerWerte['stun']        = false;
$SpielerWerte['stuncount']    = 0;
$SpielerWerte['poison']      = false;
$SpielerWerte['poisoncount']  = 0;

// Attacken aus der ausgerüsteten Waffe (Slot 4) holen
// Falls Slot 4 leer ist, leeres Array nutzen
if (isset($_SESSION['Ausruestung'][4]['Attacken'])) {
    $spielerAttacken = $_SESSION['Ausruestung'][4]['Attacken'];
} else {
    $spielerAttacken = [];
}

// ------------------------------------------------------------------
// 2. SCHATTENMONSTER DATEN (Direkt aus Session)
// ------------------------------------------------------------------
$schattenmonster = [];
if (isset($_SESSION['Schattenmonster']) && !empty($_SESSION['Schattenmonster'])) {
    $schattenmonster = $_SESSION['Schattenmonster'];
    $schattenmonster['freeze']      = false;
    $schattenmonster['freezecount']  = 0;
    $schattenmonster['burn']        = false;
    $schattenmonster['burncount']    = 0;
    $schattenmonster['bleed']       = false;
    $schattenmonster['bleedcount']   = 0;
    $schattenmonster['para']        = false;
    $schattenmonster['paracount']    = 0;
    $schattenmonster['stun']        = false;
    $schattenmonster['stuncount']    = 0;
    $schattenmonster['poison']      = false;
    $schattenmonster['poisoncount']  = 0;
    
}

// ------------------------------------------------------------------
// 3. GEGNER GENERIEREN (Gameloop Logik)
// ------------------------------------------------------------------
$gegnerliste = array();
$wins = $_SESSION['stage_wins'] ?? 0; // Aktueller Fortschritt holen
$stage = $_SESSION['stage_level'] ?? 1; // Gesamter Durchgang (1, 2, 3...)

// --- LOGIK FÜR GEGNER-AUSWAHL ---
$monsterIDs = [];

// Stage 1 sind die Kämpfe fix
if ($stage == 1 && $wins == 0) {
    // KAMPF 1 (Tutorial): 2 Riesenratten (ID 11)
    $monsterIDs = [11]; 
} 
elseif ($stage == 1 && $wins == 1) {
    // KAMPF 2 (Tutorial): 1 Wolf (ID 12) + 1 Ratte (ID 11)
    $monsterIDs = [12];
} 
elseif ($stage == 1 && $wins == 2) {
    // KAMPF 2 (Tutorial): 1 Wolf (ID 12) + 1 Ratte (ID 11)
    $monsterIDs = [12, 11];
} 
elseif ($stage == 1 && $wins == 3) {
    // KAMPF 2 (Tutorial): 1 Wolf (ID 12) + 1 Ratte (ID 11)
    $monsterIDs = [11, 12, 11];
} 
elseif ($stage == 1 && $wins == 4) {
    // KAMPF 2 (Tutorial): 1 Wolf (ID 12) + 1 Ratte (ID 11)
    $monsterIDs = [14];
} 

// Boss Gegner generierung
elseif ($stage == 3 && $wins == 4) {
    $monsterIDs = [19, 901, 19];
} 
elseif ($stage == 6 && $wins == 4) {
    $monsterIDs = [27, 902, 27];
}
elseif ($stage == 9 && $wins == 4) {
    $monsterIDs = [31, 903, 31];
}
elseif ($stage == 12 && $wins == 4) {
    $monsterIDs = [42, 904, 42];
}
elseif ($stage == 13) {
    $monsterIDs = [1001, 1002, 1003];
}
else {
    // Dynamische Gegner generierung!  
        if ($stage <= 3) {$minLevel = 1;}
        elseif ($stage <= 6) {$minLevel = 4;} 
        elseif ($stage <= 9) {$minLevel = 7;}
        elseif ($stage <= 12) {$minLevel = 10;}
        else {$minLevel=1;}

    if ($stage<=3) {$anzahl=2;}
    else {$anzahl = rand(2, 3);}
    
    
        // Zufälliges Monster holen
        // Wir holen hier nur die ID, Details laden wir unten
        $sqlRnd = "SELECT MonsterID FROM monster WHERE MonsterLevel BETWEEN ? AND ? ORDER BY RAND() LIMIT 1";
        $stmtRnd = mysqli_prepare($con, $sqlRnd);
    
        for($k=0; $k < $anzahl; $k++) {
            mysqli_stmt_bind_param($stmtRnd, "ii", $minLevel, $stage);
            mysqli_stmt_execute($stmtRnd);
            $resRnd = mysqli_stmt_get_result($stmtRnd);
            if ($rowRnd = mysqli_fetch_assoc($resRnd)) {
                $monsterIDs[] = $rowRnd['MonsterID'];
            }
        }
    }

// --- DATENBANK LADEN FÜR DIE FESTGELEGTEN IDs ---
// Profi-Upgrade: Auch hier nutzen wir Prepared Statements für den Loop!
$sqlMonster = "SELECT `MonsterID`, `Name`, `HP`, `Initiativwert`,`Immunity`, `Attacke1`, `Attacke2`, `XP`, `MonsterLevel` , `Bild`
               FROM `monster` WHERE `MonsterID`= ? LIMIT 1";
$stmtMonster = mysqli_prepare($con, $sqlMonster);

$sqlAttack = "SELECT A1.Name AS Attacke1_Name, A1.MinSchaden AS A1_MinSchaden, A1.MaxSchaden AS A1_MaxSchaden, A1.AoE AS A1_AoE, A1.Taunt AS A1_Taunt, A1.Status AS A1_Status, A1.Dauer AS A1_Dauer, A1.Execute AS A1_Execute,
                     A2.Name AS Attacke2_Name, A2.MinSchaden AS A2_MinSchaden, A2.MaxSchaden AS A2_MaxSchaden, A2.AoE AS A2_AoE, A2.Taunt AS A2_Taunt, A2.Status AS A2_Status, A2.Dauer AS A2_Dauer, A2.Execute AS A2_Execute 
              FROM monster 
              LEFT JOIN attacken AS A1 ON monster.Attacke1 = A1.AttackenID 
              LEFT JOIN attacken AS A2 ON monster.Attacke2 = A2.AttackenID 
              WHERE MonsterID = ?";
$stmtAttack = mysqli_prepare($con, $sqlAttack);

foreach ($monsterIDs as $mID) {
    // Monster Daten holen
    mysqli_stmt_bind_param($stmtMonster, "i", $mID);
    mysqli_stmt_execute($stmtMonster);
    $resMonster = mysqli_stmt_get_result($stmtMonster);
    
    if ($monster = mysqli_fetch_assoc($resMonster)) { 
        // Attacken des Monsters laden
        mysqli_stmt_bind_param($stmtAttack, "i", $monster['MonsterID']);
        mysqli_stmt_execute($stmtAttack);
        $resAttack = mysqli_stmt_get_result($stmtAttack);
        $attacke = mysqli_fetch_assoc($resAttack);

        $gegnerliste[] = [
            'ID'  => uniqid(), 
            'MonsterID' => $monster['MonsterID'],
            'Name' => $monster['Name'],
            'HP' => $monster['HP'],
            'HPmax' => $monster['HP'],
            'Initiativwert' => $monster['Initiativwert'],
            'Immunity' => $monster['Immunity'],
            'XP' => $monster['XP'],
            'Bild' => $monster['Bild'],
            'MonsterLevel' => $monster['MonsterLevel'],

            'Attacke1_Name' => $attacke['Attacke1_Name'],
            'A1_MinSchaden' => $attacke['A1_MinSchaden'],
            'A1_MaxSchaden' => $attacke['A1_MaxSchaden'],
            'A1_AoE' => $attacke['A1_AoE'],
            'A1_Taunt' => $attacke['A1_Taunt'],
            'A1_Status' => $attacke['A1_Status'],
            'A1_Dauer' => $attacke['A1_Dauer'],
            'A1_Execute' => $attacke['A1_Execute'],

            'Attacke2_Name' => $attacke['Attacke2_Name'],
            'A2_MinSchaden' => $attacke['A2_MinSchaden'],
            'A2_MaxSchaden' => $attacke['A2_MaxSchaden'],
            'A2_AoE' => $attacke['A2_AoE'],
            'A2_Taunt' => $attacke['A2_Taunt'],
            'A2_Status' => $attacke['A2_Status'],
            'A2_Dauer' => $attacke['A2_Dauer'],
            'A2_Execute' => $attacke['A2_Execute'],
            
            'freeze' => false,
            'freezecount' => 0,
            'burn' => false,
            'burncount' => 0,
            'bleed' => false,
            'bleedcount' => 0,
            'para' => false,
            'paracount' => 0,
            'stun' => false,
            'stuncount' => 0,
            'poison' => false,
            'poisoncount' => 0,
            'tot' => false
        ];
    }
}
// ------------------------------------------------------------------
// 4. REIHENFOLGE BERECHNEN
// ------------------------------------------------------------------
$reihenfolge = array();

// Spieler Initiative
$spielerini = rand (1,100) + $SpielerWerte['Geschicklichkeit'];
$reihenfolge[]=[
    'typ'   => 'spieler', 
    'id'    => 'spieler', 
    'ini'   => $spielerini,
    'name'  => $SpielerWerte['Name']
];

// Schattenmonster Initiative (NUR WENN EINS DABEI IST!)
if (!empty($schattenmonster)) {
    $schattenini = rand (1,20) + $schattenmonster['Initiativwert'];
    $reihenfolge[]=[
        'typ'   => 'schatten', 
        'id'    => 'schatten', 
        'ini'   => $schattenini,
        'name'  => $schattenmonster['Name']
    ];
}

// Gegner Initiative
foreach ($gegnerliste as $index => $monster) {
    $monsterini = rand(1, 20) + $monster['Initiativwert'];
    $reihenfolge[] = [
        'typ' => 'gegner',
        'id' => $index, 
        'ini' => $monsterini,
        'name' => $monster['Name']
    ];
}

// Sortieren (Höchste Initiative zuerst)
usort($reihenfolge, function ($a, $b) {
    return $b['ini'] <=> $a['ini'];
});

// ------------------------------------------------------------------
// 5. SESSION SPEICHERN
// ------------------------------------------------------------------
$_SESSION['Kampf'] = [
    'aktiv' => true,
    'runde' => 1,
    'log' => array("Kampf gestartet!"),
    'zug_index' => 0,
    'gewonnen' => false,
    'verloren' => false,
    'spieler' => $SpielerWerte, // Hier nutzen wir die Variable von oben
    'attacken'   => $spielerAttacken, // Und hier die Attacken Variable
    'schattenmonster' => $schattenmonster,
    'gegner' => $gegnerliste,
    'reihenfolge' => $reihenfolge,
    'fangmeldung' => [] // Initialisieren für später
];

header("Location: maingame.php");
exit;
?>
</pre>