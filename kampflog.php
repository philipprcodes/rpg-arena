<style>
    /* Radio Button verstecken? */
    .target-radio {
        display: none;
    }

    /* Standard Aussehen der Gegner-Box (Label) */
    .enemy-select-label {
        cursor: pointer;
        border: 2px solid transparent; /* Platzhalter für Border */
        box-sizing: border-box;
        opacity: 0.6;
        transition: all 0.2s;
        display: flex;
        justify-content: flex-end;
        /* Deine Styles von .enemy-slot übernehmen wir unten */
    }

    /* Hover Effekt */
    .enemy-select-label:hover {
        opacity: 0.8;
    }

    /* Wenn Radio-Button "checked" */
    .target-radio:checked + .enemy-select-label {
        border-right: 2px solid #ff5555; /* Roter Rahmen für Gegner */
        opacity: 1;
        box-shadow: 0 0 10px rgba(255, 0, 0, 0.2);
    }

    /* Wenn tot */
    .dead-state {
        filter: grayscale(100%);
        opacity: 0.3 !important;
        cursor: not-allowed;
        pointer-events: none; /* Nicht klickbar */
    }

    .log-fade-in {
  opacity: 0; /* Startzustand: Unsichtbar */
  animation: alphaSlide 2.6s ease-out forwards;
}

@keyframes alphaSlide {
  0% {
    opacity: 0;
    transform: translateX(-10px); /* Startet 10px weiter links */
  }
  100% {
    opacity: 1;
    transform: translateX(0); /* Endet an der Originalposition */
  }
}

</style>

<?php
// === LOGIK: KAMPF BEENDEN ===
if (isset($_POST['aktion']) && $_POST['aktion'] == 'beenden') {
    unset($_SESSION['Kampf']); 
    header("Location: maingame.php"); 
    exit;
}




$kampfDaten = $_SESSION['Kampf'];
$spieler = $kampfDaten['spieler'];
$gegnerListe = $kampfDaten['gegner'];
$attacken = $kampfDaten['attacken'];
$schatten = $kampfDaten['schattenmonster'];
$runde = $kampfDaten['runde'];
$zugIndex = $kampfDaten['zug_index'];
$aktuellerAkteur = $kampfDaten['reihenfolge'][$zugIndex];
$istSpielerDran = ($aktuellerAkteur['typ'] == 'spieler');
$istSchattenDran = ($aktuellerAkteur['typ'] == 'schatten');


$hasShadow = !empty($schatten['SchattenID']); 
$schattenAttacken = []; 

// Schatten Attacken laden
if ($istSchattenDran && $hasShadow) {
    for ($i = 1; $i <= 3; $i++) {
        if (isset($schatten["Attacke{$i}_Name"]) && !empty($schatten["Attacke{$i}_Name"])) {
            $schattenAttacken[] = [
                'id'   => $i, 
                'Name' => $schatten["Attacke{$i}_Name"],
                'Min'  => $schatten["A{$i}_MinSchaden"],
                'Max'  => $schatten["A{$i}_MaxSchaden"]
            ];    
        }
    }
}

// Log vorbereiten
if (isset($kampfDaten['log'])) {
    $logGesamt = $kampfDaten['log'];
} else {
    $logGesamt = [];
}
// Nur die letzten 5 Einträge
$log = array_slice(array_reverse($logGesamt), 0, 5);

$maxHP = isset($spieler['HPmax']) ? $spieler['HPmax'] : $spieler['HP']; 
if($maxHP == 0) $maxHP = 1;
$prozentHP = ($spieler['HP'] / $maxHP) * 100;
?>
<!-- -------------------------------------- 
---- HTML PART ----------------------------
--------------------------------------- -->

<form action="kampflogik.php" method="POST" style="height: 100%; display: flex; flex-direction: column; justify-content: center; gap: 15px;">

    <div class="pixel-box" style="min-height: auto !important; height: auto !important; width: 100%; padding: 15px; box-sizing: border-box;">
        
        <div class="combat-grid">
            
            <div class="col-left">
                
                <div class="ally-slot" style="justify-content: center;">
                    <div style="display: flex; flex-direction: row; align-items: center; gap: 20px; width: 100%;">
                        <div style="width: 150px; height: 150px; margin-left: 15px; display: flex; align-items: center; justify-content: center;">
                            <?php 
                                $mImg = isset($spieler['Bild']) && !empty($spieler['Bild']) ? $spieler['Bild'] : 'spieler_default.png';
                            ?>
                            <img src="Images/Character/<?php echo $mImg; ?>" class="pixel-img" alt="Charakter">
                            </div>
                        <div style="display: flex; flex-direction: column; justify-content: center;">
                            <div style="color: var(--btn-bg); font-size: 1.1rem; text-transform: uppercase; margin-bottom: 1px; font-weight: bold; letter-spacing: 2px;">
                                <?php echo $spieler['Name']; ?>
                            </div>
                            <!-- </div> -->
                            <!-- // -------------------------------------------------------------------------
                            // Status Buttons
                            // -------------------------------------------------------------------------- -->
                            <div class="status-container" style="justify-content: flex-start;">
                            <?php 
                            // Burn Check
                            if (isset($spieler['burn']) && $spieler['burn'] == true) {
                                echo '<span class="status-badge badge-burn">BRN</span>';
                            }
                            // Freeze Check
                            if (isset($spieler['freeze']) && $spieler['freeze'] == true) {
                                echo '<span class="status-badge badge-freeze">FRZ</span>';
                            }
                            // Bleed Check
                            if (isset($spieler['bleed']) && $spieler['bleed'] == true) {
                                echo '<span class="status-badge badge-bleed">BLD</span>';
                            }
                            // Poison Check
                            if (isset($spieler['poison']) && $spieler['poison'] == true) {
                                echo '<span class="status-badge badge-poison">PSN</span>';
                            }
                            // Para Check
                            if (isset($spieler['para']) && $spieler['para'] == true) {
                                echo '<span class="status-badge badge-para">PAR</span>';
                            }
                            // Stun Check
                            if (isset($spieler['stun']) && $spieler['stun'] == true) {
                                echo '<span class="status-badge badge-stun">STN</span>';
                            }
                            ?>
                            </div>
                            <?php 
                            $hp = $spieler['HP'];
                            $hpMax = $spieler['HPmax'];
                            $hpProzent = ($hpMax > 0) ? ($hp / $hpMax) * 100 : 0;
                            $mana = $spieler['Mana'];
                            $manaMax = isset($spieler['Manamax']) ? $spieler['Manamax'] : 100; 
                            $manaProzent = ($manaMax > 0) ? ($mana / $manaMax) * 100 : 0;
                            $ausdauer = $spieler['Ausdauer'];
                            $ausdauerMax = isset($spieler['Ausdauermax']) ? $spieler['Ausdauermax'] : 100;
                            $ausdauerProzent = ($ausdauerMax > 0) ? ($ausdauer / $ausdauerMax) * 100 : 0;
                            ?>

                            <div class="stat-row">
                                <div class="tiny-bar"><div class="bar-fill-hp" style="width: <?php echo $hpProzent; ?>%;"></div></div>
                                <div class="stat-text-side" style="color: #4caf50;"><?php echo $hp." / ".$hpMax." HP"; ?></div>
                            </div>
                            <div class="stat-row">
                                <div class="tiny-bar" style="border-color: #1565c0;"><div class="bar-fill-mana" style="width: <?php echo $manaProzent; ?>%;"></div></div>
                                <div class="stat-text-side" style="color: #2196f3;"><?php echo $mana." / ".$manaMax." Mana"; ?></div>
                            </div>
                            <div class="stat-row">
                                <div class="tiny-bar" style="border-color: #ef6c00;"><div class="bar-fill-stamina" style="width: <?php echo $ausdauerProzent; ?>%;"></div></div>
                                <div class="stat-text-side" style="color: #ff9800;"><?php echo $ausdauer." / ".$ausdauerMax." Ausdauer"; ?></div>
                            </div>

                            <div style="color: #fff; font-size: 0.6rem; margin-top: 10px; font-weight: bold; <?php echo $istSpielerDran ? 'animation: blink 1s infinite;' : 'visibility: hidden;'; ?>">
                                ▲ DEIN ZUG
                            </div>
                        </div>
                    </div>
                </div>

                <div class="ally-slot" style="border-bottom: none; justify-content: center;">
                    <?php if ($hasShadow): 
                        $sHP = $schatten['HP'];
                        $sHPmax = $schatten['HPmax'];
                        $sHPProzent = ($sHPmax > 0) ? ($sHP / $sHPmax) * 100 : 0;
                        $sDisplayHP = ($sHP < 0) ? 0 : $sHP;
                        $sBarWidth = ($sDisplayHP > 100) ? 100 : $sDisplayHP; 
                    ?>
                        <div style="display: flex; flex-direction: row; align-items: center; gap: 20px; width: 100%;">
                        <!-- ======================================================================
                        // Schattenmonster Bild Testlauf
                        // ==================================================================== -->
                            <div style="width: 150px; height: 150px; margin-left: 15px; display: flex; align-items: center; justify-content: center;">
                            <?php 
                                $mImg = isset($schatten['Bild']) && !empty($schatten['Bild']) ? $schatten['Bild'] : 'monster_default.png';
                            ?>
                            <img src="Images/Monsters/<?php echo $mImg; ?>" class="pixel-img" alt="Schatten">
                            </div>
                            
                            <div style="display: flex; flex-direction: column; justify-content: center;">
                                <div style="color: #9c27b0; font-size: 1.1rem; text-transform: uppercase; margin-bottom: 1px; font-weight: bold; letter-spacing: 2px;">
                                    <?php echo $schatten['Name']; ?>
                                </div>
                                <div class="status-container" style="justify-content: flex-start;">
                            <?php 
                            // Burn Check
                            if (isset($schatten['burn']) && $schatten['burn'] == true) {
                                echo '<span class="status-badge badge-burn">BRN</span>';
                            }
                            // Freeze Check
                            if (isset($schatten['freeze']) && $schatten['freeze'] == true) {
                                echo '<span class="status-badge badge-freeze">FRZ</span>';
                            }
                            // Bleed Check
                            if (isset($schatten['bleed']) && $schatten['bleed'] == true) {
                                echo '<span class="status-badge badge-bleed">BLD</span>';
                            }
                            // Poison Check
                            if (isset($schatten['poison']) && $schatten['poison'] == true) {
                                echo '<span class="status-badge badge-poison">PSN</span>';
                            }
                            // Para Check
                            if (isset($schatten['para']) && $schatten['para'] == true) {
                                echo '<span class="status-badge badge-para">PAR</span>';
                            }
                            // Stun Check
                            if (isset($schatten['stun']) && $schatten['stun'] == true) {
                                echo '<span class="status-badge badge-stun">STN</span>';
                            }
                            ?>
                            </div>
                                <div class="stat-row">
                                    <div class="tiny-bar"><div class="bar-fill-shadow" style="width: <?php echo $sHPProzent; ?>%;"></div></div>
                                    <div class="stat-text-side" style="color: #9c27b0;"><?php echo $sDisplayHP." / ".$schatten['HPmax']; ?> HP</div>
                                </div>
                                <?php if ($schatten['tot']): ?>
                                    <div style="color: red; font-weight: bold; font-size: 0.9rem; margin-top: 10px; border: 2px solid red; padding: 2px 5px; transform: rotate(-5deg); display: inline-block; text-align: center;">TOT</div>
                                <?php else: ?>
                                    <div style="color: #9c27b0; font-size: 0.6rem; margin-top: 10px; font-weight: bold; <?php echo $istSchattenDran ? 'animation: blink 1s infinite;' : 'visibility: hidden;'; ?>">
                                        ▲ ZUG
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div style="display: flex; align-items: center; gap: 20px; opacity: 0.3;">
                            <div class="char-img-placeholder">👤</div>
                            <div style="font-size: 1rem;">Kein Schatten</div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <!-- ======================================================================
            // Rechte Box für Gegner
            // ==================================================================== -->
            <div class="col-right">
                <?php 
                // Default Ziel (Erster lebender Gegner)
                $defaultZielKey = null;
                foreach($gegnerListe as $key => $m) {
                    if (!$m['tot']) {
                        $defaultZielKey = $key;
                        break; 
                    }
                }
                // Gegner Schleife            
                for($i = 0; $i < 3; $i++): 
                    $gegnerwerte = array_values($gegnerListe); 
                    
                    if(isset($gegnerwerte[$i])):
                        $monster = $gegnerwerte[$i];
                        $Key = array_search($monster, $gegnerListe);
                        
                        // ID für Label-Zuordnung erstellen dead-state wenn tot
                        $radioID = "ziel_option_" . $Key;
                        $isDeadClass = $monster['tot'] ? 'dead-state' : '';
                        
                        // Prüfen ob dies das aktive Ziel sein soll
                        $isChecked = ($Key == $defaultZielKey && !$monster['tot']) ? 'checked' : '';
                ?>
                    <!-- ======================================================================
                    // Radio Buttons erstellen
                    // ==================================================================== -->
                    <?php if (!$monster['tot'] && ($istSpielerDran || $istSchattenDran)): ?>
                        <input type="radio" 
                               name="ziel" 
                               id="<?php echo $radioID; ?>" 
                               value="<?php echo $Key; ?>" 
                               class="target-radio" 
                               <?php echo $isChecked; ?>>
                    <?php endif; ?>
                    <!-- label Verknüpfung mit id -->    
                    <label for="<?php echo $radioID; ?>" class="enemy-slot enemy-select-label <?php echo $isDeadClass; ?>" style="justify-content: flex-end;">
                        
                        <div style="text-align: right; flex-grow: 1;">
                            <div style="font-size: 0.9rem; margin-bottom: 1px; color: #fff;">
                                <?php echo $monster['Name']; ?>
                            </div>
                            
                            <div class="status-container">
                            <?php 
                            if (isset($monster['burn']) && $monster['burn']) echo '<span class="status-badge badge-burn">BRN</span>';
                            if (isset($monster['freeze']) && $monster['freeze']) echo '<span class="status-badge badge-freeze">FRZ</span>';
                            if (isset($monster['bleed']) && $monster['bleed']) echo '<span class="status-badge badge-bleed">BLD</span>';
                            if (isset($monster['poison']) && $monster['poison']) echo '<span class="status-badge badge-poison">PSN</span>';
                            if (isset($monster['para']) && $monster['para']) echo '<span class="status-badge badge-para">PAR</span>';
                            if (isset($monster['stun']) && $monster['stun']) echo '<span class="status-badge badge-stun">STN</span>';
                            ?>
                            </div>
                            
                            <div style="display: inline-block; width: 100px; height: 10px; background: #111; border: 1px solid #555;">
                                <?php 
                                    $mHP = $monster['HP'];
                                    $mHPmax = $monster['HPmax'];
                                    $mHPProzent = ($mHPmax > 0) ? ($mHP / $mHPmax) * 100 : 0;
                                    $mDisplayHP = ($mHP < 0) ? 0 : $mHP;
                                ?>
                                <div style="width: <?php echo $mHPProzent; ?>%; height: 100%; background: #d32f2f;"></div>
                            </div>
                            <div style="font-size: 0.6rem; color: #aaa; margin-top: 4px;">
                                <?php echo ($monster['HP'] > 0) ? $mDisplayHP." / ".$monster['HPmax']." HP" : "Besiegt"; ?>
                            </div>
                        </div>

                        <div style="width: 112px; height: 112px; margin-left: 15px; display: flex; align-items: center; justify-content: center;">
                            <?php 
                                $mImg = isset($monster['Bild']) && !empty($monster['Bild']) ? $monster['Bild'] : 'monster_default.png';
                            ?>
                            <img src="Images/Monsters/<?php echo $mImg; ?>" class="pixel-img" alt="Gegner" style="<?php echo $monster['tot'] ? 'filter: grayscale(100%);' : ''; ?>">
                        </div>
                        
                    </label>

                <?php else: ?>
                    <div class="enemy-slot" style="opacity: 0.1; justify-content: flex-end;">
                        <div style="flex-grow: 1; text-align: right; font-size: 0.8rem;">- Leer -</div>
                        <div style="font-size: 50px; margin-left: 15px;">💀</div>
                    </div>
                <?php endif; ?>
                <?php endfor; ?>
            </div>
        </div>
    </div>

<!-- ======================================================================
// Log Box
// ==================================================================== -->

    <div class="pixel-box" style="min-height: auto !important; height: auto !important; width: 100%; display: flex; flex-direction: row; justify-content: space-between; align-items: stretch; padding: 15px; box-sizing: border-box; gap: 15px;">
        
        <div class="log-inset-panel" style="width: 25%; min-width: 25%; flex-shrink: 0; flex-direction: column; justify-content: center;">
            <span style="font-size: 1.0rem;">Runde</span>
            <span style="font-size: 1.2rem; color: var(--btn-bg); font-weight: bold; margin-top: 5px;"><?php echo $runde; ?></span>
        </div>

        <div class="log-scroll-box" style="width: 70%; min-width: 0;">
            <?php 
    
            $neueAnzahl = isset($_SESSION['Kampf']['neueEintraege']) ? $_SESSION['Kampf']['neueEintraege'] : 1;

            for($i = 0; $i < 5; $i++) {
                if(isset($log[$i])) {
                    
                    if ($i < $neueAnzahl) {
                        
                        $delay = ($neueAnzahl - 1 - $i) * 0.3;
                        
                        echo '<div class="log-inset-panel log-line log-fade-in" style="margin-bottom: 0; animation-delay: '.$delay.'s;">';
                        echo '> ' . $log[$i];
                        echo '</div>';
                    } else {
                        // Alte Einträge werden sofort und ohne Animation angezeigt
                        echo '<div class="log-inset-panel log-line" style="margin-bottom: 0;">> ' . $log[$i] . '</div>';
                    }
                } else {
                    // Leere Zeilen für die Optik
                    echo '<div class="log-inset-panel log-line" style="margin-bottom: 0; opacity: 0;">-</div>';
                }
            }

            $_SESSION['Kampf']['neue_eintraege'] = 0;
            ?>
        </div>

    </div>

<!-- ======================================================================
// Button Box
// ==================================================================== -->

    <div class="pixel-box" style="min-height: auto !important; height: auto !important; width: 100%; padding: 15px; box-sizing: border-box;">
        <div style="display: grid; grid-template-columns: 1fr 1fr; grid-template-rows: 1fr 1fr 1fr; gap: 15px;">
            <?php 
            for ($k = 0; $k < 3; $k++) {
                $btnText = "---"; 
                $btnValue = ""; 
                $btnSub = ""; 
                $disabled = "disabled"; 
                $style = ""; // Style Variable für rote Schrift bei zu wenig Mana

                // === 1. SPIELER ZUG ===
                if ($istSpielerDran && isset($attacken[$k])) {
                    $btnText = $attacken[$k]['Name'];
                    $btnValue = $k;
                    
                    // Kosten abrufen (Wenn nicht vorhanden, dann 0)
                    $manaKosten = isset($attacken[$k]['Manakosten']) ? $attacken[$k]['Manakosten'] : 0;
                    $ausdauerKosten = isset($attacken[$k]['Ausdauerkosten']) ? $attacken[$k]['Ausdauerkosten'] : 0;

                    // Infos für den Button-Text (Schaden + Kosten)
                    $btnSub = "(" . $attacken[$k]['MinSchaden'] . "-" . $attacken[$k]['MaxSchaden'] . " dmg)";
                    if($manaKosten > 0) $btnSub .= " | " . $manaKosten . " MP";
                    if($ausdauerKosten > 0) $btnSub .= " | " . $ausdauerKosten . " AP";

                    // --- DER CHECK ---
                    // Haben wir GENUG Ressourcen?
                    if ($spieler['Mana'] >= $manaKosten && $spieler['Ausdauer'] >= $ausdauerKosten) {
                        $disabled = ""; // Genug da -> Button aktiv!
                    } else {
                        $disabled = "disabled"; // Zu wenig -> Button aus!
                        // $style = "color: #ff5555; border-color: #ff5555;"; // Optional: Rot färben
                        $btnSub .= " (Zu wenig Mana/Ausdauer!)";
                    }
                } 
                
                // === 2. SCHATTEN ZUG (Verbraucht aktuell keine Ressourcen) ===
                elseif ($istSchattenDran && isset($schattenAttacken[$k])) {
                    $btnText = $schattenAttacken[$k]['Name'];
                    $btnValue = $schattenAttacken[$k]['id'];
                    $btnSub = "(" . $schattenAttacken[$k]['Min'] . "-" . $schattenAttacken[$k]['Max'] . " dmg)";
                    $disabled = ""; 
                }

                // Button ausgeben
                echo '<button type="submit" name="aktion" value="'.$btnValue.'" class="pixel-btn action-btn" style="width: 100%; '.$style.'" '.$disabled.'>';
                echo $btnText;
                echo '<br><span style="font-size: 0.5rem; opacity: 0.7;">'.$btnSub.'</span>';
                echo '</button>';
            }
            ?>

            <?php 
                $itemDisabled = ($istSpielerDran) ? "" : "disabled";
                $itemStyle = ($istSpielerDran) ? "" : "opacity: 0.5; cursor: not-allowed;";
            ?>
            <button type="submit" name="aktion" value="kampfinventar" formaction="maingame.php" class="pixel-btn action-btn" style="width: 100%; <?php echo $itemStyle; ?>" <?php echo $itemDisabled; ?>>
                👜 GEGENSTÄNDE<br><span style="font-size: 0.5rem; opacity: 0.7;">(Item nutzen)</span>
            </button>
            <?php 
            $gegnerDisabled = ($istSpielerDran || $istSchattenDran) ? "disabled" : "";
            $gegnerStyle = (!$istSpielerDran && !$istSchattenDran) ? "background-color: #8b0000; color: #fff; border-color: #ff5555;" : "";
            ?>
            <button type="submit" name="aktion" value="gegner" class="pixel-btn action-btn" style="width: 100%; <?php echo $gegnerStyle; ?>" <?php echo $gegnerDisabled; ?>>
                ⚠️ GEGNER ZUG<br><span style="font-size: 0.5rem; opacity: 0.7;">(Berechnen)</span>
            </button>
            <button type="submit" name="aktion" value="beenden" formaction="maingame.php" class="pixel-btn action-btn" style="width: 100%;">
                🚩 KAMPF ENDE<br><span style="font-size: 0.5rem; opacity: 0.7;">(Aufgeben)</span>
            </button>
        </div>
    </div>

</form>
</html>