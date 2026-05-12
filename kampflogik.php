
<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ---------------------------------------------------------------------
// Eingeloggt Check
// ---------------------------------------------------------------------
if (!isset($_SESSION["ID"])) {
    header("Location: login.php");
    exit;
}

// RELOAD-SCHUTZ
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: maingame.php");
    exit;
}

require_once('dbconfig.php');
include_once("funktionen.php");

// =======================================================================
// Daten aus Session holen ist einfacher
// =======================================================================

$kampfDaten = $_SESSION['Kampf'];
$spieler = $kampfDaten['spieler'];
$gegnerListe = $kampfDaten['gegner'];
$attacken = $kampfDaten['attacken'];
$schatten = $kampfDaten['schattenmonster'];
$reihenfolge = $kampfDaten['reihenfolge'];
$zug= $kampfDaten['zug_index'];
$runde= $kampfDaten['runde'];
$log= $kampfDaten['log'];
$kampfgewonnen=false;
$kampfverloren=false;
$fangmeldung = array();
$anzahlAlt = count($_SESSION['Kampf']['log']);

// =======================================================================
// Spieler am ZUG
// =======================================================================
if ($reihenfolge[$zug]['typ']=="spieler" ){
    // Als Erstes der Taunt Check
    if ($spieler['taunt']){
            $spieler['tauntcount']--;
        
    if ($spieler['tauntcount'] == 0){
            $spieler['taunt']=false;
    }   } 
    // Ende

    $darf_angreifen = true;  // Für Freeze Check und falls tot durch burn,bleed       
    $freeze = false;
    $para = false;
    // =======================================================================
    // FREEZE / Para / Stun CHECK
    // =======================================================================
    if ((isset($spieler['freeze']) && $spieler['freeze']) ||
        (isset($spieler['para']) && $spieler['para']) ||    
        (isset($spieler['stun']) && $spieler['stun']))
        {
        //Freeze
        if (isset($spieler['freeze']) && $spieler['freeze']){
        // Log Nachricht
        $freeze=true;
        $log[] = $spieler['Name'] . " ist &nbsp;<span style='color: #29b6f6;'>eingefroren</span>&nbsp; und muss aussetzen!";
        // Angriff verbieten
        $darf_angreifen = false;
        // Counter runtersetzen
        if ($spieler['freezecount'] > 1) {
            $spieler['freezecount']--; 
        } else {
        // Status beenden
            $spieler['freezecount'] = 0;
            $spieler['freeze'] = false;
        }
        }
        //Para
        if (isset($spieler['para']) && $spieler['para']){
        $para=true;    
        // Log Nachricht
        if ($freeze==false)
        $log[] = $spieler['Name'] . " ist &nbsp;<span style='color: #ffb300;'>paralysiert</span>&nbsp; und muss aussetzen!";
        // Angriff verbieten
        $darf_angreifen = false;
        // Counter runtersetzen
        if ($spieler['paracount'] > 1) {
            $spieler['paracount']--; 
        } else {
        // Status beenden
            $spieler['paracount'] = 0;
            $spieler['para'] = false;
        }
        }
        //Stun
        if (isset($spieler['stun']) && $spieler['stun']){   
        // Log Nachricht
        if ($freeze==false && $para==false)
        $log[] = $spieler['Name'] . " ist &nbsp;<span style='color: #757575;'>gestunnt</span>&nbsp; und muss aussetzen!";
        // Angriff verbieten
        $darf_angreifen = false;
        // Counter runtersetzen
        if ($spieler['stuncount'] > 1) {
            $spieler['stuncount']--; 
        } else {
        // Status beenden
            $spieler['stuncount'] = 0;
            $spieler['stun'] = false;
        }
        }
    }

    // =======================================================================
    // BURN / BLEED / POISON CHECK (Nur wenn er noch lebt)
    // =======================================================================
    if(isset($spieler['burn']) && $spieler['burn']){
        if ($spieler['burncount'] > 1) {
            $spieler['burncount']--; 
        } else {
            $spieler['burncount'] = 0;
            $spieler['burn'] = false;
        } 
        $schaden=(int)($spieler['HPmax']/20);
        $spieler['HP']-=$schaden;
        $log[] = $spieler['Name'] . " bekommt ".$schaden." Schaden durch &nbsp;<span style='color: #ff5722;'>Verbrennung</span>!";
        //Noch am Leben Check
        if ($spieler['HP']<=0){
            $kampfverloren=true; 
            $kampfstatus=false;
            $spieler['HP']=0;
            $darf_angreifen = false;
            header("Location: gameover.php");
            exit;
            } 
    }

    // Das Gleiche für Bleed

    if(isset($spieler['bleed']) && $spieler['bleed']){
        if ($spieler['bleedcount'] > 1) {
            $spieler['bleedcount']--; 
        } else {
            $spieler['bleedcount'] = 0;
            $spieler['bleed'] = false;
        } 
        $schaden=(int)($spieler['HPmax']/20);
        $spieler['HP']-=$schaden;
        $log[] = $spieler['Name'] . " bekommt ".$schaden." &nbsp;<span style='color: #5a1111;'>Blutungsschaden</span>!";
        //Noch am Leben Check
        if ($spieler['HP']<=0){
            $kampfverloren=true; 
            $kampfstatus=false;
            $spieler['HP']=0;
            $darf_angreifen = false;
            header("Location: gameover.php");
            exit;
            } 
    }

    // Das Gleiche für Poison

    if(isset($spieler['poison']) && $spieler['poison']){
        if ($spieler['poisoncount'] > 1) {
            $spieler['poisoncount']--; 
        } else {
            $spieler['poisoncount'] = 0;
            $spieler['poison'] = false;
        } 
        $schaden=(int)($spieler['HPmax']/20);
        $spieler['HP']-=$schaden;
        $log[] = $spieler['Name'] . " bekommt ".$schaden." Schaden durch &nbsp;<span style='color: #43a047;'>Vergiftung</span>!";
        //Noch am Leben Check
        if ($spieler['HP']<=0){
            $kampfverloren=true; 
            $kampfstatus=false;
            $spieler['HP']=0;
            $darf_angreifen = false;
            header("Location: gameover.php");
            exit;
            } 
    }


// =======================================================================
// Zug Start
// =======================================================================
if ($darf_angreifen) {

// =======================================================================
// Gegenstand Logik
// =======================================================================
    $aktion=$_POST['aktion'];
    if ($aktion=='itembenutzen'){
                // Sicherheits-Check
                if (!isset($_POST['itemname']) || !isset($_SESSION['Inventar'][$_POST['itemname']])) {
                    header("Location: maingame.php");
                    exit;
                }

                $Itemname=$_POST['itemname'];
                $target=$_POST['target'];
                $Itemzwischen=array();
                $Itemzwischen=$_SESSION['Inventar'][$Itemname];

                // Trank Check und Variablen nach Trankart nennen
                $Potion = "";
                $Potioncheck = "";
                $wert = 0;

                if (isset($Itemzwischen['HP']) && $Itemzwischen['HP']>0){
                    $Potion="HP";
                    $Potioncheck="HPmax";
                    $wert=$Itemzwischen['HP'];
                    }
                    if (isset($Itemzwischen['Mana']) && $Itemzwischen['Mana']>0){
                    $Potion="Mana";
                    $Potioncheck="Manamax";
                    $wert=$Itemzwischen['Mana'];
                    }
                    if (isset($Itemzwischen['Ausdauer']) && $Itemzwischen['Ausdauer']>0){
                    $Potion="Ausdauer";
                    $Potioncheck="Ausdauermax";
                    $wert=$Itemzwischen['Ausdauer'];
                    }
                    if ($Potion != ""){
                        if ($target=="Spieler"){
                        // check ob trank was bringt
                            if ($spieler[$Potion]<$spieler[$Potioncheck]){
                                $spieler[$Potion]+=$Itemzwischen[$Potion];
                                $log[]= "Du trinkst einen Trank. ( +".$wert." ".$Potion." )";
                                //falls neu > Max
                                if ($spieler[$Potion]>$spieler[$Potioncheck]){
                                    $spieler[$Potion]=$spieler[$Potioncheck];
                                }
                                if ($_SESSION['Inventar'][$Itemname]['Anzahl']>1){
                                    $_SESSION['Inventar'][$Itemname]['Anzahl']--;
                                } else {
                                    unset($_SESSION['Inventar'][$Itemname]);
                                }    
                            } else {
                            $log[]= "Du hast bereits volle $Potion!";
                            }
                        }
            
                    // print_r($target);
                    // print_r($Potion);
                    // print_r($Itemzwischen);
                    if ($target=="Schattenmonster" && $Potion=='HP' && $schatten[$Potion]<$schatten[$Potioncheck] && !$schatten['tot']){
                        $log[]= "Du gibst deinem Begleiter einen Trank. ( +".$wert." ".$Potion." )";
                        // print_r($Itemzwischen);
                        $schatten[$Potion]+=$Itemzwischen[$Potion];
                            //falls neu > Max
                            if ($schatten[$Potion]>$schatten[$Potioncheck]){
                                $schatten[$Potion]=$schatten[$Potioncheck];
                            }  
                            if ($_SESSION['Inventar'][$Itemname]['Anzahl']>1){
                                $_SESSION['Inventar'][$Itemname]['Anzahl']--;
                            } else {
                                unset($_SESSION['Inventar'][$Itemname]);
                            } 
                    } elseif ($target == "Schattenmonster" && $schatten['tot']) {
                            $log[] = "Das Schattenmonster ist besiegt und kann nicht geheilt werden!";
                            $_SESSION['Kampf']['log'] = $log;
                            $_SESSION['Kampf']['inventar_offen'] = false;
                            header("Location: maingame.php");
                            exit; 
                        } elseif ($target == "Schattenmonster") {
                            $log[] = "Das Schattenmonster hat bereits volle HP!";
                            $_SESSION['Kampf']['log'] = $log;
                            $_SESSION['Kampf']['inventar_offen'] = false;
                            header("Location: maingame.php");
                            exit;
                        }

                    }        


    } else {
    // =======================================================================
    // Angriff Logik
    // =======================================================================

    $atkindex=$_POST['aktion'];
    $gegindex=$_POST['ziel'];

    // Sicherheits-Check: Existiert die Attacke und der Gegner?
    if (!isset($attacken[$atkindex]) || !isset($gegnerListe[$gegindex])) {
         header("Location: maingame.php");
         exit;
    }

    $schaden=damage($attacken[$atkindex]['MinSchaden'],$attacken[$atkindex]['MaxSchaden']);

        // =======================================================================
        // AoE Angriff
        // =======================================================================

        if (isset($attacken[$atkindex]['AoE']) && $attacken[$atkindex]['AoE']){
            foreach($gegnerListe as $id => $monster){
                
                // Statuscheck Burn Freeze Bleed und Dauer setzen...
                if (isset($attacken[$atkindex]['Status']) && Statuscheck($attacken[$atkindex]['Status']) && $attacken[$atkindex]['Status']!=$gegnerListe[$id]['Immunity'] && $gegnerListe[$id]['Immunity']!='boss'){
                    $status=$attacken[$atkindex]['Status'];
                    $statusart= $status."count";
                    $gegnerListe[$id][$status]=true;
                    $gegnerListe[$id][$statusart]=$attacken[$atkindex]['Dauer'];
                    if ($status=='poison')
                    $log[]= "Du hast ".$gegnerListe[$id]['Name']."&nbsp;<span style='color: #43a047;'> vergiftet</span>!";
                    if ($status=='bleed')
                    $log[]= "Du hast ".$gegnerListe[$id]['Name']."&nbsp;<span style='color: #5a1111;'> Blutung</span> zugefügt!";
                    if ($status=='burn')
                    $log[]= "Du hast ".$gegnerListe[$id]['Name']."&nbsp;<span style='color: #ff5722;'> verbrannt</span>!";
                    if ($status=='freeze')
                    $log[]= "Du hast ".$gegnerListe[$id]['Name']."&nbsp;<span style='color: #29b6f6;'> eingefroren</span>!";
                    if ($status=='para')
                    $log[]= "Du hast ".$gegnerListe[$id]['Name']."&nbsp;<span style='color: #ffb300;'> paralysiert</span>!";
                    if ($status=='stun')
                    $log[]= "Du hast ".$gegnerListe[$id]['Name']."&nbsp;<span style='color: #757575;'> gestunnt</span>!";
                }
                // Schaden abziehen und tot check
                $gegnerListe[$id]['HP']-=$schaden;
                    if ($gegnerListe[$id]['HP']<=0){
                    $gegnerListe[$id]['tot']=true;
                    $gegnerListe[$id]['HP']=0;
                }     
            }
            // kosten berechnen und log
            $spieler['Ausdauer']-=$attacken[$atkindex]['Ausdauerkosten'];
            $spieler['Mana']-=$attacken[$atkindex]['Manakosten'];  
                if ($schaden>$attacken[$atkindex]['MaxSchaden']){
                $log[]= "Du greifst mit ".$attacken[$atkindex]['Name']."(AoE) an.<span style='color: #ff5555;'>(".$schaden." kritischer Schaden)</span>";
                } else {
                $log[]= "Du greifst mit ".$attacken[$atkindex]['Name']."(AoE) an. (".$schaden." Schaden)";
                }  
        } else {
        // =======================================================================
        // Singel Target Angriff
        // =======================================================================    
        // Status Check
        if (isset($attacken[$atkindex]['Status']) && Statuscheck($attacken[$atkindex]['Status']) && $attacken[$atkindex]['Status']!=$gegnerListe[$id]['Immunity'] && $gegnerListe[$id]['Immunity']!='boss'){
                    $status=$attacken[$atkindex]['Status'];
                    $statusart= $status."count";
                    $gegnerListe[$gegindex][$status]=true;
                    $gegnerListe[$gegindex][$statusart]=$attacken[$atkindex]['Dauer'];
                    if ($status=='poison')
                    $log[]= "Du hast ".$gegnerListe[$gegindex]['Name']."&nbsp;<span style='color: #43a047;'>vergiftet</span>!";
                    if ($status=='bleed')
                    $log[]= "Du hast ".$gegnerListe[$gegindex]['Name']."&nbsp;<span style='color: #5a1111;'>Blutung</span> zugefügt!";
                    if ($status=='burn')
                    $log[]= "Du hast ".$gegnerListe[$gegindex]['Name']."&nbsp;<span style='color: #ff5722;'>verbrannt</span>!";
                    if ($status=='freeze')
                    $log[]= "Du hast ".$gegnerListe[$gegindex]['Name']."&nbsp;<span style='color: #29b6f6;'>eingefroren</span>!";
                    if ($status=='para')
                    $log[]= "Du hast ".$gegnerListe[$gegindex]['Name']."&nbsp;<span style='color: #ffb300;'>paralysiert</span>!";
                    if ($status=='stun')
                    $log[]= "Du hast ".$gegnerListe[$gegindex]['Name']."&nbsp;<span style='color: #757575;'>gestunnt</span>!";
                }    
        // Execute Check
        $hpProzent = $gegnerListe[$gegindex]['HP'] / $gegnerListe[$gegindex]['HPmax'];
        $executestatus=false;
        if (isset($attacken[$atkindex]['Execute']) && $attacken[$atkindex]['Execute'] && $hpProzent <= 0.2) {
            $schaden*=2;
            $executestatus=true;
        }    
        // Schaden abziehen   
        $gegnerListe[$gegindex]['HP']-=$schaden;
            // Tod Check
            if ($gegnerListe[$gegindex]['HP']<=0){
            $gegnerListe[$gegindex]['tot']=true;
            $gegnerListe[$gegindex]['HP']=0;
            } 
            // Angriffskosten berechnen
            $spieler['Ausdauer']-=$attacken[$atkindex]['Ausdauerkosten'];
            $spieler['Mana']-=$attacken[$atkindex]['Manakosten'];
        // Log schreiben
        if ($executestatus){
            $log[]= "Du greifst ".$gegnerListe[$gegindex]['Name']." an. (<span style='color: #ff5555;'>".$schaden." Execute Schaden</span>)";
        } elseif ($schaden>$attacken[$atkindex]['MaxSchaden']){
            $log[]= "Du greifst ".$gegnerListe[$gegindex]['Name']." an. (<span style='color: #ff5555;'>".$schaden." kritischer Schaden</span>)";
        } else {
            $log[]= "Du greifst ".$gegnerListe[$gegindex]['Name']." an. (".$schaden." Schaden)";
        }

        }
        if (isset($attacken[$atkindex]['Taunt']) && $attacken[$atkindex]['Taunt']){
            $spieler['taunt']=true;
            $spieler['tauntcount'] = 2;
            $log[]= "Du verspottest die Gegner für ".$spieler['tauntcount']." Runden.";
        }      
    }    
}
}
// =======================================================================
// Schattenmonster am ZUG
// =======================================================================

if ($reihenfolge[$zug]['typ']=="schatten"){
    // Als erstes der Taunt Check
    if ($schatten['taunt']){
            $schatten['tauntcount']--;
            if ($schatten['tauntcount'] == 0){
            $schatten['taunt']=false;
            } 
        }
    // Ende
    
    $darf_angreifen = true;  // Für Freeze Check und falls tot durch burn,bleed       
    $freeze = false;
    $para = false;
    // =======================================================================
    // FREEZE / Para / Stun CHECK
    // =======================================================================
    if ((isset($schatten['freeze']) && $schatten['freeze']) ||
        (isset($schatten['para']) && $schatten['para']) ||    
        (isset($schatten['stun']) && $schatten['stun']))
        {
        //Freeze
        if (isset($schatten['freeze']) && $schatten['freeze']){
        // Log Nachricht
        $freeze=true;
        $log[] = $schatten['Name'] . " ist &nbsp;<span style='color: #29b6f6;'>eingefroren</span>&nbsp; und muss aussetzen!";
        // Angriff verbieten
        $darf_angreifen = false;
        // Counter runtersetzen
        if ($schatten['freezecount'] > 1) {
            $schatten['freezecount']--; 
        } else {
        // Status beenden
            $schatten['freezecount'] = 0;
            $schatten['freeze'] = false;
        }
        }
        //Para
        if (isset($schatten['para']) && $schatten['para']){
        $para=true;    
        // Log Nachricht
        if ($freeze==false)
        $log[] = $schatten['Name'] . " ist &nbsp;<span style='color: #ffb300;'>paralysiert</span>&nbsp; und muss aussetzen!";
        // Angriff verbieten
        $darf_angreifen = false;
        // Counter runtersetzen
        if ($schatten['paracount'] > 1) {
            $schatten['paracount']--; 
        } else {
        // Status beenden
            $schatten['paracount'] = 0;
            $schatten['para'] = false;
        }
        }
        //Stun
        if (isset($schatten['stun']) && $schatten['stun']){   
        // Log Nachricht
        if ($freeze==false && $para==false)
        $log[] = $schatten['Name'] . " ist &nbsp;<span style='color: #757575;'>gestunnt</span>&nbsp; und muss aussetzen!";
        // Angriff verbieten
        $darf_angreifen = false;
        // Counter runtersetzen
        if ($schatten['stuncount'] > 1) {
            $schatten['stuncount']--; 
        } else {
        // Status beenden
            $schatten['stuncount'] = 0;
            $schatten['stun'] = false;
        }
        }
    }

    // =======================================================================
    // BURN / BLEED / POISON CHECK (Nur wenn er noch lebt)
    // =======================================================================
    if(isset($schatten['burn']) && $schatten['burn']){
        if ($schatten['burncount'] > 1) {
            $schatten['burncount']--; 
        } else {
            $schatten['burncount'] = 0;
            $schatten['burn'] = false;
        } 
        $schaden=(int)($schatten['HPmax']/20);
        $schatten['HP']-=$schaden;
        $log[] = $schatten['Name'] . " bekommt ".$schaden." Schaden durch &nbsp;<span style='color: #ff5722;'>Verbrennung</span>!";
        //Noch am Leben Check
        if ($schatten['HP']<=0){
            $schatten['tot']=true;
            $schatten['HP']=0;
            $darf_angreifen = false;
            } 
    }

    // Das Gleiche für Bleed

    if(isset($schatten['bleed']) && $schatten['bleed']){
        if ($schatten['bleedcount'] > 1) {
            $schatten['bleedcount']--; 
        } else {
            $schatten['bleedcount'] = 0;
            $schatten['bleed'] = false;
        } 
        $schaden=(int)($schatten['HPmax']/20);
        $schatten['HP']-=$schaden;
        $log[] = $schatten['Name'] . " bekommt ".$schaden." &nbsp;<span style='color: #5a1111;'>Blutungsschaden</span>!";
        //Noch am Leben Check
        if ($schatten['HP']<=0){
            $schatten['tot']=true;
            $schatten['HP']=0;
            $darf_angreifen = false;
            } 
    }

    // Das Gleiche für Poison

    if(isset($schatten['poison']) && $schatten['poison']){
        if ($schatten['poisoncount'] > 1) {
            $schatten['poisoncount']--; 
        } else {
            $schatten['poisoncount'] = 0;
            $schatten['poison'] = false;
        } 
        $schaden=($schatten['HPmax']/20);
        $schatten['HP']-=$schaden;
        $log[] = $schatten['Name'] . " bekommt ".$schaden." Schaden durch &nbsp;<span style='color: #43a047;'>Vergiftung</span>!";
        //Noch am Leben Check
        if ($schatten['HP']<=0){
            $schatten['tot']=true;
            $schatten['HP']=0;
            $darf_angreifen = false;
            } 
    }


    // =======================================================================
    // Angriff
    // =======================================================================
    if ($darf_angreifen && !$schatten['tot']) {
    // =======================================================================
    // Dynamischer Zusammenbau einiger Variablen für Abfragen
    // =======================================================================

    $atkindex=$_POST['aktion'];
    $atkmin="A".$atkindex."_MinSchaden";
    $atkmax="A".$atkindex."_MaxSchaden";
    $atkname="Attacke".$atkindex."_Name";
    $AoE="A".$atkindex."_AoE";
    $Taunt="A".$atkindex."_Taunt";
    $Status="A".$atkindex."_Status";
    $Dauer="A".$atkindex."_Dauer";
    $Execute="A".$atkindex."_Execute"; 
    $gegindex=$_POST['ziel'];


    $schaden=damage($schatten[$atkmin],$schatten[$atkmax]);

        // =======================================================================
        // AoE Angriff 
        // =======================================================================
        if ($schatten[($AoE)]){
            foreach($gegnerListe as $id => $monster){
                // Statuscheck Burn Freeze Bleed und Dauer setzen...
                if (isset($schatten[$Status]) && Statuscheck($schatten[$Status]) && $schatten[$Status]!=$gegnerListe[$id]['Immunity'] && $schatten[$Status]!='boss'){
                    $statusgeg=$schatten[$Status];
                    $statusart= $statusgeg."count";
                    $gegnerListe[$id][$statusgeg]=true;
                    $gegnerListe[$id][$statusart]=$schatten[$Dauer];
                    if ($status=='poison')
                    $log[]="<span style='color: #9c27b0;'>&nbsp".$schatten['Name']."</span>&nbsp; hat ".$gegnerListe[$id]['Name']." &nbsp;<span style='color: #43a047;'>vergiftet</span>!";
                    if ($status=='bleed')
                    $log[]="<span style='color: #9c27b0;'>&nbsp".$schatten['Name']."</span>&nbsp; hat ".$gegnerListe[$id]['Name']." &nbsp;<span style='color: #5a1111;'>Blutung</span> zugefügt!";
                    if ($status=='burn')
                    $log[]="<span style='color: #9c27b0;'>&nbsp".$schatten['Name']."</span>&nbsp; hat ".$gegnerListe[$id]['Name']." &nbsp;<span style='color: #ff5722;'>verbrannt</span>!";
                    if ($status=='freeze')
                    $log[]="<span style='color: #9c27b0;'>&nbsp".$schatten['Name']."</span>&nbsp; hat ".$gegnerListe[$id]['Name']." &nbsp;<span style='color: #29b6f6;'>eingefroren</span>!";
                    if ($status=='para')
                    $log[]="<span style='color: #9c27b0;'>&nbsp".$schatten['Name']."</span>&nbsp; hat ".$gegnerListe[$id]['Name']." &nbsp;<span style='color: #ffb300;'>paralysiert</span>!";
                    if ($status=='stun')
                    $log[]="<span style='color: #9c27b0;'>&nbsp".$schatten['Name']."</span>&nbsp; hat ".$gegnerListe[$id]['Name']." &nbsp;<span style='color: #757575;'>gestunnt</span>!";
                }
                $gegnerListe[$id]['HP']-=$schaden;
                if ($gegnerListe[$id]['HP']<=0){
                $gegnerListe[$id]['tot']=true;
                $gegnerListe[$id]['HP']=0;
                }  
                if ($schaden>$schatten[$atkmax]){
                $log[]="<span style='color: #9c27b0;'>&nbsp".$schatten['Name']."</span>&nbsp; greift mit ".$schatten[$atkname]."(AoE) an.<span style='color: #ff5555;'>(".$schaden." kritischer Schaden)</span>";
                } else {
                $log[]="<span style='color: #9c27b0;'>&nbsp".$schatten['Name']."</span>&nbsp; greift mit ".$schatten[$atkname]."(AoE) an. (".$schaden." Schaden)";
                }   
            }    
        } else {
        // =======================================================================
        // Single Target Angriff
        // =======================================================================    
        if (isset($schatten[$Status]) && Statuscheck($schatten[$Status]) && $schatten[$Status]!=$gegnerListe[$id]['Immunity'] && $schatten[$Status]!='boss'){
                    $statusgeg=$schatten[$Status];
                    $statusart= $statusgeg."count";
                    $gegnerListe[$gegindex][$statusgeg]=true;
                    $gegnerListe[$gegindex][$statusart]=$schatten[$Dauer];
                    if ($status=='poison')
                    $log[]="<span style='color: #9c27b0;'>&nbsp".$schatten['Name']."</span>&nbsp; hat ".$gegnerListe[$gegindex]['Name']." &nbsp;<span style='color: #43a047;'>vergiftet</span>!";
                    if ($status=='bleed')
                    $log[]="<span style='color: #9c27b0;'>&nbsp".$schatten['Name']."</span>&nbsp; hat ".$gegnerListe[$gegindex]['Name']." &nbsp;<span style='color: #5a1111;'>Blutung</span> zugefügt!";
                    if ($status=='burn')
                    $log[]="<span style='color: #9c27b0;'>&nbsp".$schatten['Name']."</span>&nbsp; hat ".$gegnerListe[$gegindex]['Name']." &nbsp;<span style='color: #ff5722;'>verbrannt</span>!";
                    if ($status=='freeze')
                    $log[]="<span style='color: #9c27b0;'>&nbsp".$schatten['Name']."</span>&nbsp; hat ".$gegnerListe[$gegindex]['Name']." &nbsp;<span style='color: #29b6f6;'>eingefroren</span>!";
                    if ($status=='para')
                    $log[]="<span style='color: #9c27b0;'>&nbsp".$schatten['Name']."</span>&nbsp; hat ".$gegnerListe[$gegindex]['Name']." &nbsp;<span style='color: #ffb300;'>paralysiert</span>!";
                    if ($status=='stun')
                    $log[]="<span style='color: #9c27b0;'>&nbsp".$schatten['Name']."</span>&nbsp; hat ".$gegnerListe[$gegindex]['Name']." &nbsp;<span style='color: #757575;'>gestunnt</span>!";  
        }  
        // Execute Check
        $hpProzent = $gegnerListe[$gegindex]['HP'] / $gegnerListe[$gegindex]['HPmax'];
        $executestatus=false;
        if (isset($schatten[$Execute]) && $schatten[$Execute] && $hpProzent <= 0.2) {
            $schaden*=2;
            $executestatus=true;
        }    
        // Schaden abziehen 
        $gegnerListe[$gegindex]['HP']-=$schaden;
            if ($gegnerListe[$gegindex]['HP']<=0){
            $gegnerListe[$gegindex]['tot']=true;
            $gegnerListe[$gegindex]['HP']=0;
            } 
        }
        if ($schatten[($Taunt)]){
            $schatten['taunt']=true;
            $schatten['tauntcount'] = 2;
            $log[]="<span style='color: #9c27b0;'>&nbsp".$schatten['Name']."</span>&nbsp;<span>verspottet die Gegner!</span>";
        }
        // Log schreiben
        if ($schaden>0){
        if ($executestatus){
            $log[]="<p style='color: #9c27b0;'>&nbsp".$schatten['Name']."</p>&nbsp;greift ".$gegnerListe[$gegindex]['Name']." an. (<span style='color: #ff5555;'>".$schaden." Execute Schaden</span>)";
        } elseif ($schaden>$schatten[$atkmax]){
            $log[]="<p style='color: #9c27b0;'>&nbsp".$schatten['Name']."</p>&nbsp;greift ".$gegnerListe[$gegindex]['Name']." an. (<span style='color: #ff5555;'>".$schaden." kritischer Schaden</span>)";
        } else {
            $log[]="<span style='color: #9c27b0;'>&nbsp".$schatten['Name']."</span>&nbsp;<span>greift ".$gegnerListe[$gegindex]['Name']." an. (".$schaden." Schaden)</span>";
        }        
    }
}  
}
// =======================================================================
// Gegner am ZUG
// ======================================================================= 
if ($reihenfolge[$zug]['typ'] == "gegner") {

    $id = $reihenfolge[$zug]['id']; // ID holen 
    $darf_angreifen = true;  // Für Freeze Check und falls tot durch burn,bleed       
    $freeze = false;
    $para = false;
    // =======================================================================
    // FREEZE / Para / Stun CHECK
    // =======================================================================
    if ((isset($gegnerListe[$id]['freeze']) && $gegnerListe[$id]['freeze']) ||
        (isset($gegnerListe[$id]['para']) && $gegnerListe[$id]['para']) ||    
        (isset($gegnerListe[$id]['stun']) && $gegnerListe[$id]['stun']))
        {
        //Freeze
        if (isset($gegnerListe[$id]['freeze']) && $gegnerListe[$id]['freeze']){
        // Log Nachricht
        $freeze=true;
        $log[] = $gegnerListe[$id]['Name'] . " ist&nbsp;<span style='color: #29b6f6;'>eingefroren</span>&nbsp;und muss aussetzen!";
        // Angriff verbieten
        $darf_angreifen = false;
        // Counter runtersetzen
        if ($gegnerListe[$id]['freezecount'] > 1) {
            $gegnerListe[$id]['freezecount']--; 
        } else {
        // Status beenden
            $gegnerListe[$id]['freezecount'] = 0;
            $gegnerListe[$id]['freeze'] = false;
        }
        }
        //Para
        if (isset($gegnerListe[$id]['para']) && $gegnerListe[$id]['para']){
        $para=true;    
        // Log Nachricht
        if ($freeze==false)
        $log[] = $gegnerListe[$id]['Name'] . " ist&nbsp;<span style='color: #ffb300;'>paralysiert</span>&nbsp;und muss aussetzen!";
        // Angriff verbieten
        $darf_angreifen = false;
        // Counter runtersetzen
        if ($gegnerListe[$id]['paracount'] > 1) {
            $gegnerListe[$id]['paracount']--; 
        } else {
        // Status beenden
            $gegnerListe[$id]['paracount'] = 0;
            $gegnerListe[$id]['para'] = false;
        }
        }
        //Stun
        if (isset($gegnerListe[$id]['stun']) && $gegnerListe[$id]['stun']){   
        // Log Nachricht
        if ($freeze==false && $para==false)
        $log[] = $gegnerListe[$id]['Name'] . " ist&nbsp;<span style='color: #757575;'>gestunnt</span>&nbsp;und muss aussetzen!";
        // Angriff verbieten
        $darf_angreifen = false;
        // Counter runtersetzen
        if ($gegnerListe[$id]['stuncount'] > 1) {
            $gegnerListe[$id]['stuncount']--; 
        } else {
        // Status beenden
            $gegnerListe[$id]['stuncount'] = 0;
            $gegnerListe[$id]['stun'] = false;
        }
        }
    }
    // =======================================================================
    // BURN / BLEED / POISON CHECK (Nur wenn er noch lebt)
    // =======================================================================
    if(isset($gegnerListe[$id]['burn']) && $gegnerListe[$id]['burn']){
        if ($gegnerListe[$id]['burncount'] > 1) {
            $gegnerListe[$id]['burncount']--; 
        } else {
            $gegnerListe[$id]['burncount'] = 0;
            $gegnerListe[$id]['burn'] = false;
        } 
        $schaden=(int)($gegnerListe[$id]['HPmax']/20);
        $gegnerListe[$id]['HP']-=$schaden;
        $log[] = $gegnerListe[$id]['Name'] . " bekommt ".$schaden." Schaden durch&nbsp;<span style='color: #ff5722;'>Verbrennung</span>!";
        //Noch am Leben Check
        if ($gegnerListe[$id]['HP']<=0){
            $gegnerListe[$id]['tot']=true;
            $gegnerListe[$id]['HP']=0;
            $darf_angreifen = false;
            } 
    }

    // Das Gleiche für Bleed

    if(isset($gegnerListe[$id]['bleed']) && $gegnerListe[$id]['bleed']){
        if ($gegnerListe[$id]['bleedcount'] > 1) {
            $gegnerListe[$id]['bleedcount']--; 
        } else {
            $gegnerListe[$id]['bleedcount'] = 0;
            $gegnerListe[$id]['bleed'] = false;
        } 
        $schaden=(int)($gegnerListe[$id]['HPmax']/20);
        $gegnerListe[$id]['HP']-=$schaden;
        $log[] = $gegnerListe[$id]['Name'] . " bekommt ".$schaden."&nbsp;<span style='color: #5a1111;'>Blutungsschaden</span>!";
        //Noch am Leben Check
        if ($gegnerListe[$id]['HP']<=0){
            $gegnerListe[$id]['tot']=true;
            $gegnerListe[$id]['HP']=0;
            $darf_angreifen = false;
            } 
    }

    // Das Gleiche für Poison

    if(isset($gegnerListe[$id]['poison']) && $gegnerListe[$id]['poison']){
        if ($gegnerListe[$id]['poisoncount'] > 1) {
            $gegnerListe[$id]['poisoncount']--; 
        } else {
            $gegnerListe[$id]['poisoncount'] = 0;
            $gegnerListe[$id]['poison'] = false;
        } 
        $schaden=($gegnerListe[$id]['HPmax']/20);
        $gegnerListe[$id]['HP']-=(int)$schaden;
        $log[] = $gegnerListe[$id]['Name'] . " bekommt ".$schaden." Schaden durch&nbsp;<span style='color: #43a047;'>Vergiftung</span>!";
        //Noch am Leben Check
        if ($gegnerListe[$id]['HP']<=0){
            $gegnerListe[$id]['tot']=true;
            $gegnerListe[$id]['HP']=0;
            $darf_angreifen = false;
            } 
    }

    // =======================================================================
    // DER ANGRIFF 
    // =======================================================================
    if ($darf_angreifen) {

        
        // Zufällige Attacke
        $atkindex = rand(1, 2);
        $atkmin = "A" . $atkindex . "_MinSchaden";
        $atkmax = "A" . $atkindex . "_MaxSchaden";
        $atkAoE = "A" . $atkindex . "_AoE";
        $atkStatus = "A" . $atkindex . "_Status";
        $atkDauer = "A" . $atkindex . "_Dauer";
        $atkExecute = "A" . $atkindex . "_Execute";
        $atkName = "A" . $atkindex . "_Name";

        $randtarget = rand(1, 2);
        
        $schaden = damage($gegnerListe[$id][$atkmin], $gegnerListe[$id][$atkmax]);

      
        // =======================================================================
        // AoE Angriff???
        // =======================================================================        
        if ($gegnerListe[$id][$atkAoE]){
            // Status gegen Player
            // Statuscheck Burn Freeze Bleed und Dauer setzen...
            if (isset($gegnerListe[$id][$atkStatus]) && Statuscheck($gegnerListe[$id][$atkStatus])){
                $statusgeg=$gegnerListe[$id][$atkStatus];
                $statusart= $statusgeg."count";
                $spieler[$statusgeg]=true;
                $spieler[$statusart]=$gegnerListe[$id][$atkDauer];
                    if ($statusgeg=='poison')
                    $log[]= $gegnerListe[$id]['Name']." hat &nbsp;<span style='color: var(--btn-bg);'> ".$spieler['Name']."</span>&nbsp;<span style='color: #43a047;'> vergiftet</span>!";
                    if ($statusgeg=='bleed')
                    $log[]= $gegnerListe[$id]['Name']." hat &nbsp;<span style='color: var(--btn-bg);'> ".$spieler['Name']."</span>&nbsp;<span style='color: #5a1111;'> Blutung</span>&nbsp;zugefügt!";
                    if ($statusgeg=='burn')
                    $log[]= $gegnerListe[$id]['Name']." hat &nbsp;<span style='color: var(--btn-bg);'> ".$spieler['Name']."</span>&nbsp;<span style='color: #ff5722;'> verbrannt</span>!";
                    if ($statusgeg=='freeze')
                    $log[]= $gegnerListe[$id]['Name']." hat &nbsp;<span style='color: var(--btn-bg);'> ".$spieler['Name']."</span>&nbsp;<span style='color: #29b6f6;'> eingefroren</span>!";
                    if ($statusgeg=='para')
                    $log[]= $gegnerListe[$id]['Name']." hat &nbsp;<span style='color: var(--btn-bg);'> ".$spieler['Name']."</span>&nbsp;<span style='color: #ffb300;'> paralysiert</span>!";
                    if ($statusgeg=='stun')
                    $log[]= $gegnerListe[$id]['Name']." hat &nbsp;<span style='color: var(--btn-bg);'> ".$spieler['Name']."</span>&nbsp;<span style='color: #757575;'> gestunnt</span>!";
            }
            // Status gegen Schattenmonster
            // Statuscheck Burn Freeze Bleed und Dauer setzen...
            if (isset($gegnerListe[$id][$atkStatus]) && Statuscheck($gegnerListe[$id][$atkStatus])){
                $statusgeg=$gegnerListe[$id][$atkStatus];
                $statusart= $statusgeg."count";
                $schatten[$statusgeg]=true;
                $schatten[$statusart]=$gegnerListe[$id][$atkDauer];
                    if ($statusgeg=='poison')
                    $log[]= $gegnerListe[$id]['Name']." hat &nbsp;<span style='color: #9c27b0;'> ".$schatten['Name']."</span>&nbsp;<span style='color: #43a047;'> vergiftet</span>!";
                    if ($statusgeg=='bleed')
                    $log[]= $gegnerListe[$id]['Name']." hat &nbsp;<span style='color: #9c27b0;'> ".$schatten['Name']."</span>&nbsp;<span style='color: #5a1111;'> Blutung</span>&nbsp;zugefügt!";
                    if ($statusgeg=='burn')
                    $log[]= $gegnerListe[$id]['Name']." hat &nbsp;<span style='color: #9c27b0;'> ".$schatten['Name']."</span>&nbsp;<span style='color: #ff5722;'> verbrannt</span>!";
                    if ($statusgeg=='freeze')
                    $log[]= $gegnerListe[$id]['Name']." hat &nbsp;<span style='color: #9c27b0;'> ".$schatten['Name']."</span>&nbsp;<span style='color: #29b6f6;'> eingefroren</span>!";
                    if ($statusgeg=='para')
                    $log[]= $gegnerListe[$id]['Name']." hat &nbsp;<span style='color: #9c27b0;'> ".$schatten['Name']."</span>&nbsp;<span style='color: #ffb300;'> paralysiert</span>!";
                    if ($statusgeg=='stun')
                    $log[]= $gegnerListe[$id]['Name']." hat &nbsp;<span style='color: #9c27b0;'> ".$schatten['Name']."</span>&nbsp;<span style='color: #757575;'> gestunnt</span>!";
            }
            $spieler['HP']-=$schaden;
            if ($spieler['HP']<=0){
                $kampfverloren=true; 
                $kampfstatus=false;
                header("Location: gameover.php");
                exit;
            }  
            $schatten['HP']-=$schaden;
            if ($schatten['HP']<=0){
                $schatten['tot']=true;
                $schatten['HP']=0;
            }  
            if ($schaden>$gegnerListe[$id][$atkmax]){
            $log[]= $gegnerListe[$id]['Name']." greift mit ".$gegnerListe[$id][$atkName]." (AoE) an.<span style='color: #ff5555;'>(".$schaden." kritischer Schaden)</span>";
            } else {
            $log[]= $gegnerListe[$id]['Name']." greift mit ".$gegnerListe[$id][$atkName]." (AoE) an. (".$schaden." Schaden)";
            } 
        } else {
// =======================================================================
// Single Target Angriff
// =======================================================================
           
        // =======================================================================
        // Target Änderung durch Taunt?
        // =======================================================================
        // Wenn beide Spieler noch leben
        if ($spieler['HP'] > 0 && !empty($schatten) && $schatten['HP'] > 0) {
            // Wenn taunt dann ziel wählen
            if (isset($schatten['taunt']) && $schatten['taunt']){
                    $target="schatten";
                }
            elseif (isset($spieler['taunt']) && $spieler['taunt']){
                    $target="spieler";
                }
            // Wenn beide taunt oder kein taunt dann zufall        
            if (($schatten['taunt'] && $spieler['taunt']) || (!$schatten['taunt'] && !$spieler['taunt'])){
                // Random 
                if ($randtarget==1) {
                $target="spieler";
                }
                if ($randtarget==2) {
                $target="schatten";
                }
                // =======================================================================
                // Killing Blow Prio oder Pitty Mechanik für Game Journalist Modus möglich
                // =======================================================================
                if ($schatten['HP']<$schaden){
                $target="schatten";
                } elseif ($spieler['HP']<$schaden){
                $target="spieler";
                }
            }
        } else { // Wenn Schatten schon tot dann Spieler als Ziel
            $target="spieler";
        }
    // =======================================================================
    // Schaden und Tot Check
    // ======================================================================= 
        // Execute Check Spieler
        if ($target=='spieler'){
        $hpProzent = $spieler['HP'] / $spieler['HPmax'];
        $executestatusspieler=false;
        if (isset($atkExecute) && $atkExecute && $hpProzent <= 0.2) {
            $schaden*=2;
            $executestatusspieler=true;
        } 
    }
        // Execute Check Schatten
        if ($target=='schatten'){
        $hpProzent = $schatten['HP'] / $schatten['HPmax'];
        $executestatusschatten=false;
        if (isset($atkExecute) && $atkExecute && $hpProzent <= 0.2) {
            $schaden*=2;
            $executestatusschatten=true;
        } 
    }
        if (isset($gegnerListe[$id][$atkStatus]) && Statuscheck($gegnerListe[$id][$atkStatus])){
                    $status=$gegnerListe[$id][$atkStatus];
                    $statusart= $status."count";
                    if ($target=='spieler'){
                    $spieler[$status]=true;
                    $spieler[$statusart]=$gegnerListe[$id][$atkDauer];
                    if ($status=='poison')
                    $log[]= $gegnerListe[$id]['Name']." hat &nbsp;<span style='color: var(--btn-bg);'>".$spieler['Name']."</span>&nbsp;<span style='color: #43a047;'> vergiftet</span>!";
                    if ($status=='bleed')
                    $log[]= $gegnerListe[$id]['Name']." hat &nbsp;<span style='color: var(--btn-bg);'>".$spieler['Name']."</span>&nbsp;<span style='color: #5a1111;'> Blutung</span>&nbsp;zugefügt!";
                    if ($status=='burn')
                    $log[]= $gegnerListe[$id]['Name']." hat &nbsp;<span style='color: var(--btn-bg);'>".$spieler['Name']."</span>&nbsp;<span style='color: #ff5722;'> verbrannt</span>!";
                    if ($status=='freeze')
                    $log[]= $gegnerListe[$id]['Name']." hat &nbsp;<span style='color: var(--btn-bg);'>".$spieler['Name']."</span>&nbsp;<span style='color: #29b6f6;'> eingefroren</span>!";
                    if ($status=='para')
                    $log[]= $gegnerListe[$id]['Name']." hat &nbsp;<span style='color: var(--btn-bg);'>".$spieler['Name']."</span>&nbsp;<span style='color: #ffb300;'> paralysiert</span>!";
                    if ($status=='stun')
                    $log[]= $gegnerListe[$id]['Name']." hat &nbsp;<span style='color: var(--btn-bg);'>".$spieler['Name']."</span>&nbsp;<span style='color: #757575;'> gestunnt</span>!";
                    }
                    if ($target=='schatten'){
                    $schatten[$status]=true;
                    $schatten[$statusart]=$gegnerListe[$id][$atkDauer];
                    if ($status=='poison')
                    $log[]= $gegnerListe[$id]['Name']." hat &nbsp;<span style='color: #9c27b0;'>".$schatten['Name']."</span>&nbsp;<span style='color: #43a047;'> vergiftet</span>!";
                    if ($status=='bleed')
                    $log[]= $gegnerListe[$id]['Name']." hat &nbsp;<span style='color: #9c27b0;'>".$schatten['Name']."</span>&nbsp;<span style='color: #5a1111;'> Blutung</span>&nbsp;zugefügt!";
                    if ($status=='burn')
                    $log[]= $gegnerListe[$id]['Name']." hat &nbsp;<span style='color: #9c27b0;'>".$schatten['Name']."</span>&nbsp;<span style='color: #ff5722;'> verbrannt</span>!";
                    if ($status=='freeze')
                    $log[]= $gegnerListe[$id]['Name']." hat &nbsp;<span style='color: #9c27b0;'>".$schatten['Name']."</span>&nbsp;<span style='color: #29b6f6;'> eingefroren</span>!";
                    if ($status=='para')
                    $log[]= $gegnerListe[$id]['Name']." hat &nbsp;<span style='color: #9c27b0;'>".$schatten['Name']."</span>&nbsp;<span style='color: #ffb300;'> paralysiert</span>!";
                    if ($status=='stun')
                    $log[]= $gegnerListe[$id]['Name']." hat &nbsp;<span style='color: #9c27b0;'>".$schatten['Name']."</span>&nbsp;<span style='color: #757575;'> gestunnt</span>!";
                    }
                }     

    // =======================================================================
    // Schaden und Tot Check
    // ======================================================================= 
        if ($target == "spieler"){
            $spieler['HP']-=$schaden;
            if ($spieler['HP']<=0){
                $kampfverloren=true; 
                $kampfstatus=false; 
                header("Location: gameover.php");
                exit; 
        } 
    }
        if ($target == "schatten"){
            $schatten['HP']-=$schaden;
            if ($schatten['HP']<=0){
                $schatten['tot']=true;
                $schatten['HP']=0;
            }  
        }
             
        
        
        // Log Eintrag

        // print_r($target);
        // print_r($schaden);

        if ($schaden>$gegnerListe[$id][$atkmax]){
            $log[]= $gegnerListe[$id]['Name']." greift ".$$target['Name']." an (<span style='color: #ff5555;'>".$schaden." kritischer Schaden</span>)";
        } else {
            $log[]= $gegnerListe[$id]['Name']." greift ".$$target['Name']." an (".$schaden." Schaden)";
        }  
    }
    }
}
    
    
// =======================================================================
// Ende Gegner ZUG
// ======================================================================= 

    // Zug und Runde erhöhen
    do {
    $zug++;
    if ($zug>= count($reihenfolge)){
        $zug=0;
        $runde++;
        }
    // Abfrage ob nächster Angreifer tot ist.
    $tot=false;
    if ($reihenfolge[$zug]['typ']=="gegner"){
        $id=$reihenfolge[$zug]['id'];
        if ($gegnerListe[$id]['tot']==true){
            $tot=true;
        }
    } elseif ($reihenfolge[$zug]['typ']=="schatten") {
        if ($schatten['tot']==true){
            $tot=true;
        }
    }
    } while ($tot==true);

    // Wenn Kampf nicht verloren gucken ob Kampf gewonnen.
    if (!$kampfverloren){
    $kampfstatus=false;
    foreach($gegnerListe as $id => $monster){
                if(!$gegnerListe[$id]['tot']){
                    $kampfstatus=true;
                }
    }
    if (!$kampfstatus){
        $kampfgewonnen=true;
        
        //GAMELOOP ZÄHLER 
        if (!isset($_SESSION['stage_wins'])) {
        $_SESSION['stage_wins'] = 0;
        }
        $_SESSION['stage_wins']++;
        
    // =======================================================================
    // Schattenmonster Abfrage
    // ======================================================================= 
        
        $bereitsGefangen = array();
            if (isset($_SESSION['Schattendex']) && !empty($_SESSION['Schattendex'])) {
                // Nur Monster IDs aus Schattendex
                $bereitsGefangen = array_column($_SESSION['Schattendex'], 'MonsterID');
            }

            $aktuelleCharID = $_SESSION['CharID']; 

            foreach($gegnerListe as $besiegt) {
                // Nur wenn das Monster tot ist und eine ID hat
                if ($besiegt['tot'] && isset($besiegt['MonsterID'])) {
                    
                    $mID = $besiegt['MonsterID'];
                    
                    // MonsterID im array von oben?
                    if (!in_array($mID, $bereitsGefangen)) {
                        
                        // Kurzer check ob das monster "fangbar" ist falls boss oder so
                        $sql = "SELECT SchattenID FROM schattenmonster WHERE MonsterID = ? LIMIT 1";
                        $stmt = mysqli_prepare($con, $sql);
                        mysqli_stmt_bind_param($stmt, "i", $mID);
                        mysqli_stmt_execute($stmt);
                        $result = mysqli_stmt_get_result($stmt);

                        if ($wert = mysqli_fetch_assoc($result)) {
                            // Treffer! Es ist fangbar und wir haben es noch nicht.
                            $gefundenSchattenID = $wert['SchattenID'];
                            $Name = $besiegt['Name'];

                            // 4. Einfügen in die Datenbank
                            $sqlInsert = "INSERT INTO charakterschatten (SchattenID, CharakterID, Name) 
                                          VALUES (?, ?, ?)";
                            
                            $stmtInsert = mysqli_prepare($con, $sqlInsert);
                            mysqli_stmt_bind_param($stmtInsert, "iis", $gefundenSchattenID, $aktuelleCharID, $Name);
                            
                            if (mysqli_stmt_execute($stmtInsert)) {
                                $nachricht = "Die Essenz von <span style='color: #9c27b0;'>$Name</span> wurde deinem Schattendex hinzugefügt!";
                                
                                // 1. Ins normale Log (für die Historie)
                                $log[] = $nachricht;
                                
                                // 2. WICHTIG: In separates Array für den Gewonnen-Screen
                                $fangmeldung[] = $nachricht;
                                
                                // Ausschluss-Liste aktualisieren
                                $bereitsGefangen[] = $mID; 
                            }
                            $CharakterID=$_SESSION['CharID'];
                            loadschattendex($con, $CharakterID);
                            }
                        }
                    }
                }
            }    
    }


// =======================================================================
// Alles wieder in die Session!
// =======================================================================

$anzahlNeu = count($log);
$neueEintraege = $anzahlNeu - $anzahlAlt;
if($neueEintraege < 0) { $neueEintraege = 0; }

     $_SESSION['Kampf'] = [
    'aktiv' => $kampfstatus,
    'runde' => $runde,
    'log' => $log,
    'zug_index' => $zug,
    'gewonnen' => $kampfgewonnen,
    'verloren' => $kampfverloren,
    'spieler' => $spieler,
    'attacken'   => $attacken ,
    'schattenmonster' => $schatten ,
    'gegner' => $gegnerListe ,
    'reihenfolge' => $reihenfolge ,
    'fangmeldung' => $fangmeldung ,
    'neueEintraege' => $neueEintraege ,
    ];


    header("Location: maingame.php");
    exit;

?>
