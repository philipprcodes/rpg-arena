<?php

// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Charakterwerte auslesen
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

function Charakterwerte($con, $userID){
    $sql = "SELECT * FROM charakterliste WHERE AnwenderID = ?";
    $stmt = mysqli_prepare($con, $sql);
    mysqli_stmt_bind_param($stmt, "i", $userID);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result) {
        return mysqli_fetch_assoc($result);
    } else {
        return false;
    }
}
 
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Start Loot
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

function starterPaket($con, $CharakterID) {
    
    $sql_equip = "INSERT INTO charakterausruestung (CharakterID, WaffenID, GegenstandsID, Platz) VALUES 
                  (?, 101, 0, 4),
                  (?, 0, 5, 1),
                  (?, 0, 1001, 2)";
    $stmt_equip = mysqli_prepare($con, $sql_equip);
    mysqli_stmt_bind_param($stmt_equip, "iii", $CharakterID, $CharakterID, $CharakterID);
    mysqli_stmt_execute($stmt_equip);


    $sql_inv = "INSERT INTO charakterinventar (CharakterID, GegenstandsID, Anzahl) VALUES 
                (?, 1, 3),
                (?, 999, 100)";
    $stmt_inv = mysqli_prepare($con, $sql_inv);
    mysqli_stmt_bind_param($stmt_inv, "ii", $CharakterID, $CharakterID);
    mysqli_stmt_execute($stmt_inv);
}

// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Stats Update bei Ausrüstungswechsel
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

function statsupdate($con, $CharID){
    $sql = "SELECT Staerke, Geschicklichkeit, Intelligenz, HPmax, Manamax, Ausdauermax 
            FROM charakterliste 
            WHERE CharakterID = ? LIMIT 1";
    
    $stmt = mysqli_prepare($con, $sql);
    mysqli_stmt_bind_param($stmt, "i", $CharID);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);


    if ($baseStats = mysqli_fetch_assoc($result)) {
        
        // Basiswerte als Start
        $neuStr = $baseStats['Staerke'];
        $neuDex = $baseStats['Geschicklichkeit'];
        $neuInt = $baseStats['Intelligenz'];
        
        
        $neuHPMax = $baseStats['HPmax'];
        $neuManaMax = $baseStats['Manamax'];
        $neuAusdauerMax = $baseStats['Ausdauermax'];

        // Ausrüstung checken
        if (isset($_SESSION['Ausruestung'])) {
            foreach ($_SESSION['Ausruestung'] as $slot => $item) {
                // Check ob Item vorhanden?
                if (isset($item['IstLeer']) && $item['IstLeer'] == false) {
                    
                    // Addieren (mit Check, falls das Item den Stat gar nicht hat)
                    $neuStr += isset($item['Staerke']) ? $item['Staerke'] : 0;
                    $neuDex += isset($item['Geschicklichkeit']) ? $item['Geschicklichkeit'] : 0;
                    $neuInt += isset($item['Intelligenz']) ? $item['Intelligenz'] : 0;
                    
                    // Neue Werte berechnen an Hand neuer Stats
                    $neuHPMax = 50 + $neuStr * 5;
                    $neuManaMax = 50 + $neuInt * 5;
                    $neuAusdauerMax = 50 + $neuDex * 5;
                }
            }
        }
        // Werte in die Session schreiben
        $_SESSION['Spieler']['Staerke'] = $neuStr;
        $_SESSION['Spieler']['Geschicklichkeit'] = $neuDex;
        $_SESSION['Spieler']['Intelligenz'] = $neuInt;
        
        $_SESSION['Spieler']['HPmax'] = $neuHPMax;
        $_SESSION['Spieler']['Manamax'] = $neuManaMax;
        $_SESSION['Spieler']['Ausdauermax'] = $neuAusdauerMax;
        // Werte kappen falls sie höher sind als max wert beim ausziehen
        if ($_SESSION['Spieler']['HP'] > $neuHPMax) {
            $_SESSION['Spieler']['HP'] = $neuHPMax;
        }
        if ($_SESSION['Spieler']['Mana'] > $neuManaMax) {
            $_SESSION['Spieler']['Mana'] = $neuManaMax;
        }
        if ($_SESSION['Spieler']['Ausdauer'] > $neuAusdauerMax) {
            $_SESSION['Spieler']['Ausdauer'] = $neuAusdauerMax;
        }
    }



}

// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Waffen Requirements als Rückgabe
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

function ReqCheck($sreq, $greq, $ireq){
    // Prüfung auf Stärke
    if ($sreq > 0){
        return "Waffe (STR $sreq)";
    }
    // Prüfung auf Geschick
    elseif ($greq > 0){
        return "Waffe (DEX $greq)";
    }
    // Prüfung auf Intelligenz
    elseif ($ireq > 0){
        return "Waffe (INT $ireq)";
    }
    
    // WICHTIG: Fallback, wenn keine Anforderungen da sind!
    return "Waffe";
}

// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Schadensberechnung
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

function damage($min,$max){
    $crit=critcheck();
    $diff=$max-$min;
    $schaden=(rand(0,$diff)+$min)*$crit;
    return $schaden;
    exit;
}

function critcheck(){
    if (rand(1,20)==20){
        return 2;
    } else {
        return 1;
    }
}


// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Inventar laden
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

function loadinventar($con, $CharID){

        $inventar =  array();
        $finalesinventar = array();
        $sql = "SELECT inventar.Anzahl, inventar.WaffenID, inventar.GegenstandsID, SReq, GReq, IReq, Gegenstandsart, Staerke, Geschicklichkeit, Intelligenz, HP, Ausdauer, Mana,
                    CASE 
                        WHEN inventar.WaffenID > 0 THEN waffe.Name 
                        WHEN inventar.GegenstandsID > 0 THEN gegenstand.Name
                        END AS Name,
                    CASE 
                        WHEN inventar.WaffenID > 0 THEN waffe.Gold 
                        WHEN inventar.GegenstandsID > 0 THEN gegenstand.Gold
                        END AS Gold       
                FROM charakterinventar AS inventar
                LEFT JOIN waffen AS waffe ON inventar.WaffenID = waffe.WaffenID
                LEFT JOIN gegenstaende AS gegenstand ON inventar.GegenstandsID = gegenstand.GegenstandsID
                WHERE inventar.CharakterID = ?
                ORDER BY Name ASC";

        $stmt = mysqli_prepare($con, $sql);
        mysqli_stmt_bind_param($stmt, "i", $CharID);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        while ($wert = mysqli_fetch_assoc($result)) {
            
        // ---------------------------------------------------------
        // NUR WENN ES EINE WAFFE IST: ATTACKEN LADEN
        // ---------------------------------------------------------
        $attackenliste = array();
        if ($wert['WaffenID'] > 0) {
            $WaffenID = $wert['WaffenID'];
            $wert['Gegenstandsart']=ReqCheck($wert['SReq'], $wert['GReq'], $wert['IReq']);
            $sql2 = "SELECT 
                        Name, MinSchaden, MaxSchaden, Ausdauerkosten, Manakosten, AoE, Taunt, `Status`, `Dauer`, `Execute`  
                             
                        FROM attacken
                        INNER JOIN attackenzuweisung ON attacken.AttackenID = attackenzuweisung.AttackenID
                        WHERE attackenzuweisung.WaffenID = ?";
            
            $stmt2 = mysqli_prepare($con, $sql2);
            mysqli_stmt_bind_param($stmt2, "i", $WaffenID);
            mysqli_stmt_execute($stmt2);
            $result2 = mysqli_stmt_get_result($stmt2);
            
            while ($attacken = mysqli_fetch_assoc($result2)) {
                $attackenliste[] = $attacken;
            }
        }
            $inventar = [
                'Name' => $wert['Name'],
                'WaffenID' => $wert['WaffenID'],
                'GegenstandsID' => $wert['GegenstandsID'],
                'SReq' => $wert['SReq'],
                'GReq' => $wert['GReq'],
                'IReq' => $wert['IReq'],
                'Gegenstandsart' => $wert['Gegenstandsart'],
                'Staerke' => $wert['Staerke'],
                'Geschicklichkeit' => $wert['Geschicklichkeit'],
                'Intelligenz' => $wert['Intelligenz'],
                'HP' => $wert['HP'],
                'Ausdauer' => $wert['Ausdauer'],
                'Mana' => $wert['Mana'],
                'Gold' => $wert['Gold'],
                'Anzahl' => $wert['Anzahl'],
                'Attacken' => $attackenliste
                                        
        ];
        $finalesinventar[$inventar['Name']]=$inventar;
        }
        $_SESSION['Inventar'] = $finalesinventar;
    }

// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Ausrüstung laden
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

function loadausruestung($con, $CharID){

    // Array initialisieren falls Ausrüstungsslot leer ist.
    $finaleausruestung = array();
    for($i=1; $i<=5; $i++) {
        $finaleausruestung[$i] = ['Name' => 'Leer', 'Icon' => '🛡️', 'IstLeer' => true];
    }

    
    // Infos zu Waffen, Gegenständen , Schattenmonstern
    $sql = "SELECT 
                charakterausruestung.Platz, charakterausruestung.WaffenID, charakterausruestung.GegenstandsID, charakterausruestung.ShadowID, SReq, GReq, IReq, Gegenstandsart, Staerke, Geschicklichkeit, Intelligenz, HP, Ausdauer, Mana, 
                CASE 
                    WHEN charakterausruestung.WaffenID > 0 THEN waffen.Gold 
                    WHEN charakterausruestung.GegenstandsID > 0 THEN gegenstaende.Gold
                    
                END AS Gold,
                -- Name ermitteln (Waffe, Gegenstand oder Schatten)
                CASE 
                    WHEN charakterausruestung.WaffenID > 0 THEN waffen.Name 
                    WHEN charakterausruestung.GegenstandsID > 0 THEN gegenstaende.Name
                    WHEN charakterausruestung.ShadowID > 0 THEN charakterschatten.Name
                END AS Name
            FROM charakterausruestung
            LEFT JOIN waffen ON charakterausruestung.WaffenID = waffen.WaffenID
            LEFT JOIN gegenstaende ON charakterausruestung.GegenstandsID = gegenstaende.GegenstandsID
            LEFT JOIN charakterschatten ON charakterausruestung.ShadowID = charakterschatten.ShadowID
            WHERE charakterausruestung.CharakterID = ?
            ORDER BY charakterausruestung.Platz ASC";

    $stmt = mysqli_prepare($con, $sql);
    mysqli_stmt_bind_param($stmt, "i", $CharID);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    while ($wert = mysqli_fetch_assoc($result)) {
        
        $platz = $wert['Platz']; // 1 bis 5
        $attackenliste = array(); // Leere Liste für Attacken

        // ---------------------------------------------------------
        // A) WENN ES EINE WAFFE IST: ATTACKEN LADEN
        // ---------------------------------------------------------
        if ($wert['WaffenID'] > 0) {
            $WaffenID = $wert['WaffenID'];
            $wert['Gegenstandsart']=ReqCheck($wert['SReq'], $wert['GReq'], $wert['IReq']);
            $sql2 = "SELECT 
                            attacken.Name, MinSchaden, MaxSchaden, Ausdauerkosten, Manakosten, AoE, Taunt, `Status`, `Dauer`, `Execute`  
                        FROM attacken
                        INNER JOIN attackenzuweisung ON attacken.AttackenID = attackenzuweisung.AttackenID
                        WHERE attackenzuweisung.WaffenID = ?";
            
            $stmt2 = mysqli_prepare($con, $sql2);
            mysqli_stmt_bind_param($stmt2, "i", $WaffenID);
            mysqli_stmt_execute($stmt2);
            $result2 = mysqli_stmt_get_result($stmt2);

            while ($attacken = mysqli_fetch_assoc($result2)) {
                $attackenliste[] = $attacken;
            }
        }
        $finaleausruestung[$platz] = [
            'IstLeer' => false, // Markieren, dass hier was drin ist
            'Name' => $wert['Name'],
            'WaffenID' => $wert['WaffenID'],
            'GegenstandsID' => $wert['GegenstandsID'],
            'ShadowID' => $wert['ShadowID'],
            'SReq' => $wert['SReq'],
            'GReq' => $wert['GReq'],
            'IReq' => $wert['IReq'],
            'Gegenstandsart' => $wert['Gegenstandsart'],
            'Staerke' => $wert['Staerke'],
            'Geschicklichkeit' => $wert['Geschicklichkeit'],
            'Intelligenz' => $wert['Intelligenz'],
            'HP' => $wert['HP'],
            'Ausdauer' => $wert['Ausdauer'],
            'Mana' => $wert['Mana'],
            'Anzahl' => 1,
            'Gold' => $wert['Gold'],
            'Attacken' => $attackenliste,
        ];
    }
    
    // 3. In Session speichern
    $_SESSION['Ausruestung'] = $finaleausruestung;
}
    

// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Lootwerte laden für After Fight Loot Generierung
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

function Lootwerte($con, $WaffenID, $GegenstandsID) {
    
    // Leeres aray erstmal zur sicherheit
    $item = [
        'Name' => 'Unbekannt',
        'WaffenID' => 0,
        'GegenstandsID' => 0,
        'SReq' => 0, 
        'GReq' => 0, 
        'IReq' => 0,
        'Gegenstandsart' => '',
        'Staerke' => 0, 
        'Geschicklichkeit' => 0, 
        'Intelligenz' => 0,
        'HP' => 0, 
        'Ausdauer' => 0, 
        'Mana' => 0,
        'Gold' => 0,
        'Anzahl' => 1,
        'Attacken' => []
    ];

    // -----------------------------------------------------
    // WAFFE
    // -----------------------------------------------------
    if ($WaffenID > 0) {
        $sql = "SELECT * FROM waffen WHERE WaffenID = ?";
        $stmt = mysqli_prepare($con, $sql);
        mysqli_stmt_bind_param($stmt, "i", $WaffenID);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $wert = mysqli_fetch_assoc($result);


        if ($wert) {
            $reqString = ReqCheck($wert['SReq'], $wert['GReq'], $wert['IReq']);
            $item['Gegenstandsart'] = "Waffe " . $reqString;

            // $item['Gegenstandsart'] = $wert['Gegenstandsart'];

            $item['Name'] = $wert['Name'];
            $item['WaffenID'] = $wert['WaffenID'];
            $item['SReq'] = $wert['SReq'];
            $item['GReq'] = $wert['GReq'];
            $item['IReq'] = $wert['IReq'];
            $item['Gold'] = $wert['Gold'];
            
            // Attacken laden
            $sql2 = "SELECT attacken.Name, MinSchaden, MaxSchaden, Ausdauerkosten, Manakosten, AoE, Taunt, `Status`, `Dauer`, `Execute`
                    FROM attacken
                    INNER JOIN attackenzuweisung ON attacken.AttackenID = attackenzuweisung.AttackenID
                    WHERE attackenzuweisung.WaffenID = ?";
            $stmt2 = mysqli_prepare($con, $sql2);
            mysqli_stmt_bind_param($stmt2, "i", $WaffenID);
            mysqli_stmt_execute($stmt2);
            $result2 = mysqli_stmt_get_result($stmt2);
            
            while ($attacken = mysqli_fetch_assoc($result2)) {
                $item['Attacken'][] = $attacken;
            }
        }
    }

    // -----------------------------------------------------
    // GEGENSTAND
    // -----------------------------------------------------
    elseif ($GegenstandsID > 0) {
        $sql = "SELECT * FROM gegenstaende WHERE GegenstandsID = ?";
        $stmt = mysqli_prepare($con, $sql);
        mysqli_stmt_bind_param($stmt, "i", $GegenstandsID);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $wert = mysqli_fetch_assoc($result);

        if ($wert) {
            $item['Name'] = $wert['Name'];
            $item['GegenstandsID'] = $wert['GegenstandsID'];
            $item['Gegenstandsart'] = $wert['Gegenstandsart'];
            $item['Staerke'] = $wert['Staerke'];
            $item['Geschicklichkeit'] = $wert['Geschicklichkeit'];
            $item['Intelligenz'] = $wert['Intelligenz'];
            $item['HP'] = $wert['HP'];
            $item['Ausdauer'] = $wert['Ausdauer'];
            $item['Mana'] = $wert['Mana'];
            $item['Gold'] = $wert['Gold'];
        }
    }

    return $item;
}

// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Spielerwerte laden
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

function loadplayer($con, $UserID){
$SpielerWerte = Charakterwerte($con, $UserID);
$SpielerWerte['taunt'] = false;
$sql="SELECT attacken.Name as AttackenName, `MinSchaden`, `MaxSchaden`, `Ausdauerkosten`, `Manakosten`, `AoE`, `Taunt`, `Status`, `Dauer`, `Execute` 
      FROM `attacken` 
      INNER JOIN attackenzuweisung on attacken.AttackenID=attackenzuweisung.AttackenID 
      INNER JOIN waffen on attackenzuweisung.WaffenID=waffen.WaffenID 
      INNER JOIN charakterausruestung on waffen.WaffenID=charakterausruestung.WaffenID 
      INNER JOIN charakterliste on charakterausruestung.CharakterID=charakterliste.CharakterID 
      WHERE AnwenderID= ?";
    $stmt = mysqli_prepare($con, $sql);
    mysqli_stmt_bind_param($stmt, "i", $UserID);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $spielerattacken = array();
    while ($attacke = mysqli_fetch_assoc($result)){
        $spielerattacken[] = $attacke;
    }
$_SESSION['Spieler'] = $SpielerWerte;
// ---------------------------------------------
// Für leichtere Abfrage direkt in Session
$_SESSION['stage_level'] = $SpielerWerte['StageLevel'];
$_SESSION['stage_wins']  = $SpielerWerte['StageWins'];
}

// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Schattenmonster laden
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

function loadshadow($con, $UserID){

    $schattenmonster = array();

    $sql="SELECT AusruestungsID from charakterausruestung
                            INNER JOIN charakterliste on charakterausruestung.CharakterID=charakterliste.CharakterID
                            where AnwenderID = ? AND Platz=5";
    $stmt = mysqli_prepare($con, $sql);
    mysqli_stmt_bind_param($stmt, "i", $UserID);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $abfragearray = array();
    while($wert = mysqli_fetch_assoc($result)){ 
        $abfragearray[] = $wert["AusruestungsID"];
        };
    
    // -------------------------------------------------------------
    // Wenn Array leer abbrechen
    // -------------------------------------------------------------
    if (empty($abfragearray)) {
        // Kein Schatten ausgerüstet -> Session leer machen oder null setzen
        $_SESSION['Schattenmonster'] = null; 
        return; // Funktion beenden
    }

    $AusruestungsID = $abfragearray[0];

    $sql="SELECT  charakterausruestung.ShadowID as ShadowID,  schattenmonster.SchattenID as ID , charakterschatten.Name as Name , `HP`, `Initiativwert`, 
                `Attack1`, `Attack2`, `Attack3`, schattenmonster.Bild as Bild 
                FROM `monster`
                INNER JOIN schattenmonster on schattenmonster.MonsterID=monster.MonsterID
                INNER JOIN charakterschatten on schattenmonster.SchattenID=charakterschatten.SchattenID
                INNER JOIN charakterausruestung on charakterschatten.ShadowID=charakterausruestung.ShadowID
                where AusruestungsID = ?";
    $stmt = mysqli_prepare($con, $sql);
    mysqli_stmt_bind_param($stmt, "i", $AusruestungsID);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    while($schatten = mysqli_fetch_assoc($result)){ 
            $sql2="SELECT   A1.Name         AS Attacke1_Name, 
                            A1.MinSchaden   AS A1_MinSchaden, 
                            A1.MaxSchaden   AS A1_MaxSchaden, 
                            A1.AoE          AS A1_AoE, 
                            A1.Taunt        AS A1_Taunt,
                            A1.Status       AS A1_Status,
                            A1.Dauer        AS A1_Dauer,
                            A1.Execute      AS A1_Execute, 
                            A2.Name         AS Attacke2_Name,
                            A2.MinSchaden   AS A2_MinSchaden,
                            A2.MaxSchaden   AS A2_MaxSchaden, 
                            A2.AoE          AS A2_AoE, 
                            A2.Taunt        AS A2_Taunt,
                            A2.Status       AS A2_Status,
                            A2.Dauer        AS A2_Dauer,
                            A2.Execute      AS A2_Execute, 
                            A3.Name         AS Attacke3_Name,
                            A3.MinSchaden   AS A3_MinSchaden,
                            A3.MaxSchaden   AS A3_MaxSchaden, 
                            A3.AoE          AS A3_AoE, 
                            A3.Taunt        AS A3_Taunt, 
                            A3.Status       AS A3_Status,
                            A3.Dauer        AS A3_Dauer,
                            A3.Execute      AS A3_Execute
                        FROM schattenmonster
                        LEFT JOIN attacken AS A1 ON schattenmonster.Attack1 = A1.AttackenID 
                        LEFT JOIN attacken AS A2 ON schattenmonster.Attack2 = A2.AttackenID
                        LEFT JOIN attacken AS A3 ON schattenmonster.Attack3 = A3.AttackenID 
                        WHERE SchattenID = ?" ;
            
            $stmt2 = mysqli_prepare($con, $sql2);
            mysqli_stmt_bind_param($stmt2, "i", $schatten['ID']);
            mysqli_stmt_execute($stmt2);
            $result2 = mysqli_stmt_get_result($stmt2);
            $attacke = mysqli_fetch_assoc($result2);
            // print_r($attacke);
            $schattenmonster = [
                            'ShadowID' => $schatten['ShadowID'],
                            'SchattenID' => $schatten['ID'],
                            'Name' => $schatten['Name'],
                            'Bild' => $schatten['Bild'],
                            'HP' => $schatten['HP'],
                            'HPmax' => $schatten['HP'],
                            'Initiativwert' => $schatten['Initiativwert'],
                            'Attacke1_Name' => $attacke['Attacke1_Name'],
                            'A1_MinSchaden' => $attacke['A1_MinSchaden'],
                            'A1_MaxSchaden' => $attacke['A1_MaxSchaden'],
                            'A1_AoE' => $attacke['A1_AoE'],
                            'A1_Taunt' => $attacke['A1_Taunt'],
                            'A1.Status' => $attacke['A1_Status'],
                            'A1.Dauer' => $attacke['A1_Dauer'],
                            'A1.Execute' => $attacke['A1_Execute'], 
                            'Attacke2_Name' => $attacke['Attacke2_Name'],
                            'A2_MinSchaden' => $attacke['A2_MinSchaden'],
                            'A2_MaxSchaden' => $attacke['A2_MaxSchaden'],
                            'A2_AoE' => $attacke['A2_AoE'],
                            'A2_Taunt' => $attacke['A2_Taunt'],
                            'A2.Status' => $attacke['A2_Status'],
                            'A2.Dauer' => $attacke['A2_Dauer'],
                            'A2.Execute' => $attacke['A2_Execute'], 
                            'Attacke3_Name' => $attacke['Attacke3_Name'],
                            'A3_MinSchaden' => $attacke['A3_MinSchaden'],
                            'A3_MaxSchaden' => $attacke['A3_MaxSchaden'],
                            'A3_AoE' => $attacke['A3_AoE'],
                            'A3_Taunt' => $attacke['A3_Taunt'],
                            'A3.Status' => $attacke['A3_Status'],
                            'A3.Dauer' => $attacke['A3_Dauer'],
                            'A3.Execute' => $attacke['A3_Execute'], 
                            'taunt' => false,
                            'tot' => false
        ];
        }
        $_SESSION['Schattenmonster'] = $schattenmonster;
}

// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Schattendex laden
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

function loadschattendex($con, $CharakterID){

    $schattendex = array();

    $sql="SELECT  ShadowID, monster.MonsterID as MID , schattenmonster.SchattenID as ID , charakterschatten.Name as Name , `HP`, `Initiativwert`, 
                `Attack1`, `Attack2`, `Attack3` , schattenmonster.Bild
                FROM `monster`
                INNER JOIN schattenmonster on schattenmonster.MonsterID=monster.MonsterID
                INNER JOIN charakterschatten on schattenmonster.SchattenID=charakterschatten.SchattenID
                where CharakterID = ?";
    $stmt = mysqli_prepare($con, $sql);
    mysqli_stmt_bind_param($stmt, "i", $CharakterID);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while($schatten = mysqli_fetch_assoc($result)){ 
            $sql2="SELECT   A1.Name         AS Attacke1_Name, 
                            A1.MinSchaden   AS A1_MinSchaden, 
                            A1.MaxSchaden   AS A1_MaxSchaden, 
                            A1.AoE          AS A1_AoE, 
                            A1.Taunt        AS A1_Taunt,
                            A1.Status       AS A1_Status,
                            A1.Dauer        AS A1_Dauer,
                            A1.Execute      AS A1_Execute, 
                            A2.Name         AS Attacke2_Name,
                            A2.MinSchaden   AS A2_MinSchaden,
                            A2.MaxSchaden   AS A2_MaxSchaden, 
                            A2.AoE          AS A2_AoE, 
                            A2.Taunt        AS A2_Taunt,
                            A2.Status       AS A2_Status,
                            A2.Dauer        AS A2_Dauer,
                            A2.Execute      AS A2_Execute, 
                            A3.Name         AS Attacke3_Name,
                            A3.MinSchaden   AS A3_MinSchaden,
                            A3.MaxSchaden   AS A3_MaxSchaden, 
                            A3.AoE          AS A3_AoE, 
                            A3.Taunt        AS A3_Taunt, 
                            A3.Status       AS A3_Status,
                            A3.Dauer        AS A3_Dauer,
                            A3.Execute      AS A3_Execute
                        FROM schattenmonster
                        LEFT JOIN attacken AS A1 ON schattenmonster.Attack1 = A1.AttackenID 
                        LEFT JOIN attacken AS A2 ON schattenmonster.Attack2 = A2.AttackenID
                        LEFT JOIN attacken AS A3 ON schattenmonster.Attack3 = A3.AttackenID 
                        WHERE SchattenID = ?" ; 
            $stmt2 = mysqli_prepare($con, $sql2);
            mysqli_stmt_bind_param($stmt2, "i", $schatten['ID']);
            mysqli_stmt_execute($stmt2);
            $result2 = mysqli_stmt_get_result($stmt2);
            $attacke = mysqli_fetch_assoc($result2);
            // print_r($attacke);
            $schattenmonster = [
                            'ShadowID' => $schatten['ShadowID'],
                            'MonsterID' => $schatten['MID'],
                            'SchattenID' => $schatten['ID'],
                            'Name' => $schatten['Name'],
                            'Bild' => $schatten['Bild'],
                            'HP' => $schatten['HP'],
                            'HPmax' => $schatten['HP'],
                            'Initiativwert' => $schatten['Initiativwert'],
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
                            'Attacke3_Name' => $attacke['Attacke3_Name'],
                            'A3_MinSchaden' => $attacke['A3_MinSchaden'],
                            'A3_MaxSchaden' => $attacke['A3_MaxSchaden'],
                            'A3_AoE' => $attacke['A3_AoE'],
                            'A3_Taunt' => $attacke['A3_Taunt'],
                            'A3_Status' => $attacke['A3_Status'],
                            'A3_Dauer' => $attacke['A3_Dauer'],
                            'A3_Execute' => $attacke['A3_Execute'], 
                            'taunt' => false,
                            'tot' => false
        ];
        $schattendex[]=$schattenmonster;
        }
        $_SESSION['Schattendex'] = $schattendex;
}    

// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Save / Löschen Funktionen
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~


    function saveGame($con, $CharID) {
    if (!isset($_SESSION['Spieler'])) return false;

    // ---------------------------------------------------------
    // CHARAKTER STATS & FORTSCHRITT SPEICHERN
    // ---------------------------------------------------------
    
    $level = $_SESSION['Spieler']['Level'];
    $xp = $_SESSION['Spieler']['XP'];
    $hp = $_SESSION['Spieler']['HP'];
    $mana = $_SESSION['Spieler']['Mana'];
    $ausdauer = $_SESSION['Spieler']['Ausdauer'];
    
    // Fortschritt aus Session im Notfall 1 und 0
    $stage = isset($_SESSION['stage_level']) ? $_SESSION['stage_level'] : 1;
    $wins = isset($_SESSION['stage_wins']) ? $_SESSION['stage_wins'] : 0;

    $sql = "UPDATE charakterliste SET 
            Level = ?, 
            XP = ?, 
            HP = ?, 
            Mana = ?, 
            Ausdauer = ?,
            StageLevel = ?,
            StageWins = ?
            WHERE CharakterID = ?";
            
    $stmt = mysqli_prepare($con, $sql);
    mysqli_stmt_bind_param($stmt, "iiiiiiii", $level, $xp, $hp, $mana, $ausdauer, $stage, $wins, $CharID);
    mysqli_stmt_execute($stmt);

    // ---------------------------------------------------------
    // INVENTAR SPEICHERN 
    // ---------------------------------------------------------
    
    
    // Erst alles alte löschen
    $sqldel = "DELETE FROM charakterinventar WHERE CharakterID = ?";
    $stmtDel = mysqli_prepare($con, $sqldel);
    mysqli_stmt_bind_param($stmtDel, "i", $CharID);
    mysqli_stmt_execute($stmtDel);


    if (isset($_SESSION['Inventar']) && !empty($_SESSION['Inventar'])) {
        // Vorbereitung vor der Schleife
        $sqlin = "INSERT INTO charakterinventar (CharakterID, WaffenID, GegenstandsID, Anzahl) VALUES (?, ?, ?, ?)";
        $stmtIn = mysqli_prepare($con, $sqlin);
        
        foreach ($_SESSION['Inventar'] as $item) {
            $anzahl = $item['Anzahl'];       
            $WaffenID = isset($item['WaffenID']) && $item['WaffenID'] > 0 ? $item['WaffenID'] : 0;
            $GegenstandsID = isset($item['GegenstandsID']) && $item['GegenstandsID'] > 0 ? $item['GegenstandsID'] : 0;
            mysqli_stmt_bind_param($stmtIn, "iiii", $CharID, $WaffenID, $GegenstandsID, $anzahl);
            mysqli_stmt_execute($stmtIn);
            
        }
        mysqli_stmt_close($stmtIn);
    }

    // ---------------------------------------------------------
    // AUSRÜSTUNG SPEICHERN 
    // ---------------------------------------------------------
    
    // Erst alles alte löschen
    $sqldel2 = mysqli_prepare($con, "DELETE FROM charakterausruestung WHERE CharakterID = ?");
    mysqli_stmt_bind_param($sqldel2, "i", $CharID);
    mysqli_stmt_execute($sqldel2);
    mysqli_stmt_close($sqldel2);

    if (isset($_SESSION['Ausruestung']) && !empty($_SESSION['Ausruestung'])) {
        foreach ($_SESSION['Ausruestung'] as $slot => $item) {
            // Nur speichern, wenn nicht leer
            if (isset($item['IstLeer']) && $item['IstLeer'] == true) continue;

            $WaffenID      = isset($item['WaffenID'])      && $item['WaffenID']      > 0 ? $item['WaffenID']      : 0;
            $GegenstandsID = isset($item['GegenstandsID']) && $item['GegenstandsID'] > 0 ? $item['GegenstandsID'] : 0;
            $ShadowID      = isset($item['ShadowID'])      && $item['ShadowID']      > 0 ? $item['ShadowID']      : 0;

            $sqlequip = mysqli_prepare($con, "INSERT INTO charakterausruestung (CharakterID, WaffenID, GegenstandsID, ShadowID, Platz) 
                                            VALUES (?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($sqlequip, "iiiii", $CharID, $WaffenID, $GegenstandsID, $ShadowID, $slot);
            mysqli_stmt_execute($sqlequip);
            mysqli_stmt_close($sqlequip);
        }
    }
}

// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Burn/Freeze/Bleed Check
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    function Statuscheck($status){
        $zufall=rand(1,20);
        if ($status=='bleed' && $zufall>10){
            return true;
        } elseif ($status=='freeze' && $zufall>14){
            return true;
        } elseif ($status=='burn' && $zufall>12){
            return true;
        } elseif ($status=='stun' && $zufall>14){
            return true;
        } elseif ($status=='poison' && $zufall>12){
            return true;  
        } elseif ($status=='para' && $zufall>14){
            return true;      
        } else {
            return false;
        }
    }
?>
