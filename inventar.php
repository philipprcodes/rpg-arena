<?php
// =======================================================================
// 1. PHP INITIALISIERUNG & LOGIK
// =======================================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kontext bestimmen: Sind wir im Loot-Fenster oder im Inventar/Schatten-Menü?
if (isset($_SESSION['inventarart'])) {
    $context = $_SESSION['inventarart'];
} else {
    $context = 'loot';
}

// --- AKTION: ALLES NEHMEN (Loot) ---
if (isset($_POST['aktion']) && $_POST['aktion'] == 'take_all') {
    $_SESSION['Kampf']['hat_gelootet'] = true;
    unset($_SESSION['inv_context']);
    echo "<script>window.location.href='maingame.php';</script>";
    exit;
}

// --- AKTION: ZURÜCK / SCHLIESSEN ---
if (isset($_POST['aktion']) && $_POST['aktion'] == 'back') {
    unset($_SESSION['inventar']); 
    unset($_SESSION['inventarart']);
    echo "<script>window.location.href='maingame.php';</script>";
    exit;
}

// --- DATEN LADEN ---
// Loot aus der Session holen falls benötigt
if (isset($_SESSION['Kampf']['loot'])) {
    $loot = $_SESSION['Kampf']['loot'];
} else {
    $loot = array();
}
$CharID = isset($_SESSION['CharID']) ? $_SESSION['CharID'] : 0;

// Ausrüstung holen
if (isset($_SESSION['Ausruestung'])) {
    $ausruestung = $_SESSION['Ausruestung'];
} 

// Listen vorbereiten (Inventar oder Schattendex)
$inventarListe = isset($_SESSION['Inventar']) ? $_SESSION['Inventar'] : [];
$schattenListe = isset($_SESSION['Schattendex']) ? $_SESSION['Schattendex'] : [];


// --- LOGIK: ITEM ODER MONSTER ANGEKLICKT? ---
$selectedItem = null;

if (isset($_POST['itemklick'])) {
    $klickWert = $_POST['itemklick'];
    
    // FALL A: SCHATTENDEX MODUS
    if ($context == 'schatten') {
        // Wir prüfen, ob der Index im Schattendex existiert
        if (isset($schattenListe[$klickWert])) {
            $selectedItem = $schattenListe[$klickWert];
            
            // Wichtig für das Ausrüsten später
            $selectedItem['SessionIndex'] = $klickWert;

            // Dummy-Werte setzen, damit die Anzeige (Overlay) nicht abstürzt,
            // da Schatten keine "WaffenID" oder "Gegenstandsart" haben.
            $selectedItem['WaffenID'] = 0;
            $selectedItem['GegenstandsID'] = 0;
            $selectedItem['Gegenstandsart'] = 'Schatten'; 
        }
    } 
    // FALL B: NORMALES INVENTAR
    else {
        if (isset($inventarListe[$klickWert])) {
            $selectedItem = $inventarListe[$klickWert];
        }
    }
}

// --- LOGIK: ZIEL AUSWAHL (Spieler oder Schattenmonster) ---
if (isset($_POST['target_select'])) {
    $selectedTarget = $_POST['target_select'];
    $_SESSION['current_target'] = $selectedTarget;
} elseif (isset($_SESSION['current_target'])) {
    $selectedTarget = $_SESSION['current_target'];
} else {
    $selectedTarget = 'Spieler'; // Standard ist immer der Spieler
}
?>

<html>
<!-- =======================================================================
 DESIGN ABSCHNITT
======================================================================= -->
<div style="height: 100%; display: flex; flex-direction: column; gap: 15px; justify-content: flex-start;">

    <!-- =======================================================================
    1. Box mit Status Spieler und Schattenmonster 
    ======================================================================= -->
        <div class="pixel-box" style="width: 100%; padding: 10px; box-sizing: border-box; flex-shrink: 0; min-height: 0;"> 
            <!-- Überschrift -->
            <div style="display:flex; justify-content:space-between; margin: 8px; border-bottom: 2px solid #333; padding-bottom:5px;">
            <span style="color: #2196f3; font-size: 0.8rem; line-height: 1.5;">STATUS (Ziel wählen)</span>
            </div>
            <!-- Box für Spieler und Schattenmonster -->
            <div style="display: flex; gap: 10px; width: 100%; padding: 0 8px;">
            <!-- php Code für wenn Spieler ausgewählt ist -->
            <?php 
            $pStyle = ($selectedTarget == 'Spieler') ? 'border-right: 2px solid #ff9800;' : 'border-right: 2px solid transparent; opacity: 0.7;';
            ?>
            <!-- Box um den Stil anzuwenden  -->
            <div style="flex: 1; position: relative; transition: all 0.2s; <?php echo $pStyle; ?> padding: 2px;">
                <!-- Formular als Hülle für Button zum übermitteln vom target. Button mit all:unset ohne blöde Standard Styles  -->
                <form method="POST" style="margin:0; width:100%; height:100%;">
                <button type="submit" name="target_select" value="Spieler" style="all: unset; display: block; width: 100%; height: 100%; cursor: pointer;">
                        <div style="display: flex; flex-direction: column; gap: 4px;">
                            <div style="color: #ff9800; font-size: 0.9rem; text-transform: uppercase; margin-bottom: 4px;">
                                <?php echo isset($_SESSION['Spieler']['Name']) ? $_SESSION['Spieler']['Name'] : 'Spieler'; ?>
                            </div>
                            <div style="display: flex; justify-content: space-between; font-size: 0.6rem; color: #ccc; background: rgba(0,0,0,0.2); padding: 2px;">
                                <span style="color: #ff5555">HP</span>
                                <span><?php echo $_SESSION['Spieler']['HP'] ?? 0; ?> / <?php echo $_SESSION['Spieler']['HPmax'] ?? 100; ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between; font-size: 0.6rem; color: #ccc; background: rgba(0,0,0,0.2); padding: 2px;">
                                <span style="color: #2196f3">Mana</span>
                                <span><?php echo $_SESSION['Spieler']['Mana'] ?? 0; ?> / <?php echo $_SESSION['Spieler']['Manamax'] ?? 50; ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between; font-size: 0.6rem; color: #ccc; background: rgba(0,0,0,0.2); padding: 2px;">
                                <span style="color: #ff9800">Ausdauer</span>
                                <span><?php echo $_SESSION['Spieler']['Ausdauer'] ?? 0; ?> / <?php echo $_SESSION['Spieler']['Ausdauermax'] ?? 50; ?></span>
                            </div>
                        </div>
                </button>
                </form>
            </div>
            <!-- Trennlinie  -->
            <div style="width: 2px; background-color: #333; margin: 0 5px;"></div>

            <?php 
            $sStyle = ($selectedTarget == 'Schattenmonster') ? 'border-left: 2px solid #9c27b0;' : 'border-left: 2px solid transparent; opacity: 0.5;';
            ?>
            <div style="flex: 1; position: relative; transition: all 0.2s; <?php echo $sStyle; ?> padding: 2px;">
                <form method="POST" style="margin:0; width:100%; height:100%;">
                <button type="submit" name="target_select" value="Schattenmonster" style="all: unset; display: block; width: 100%; height: 100%; cursor: pointer;">
                        <div style="display: flex; flex-direction: column; gap: 4px; padding-right: 16px;">
                            <div style="color: #9c27b0; font-size: 0.9rem; text-transform: uppercase; margin-bottom: 4px;">
                                <?php echo isset($_SESSION['Schattenmonster']['Name']) ? $_SESSION['Schattenmonster']['Name'] : '(Kein Schatten)'; ?>
                            </div>
                            <div style="display: flex; justify-content: space-between; font-size: 0.6rem; color: #ccc; background: rgba(0,0,0,0.2); padding: 2px;">
                                <span>HP</span>
                                <span>
                                    <?php echo isset($_SESSION['Schattenmonster']['HP']) ? $_SESSION['Schattenmonster']['HP'] : '-'; ?> /
                                    <?php echo isset($_SESSION['Schattenmonster']['HPmax']) ? $_SESSION['Schattenmonster']['HPmax'] : '-'; ?>
                                </span>
                            </div>
                            <!-- Platzhalter  -->
                            <div style="padding: 2px; font-size: 0.6rem;">&nbsp;</div>
                            <div style="padding: 2px; font-size: 0.6rem;">&nbsp;</div>
                        </div>
                </button>
                </form>
            </div>

        </div>
    </div>

    <!-- =======================================================================
    2. Box mit Ausrüstung & Tasche  
    ======================================================================= -->

    <div class="pixel-box" style="width: 100%; padding: 10px; box-sizing: border-box; height: auto !important; min-height: 0 !important;">
        
        <div style="display:flex; justify-content:space-between; margin: 8px; border-bottom: 2px solid #333; padding-bottom:5px;">
            <span style="color: var(--btn-bg); font-size: 0.8rem; line-height: 1.5;">AUSRÜSTUNG & TASCHE</span>
            <form method="POST" style="margin:0;">
                <button type="submit" name="aktion" value="back" class="pixel-btn" style="padding: 1px 5px; font-size: 0.6rem;">X</button>
            </form>
        </div>
        
        <div style="display: flex; margin: 8px; gap: 15px; height: 200px !important;">
            
            <div style="width: 45%; display: flex; flex-direction: column; height: 100% !important; position: relative;">
                    <!-- =======================================================================
                    Box über Ausrüstung wenn Item oder Schattenmonster angeklickt wird aus Inventar oder Dex 
                    ======================================================================= -->
                <?php if ($selectedItem): ?>
                    <div class="log-inset-panel" 
                         style="position: absolute; 
                                top: -5px; 
                                left: 0; 
                                width: 100%; 
                                height: calc(100% + 5px); 
                                z-index: 10; 
                                margin: 0;
                                padding: 10px; 
                                box-sizing: border-box;
                                display: flex; 
                                flex-direction: column; 
                                gap: 10px;">
                        
                        <div style="text-align: center; margin-top: 2px;">
                            <div style="font-size: 2rem; margin-bottom: 5px;">
                                <?php 
                                if (isset($selectedItem['Bild']) && !empty($selectedItem['Bild'])) {
                                 echo '<img src="Images/Monsters/' . $selectedItem['Bild'] . '" style="max-height: 40px; max-width: 40px; image-rendering: pixelated; filter: drop-shadow(2px 2px 0 #000);">';
                                }
                                    elseif (isset($selectedItem['WaffenID']) && $selectedItem['WaffenID'] > 0) echo '🗡️';
                                    elseif (isset($selectedItem['GegenstandsID']) && $selectedItem['GegenstandsID'] > 0) echo '🧪'; 
                                    else echo '📦';
                                ?>
                            </div>
                            <div style="color: #ffcc00; font-size: 0.9rem; text-transform: uppercase;">
                                <?php echo $selectedItem['Name']; ?>
                            </div>
                            <div style="color: #888; font-size: 0.6rem; margin-top: 4px;">
                               
                                <?php 
                                if(!isset($selectedItem['SchattenID'])){ 
                                echo isset($selectedItem['Gegenstandsart']) ? $selectedItem['Gegenstandsart'] : ''; 
                                } else {
                                echo $selectedItem['HPmax']." HP";    
                                }
                                ?>
                            </div>
                            
                            <div style="font-size: 0.6rem; color: #4caf50; margin-top: 5px; display: flex; flex-direction: column; gap: 3px;">
                                <?php 
                                    // Attribute für Ausrüstung
                                    if(isset($selectedItem['Staerke']) && $selectedItem['Staerke'] > 0) echo "<span>+".$selectedItem['Staerke']." Stärke</span>";
                                    if(isset($selectedItem['Intelligenz']) && $selectedItem['Intelligenz'] > 0) echo "<span>+".$selectedItem['Intelligenz']." Intelligenz</span>";
                                    if(isset($selectedItem['Geschicklichkeit']) && $selectedItem['Geschicklichkeit'] > 0) echo "<span>+".$selectedItem['Geschicklichkeit']." Geschick</span>";

                                    // Tränke Stats
                                    if(!isset($selectedItem['SchattenID'])){
                                    if(isset($selectedItem['HP']) && $selectedItem['HP'] > 0) echo "<span>+".$selectedItem['HP']." HP</span>";
                                    if(isset($selectedItem['Mana']) && $selectedItem['Mana'] > 0) echo "<span>+".$selectedItem['Mana']." Mana</span>";
                                    if(isset($selectedItem['Ausdauer']) && $selectedItem['Ausdauer'] > 0) echo "<span>+".$selectedItem['Ausdauer']." Ausdauer</span>";
                                    }
                                    // Attacken (bei Waffen)
                                    if (isset($selectedItem['Attacken']) && is_array($selectedItem['Attacken']) && !empty($selectedItem['Attacken'])) {
                                        echo "<div style='border-top: 1px solid #444; margin: 2px 0;'></div>";
                                        foreach($selectedItem['Attacken'] as $atk) {
                                            $kosten = "";
                                            if($atk['Manakosten'] > 0) $kosten .= " <span style='color:#2196f3'>".$atk['Manakosten']." MP</span>";
                                            if($atk['Ausdauerkosten'] > 0) $kosten .= " <span style='color:#ff9800'>".$atk['Ausdauerkosten']." AP</span>";

                                            echo "<div style='line-height: 1.2;'>";
                                            echo "<span style='color: #ddd;'>• ".$atk['Name']."</span>";
                                            echo " <span style='color: #888; font-size: 0.5rem;'>(".$atk['MinSchaden']."-".$atk['MaxSchaden'].")</span>";
                                            echo $kosten;
                                            echo "</div>";
                                        }
                                    }
                                    // FALL B: SCHATTENMONSTER 
                                    elseif (isset($selectedItem['SchattenID']) && $selectedItem['SchattenID'] > 0) {
                                        echo "<div style='border-top: 1px solid #444; margin: 5px 0;'></div>";
                                        // echo "<div style='text-align: left; font-size: 0.6rem; color: #888; margin-bottom: 2px; padding-left: 5px;'>ANGRIFFE:</div>";

                                        // Schleife durch Attacken
                                        for($i=1; $i<=3; $i++) {
                                            $name = "Attacke".$i."_Name";
                                            
                                            // Existiert Attackenname?
                                            if(!empty($selectedItem[$name])) {
                                                $min = "A".$i."_MinSchaden";
                                                $max = "A".$i."_MaxSchaden";

                                            $dmg = "";
                                            // Schaden formatieren (nur wenn MaxSchaden > 0)
                                            if(isset($selectedItem[$min]) && isset($selectedItem[$max]) && $selectedItem[$max] > 0) {
                                                $dmg = " <span style='color: #888; font-size: 0.6rem;'>(".$selectedItem[$min]."-".$selectedItem[$max].")</span>";
                                            }

                                            echo "<div style='line-height: 1.3; text-align: left; padding-left: 5px;'>";
                                            echo "<span style='color: #ddd;'>• " . $selectedItem[$name] . "</span>";
                                            echo $dmg;
                                            echo "</div>";
                                        }
                                    }
                                }
                                ?>
                            </div>
                        </div>
                            <!-- =======================================================================
                            Requirement Check für Waffen 
                            ======================================================================= -->
                        <?php
                            $showButton = false; 
                            $buttonText = "";    
                            $buttonValue = "";   
                            $btnStyle = "width: 100%; font-size: 0.6rem; padding: 8px;";
                            $btnDisabled = "";

                            // --- Requirements Check ---
                            $pStr = $_SESSION['Spieler']['Staerke'];
                            $pInt = $_SESSION['Spieler']['Intelligenz'];
                            $pDex = $_SESSION['Spieler']['Geschicklichkeit'];
                            $iStr = isset($selectedItem['SReq']) ? $selectedItem['SReq'] : 0;
                            $iInt = isset($selectedItem['IReq']) ? $selectedItem['IReq'] : 0;
                            $iDex = isset($selectedItem['GReq']) ? $selectedItem['GReq'] : 0;

                            $canEquip = true;
                            if ($pStr < $iStr) $canEquip = false;
                            if ($pInt < $iInt) $canEquip = false;
                            if ($pDex < $iDex) $canEquip = false;

                            $art = isset($selectedItem['Gegenstandsart']) ? $selectedItem['Gegenstandsart'] : '';
                            $waffenID = isset($selectedItem['WaffenID']) ? $selectedItem['WaffenID'] : 0;

                            // Button Logik: Was für ein Item ist es?
                            if ($waffenID > 0 || in_array($art, ['Kopf', 'Brust', 'Bein'])) {
                                $showButton = true;
                                $buttonText = "AUSRÜSTEN";
                                $buttonValue = "ausruesten";
                                if (!$canEquip) {
                                    $btnDisabled = "disabled";
                                    $buttonText = "NICHT TRAGBAR";
                                    $btnStyle .= " background-color: #333; color: #555; border-color: #444; cursor: not-allowed; box-shadow: none;";
                                }
                            }
                            elseif ($art == 'Gebrauch') { 
                                $showButton = true;
                                $buttonText = "BENUTZEN";
                                $buttonValue = "benutzen";
                            }
                            elseif ($art == 'Junk') {
                                $showButton = false;
                            }
                            // Speziell für Schattenmonster ausrüsten
                            elseif (isset($selectedItem['SchattenID']) && $selectedItem['SchattenID']>0) {
                                $showButton = true;
                                $buttonText = "AUSRÜSTEN";
                                $buttonValue = "ausruestenschatten";
                            }   
                        ?>

                        <div style="margin-top: auto; width: 100%; display: flex; gap: 5px;">
                                <!-- =======================================================================
                                Button zusammenbauen
                                ======================================================================= --> 
                            <?php if ($showButton): ?>
                                <form action="ausruesten.php" method="POST" style="flex: 1; margin: 0;">
                                    <input type="hidden" name="itemname" value="<?php echo $selectedItem['Name']; ?>">
                                    <input type="hidden" name="target" value="<?php echo $selectedTarget; ?>">
                                    <?php if (isset($selectedItem['SessionIndex'])): ?>
                                        <input type="hidden" name="schattenindex" value="<?php echo $selectedItem['SessionIndex']; ?>">
                                    <?php endif; ?>
                                    
                                    <button type="submit" name="aktion" value="<?php echo $buttonValue; ?>" 
                                            class="pixel-btn" 
                                            style="<?php echo $btnStyle; ?>" 
                                            <?php echo $btnDisabled; ?>>
                                            <?php echo $buttonText; ?>
                                    </button>
                                </form>
                            <?php else: ?>
                                <div style="flex: 1;"></div>
                            <?php endif; ?>

                            <form method="POST" style="flex: 1; margin: 0;">
                                <button class="pixel-btn" style="width: 100%; font-size: 0.6rem; padding: 8px;">
                                    EINKLAPPEN
                                </button>
                            </form>
                            
                        </div>

                    </div>
                <?php endif; ?>
                    <!-- =======================================================================
                    Box für Ausrüstung 
                    ======================================================================= -->
                <div style="font-size: 0.7rem; color: #888; margin-bottom: 4px;">GETRAGEN:</div>
                <div style="display: flex; flex-direction: column; gap: 6px; overflow-y: hidden !important; height: 100% !important;">
                    <?php for($i=1; $i<=5; $i++): ?>
                        <div class="log-inset-panel" style="width: 100%; padding: 3px 5px; justify-content: flex-start; gap: 8px; min-height: 32px !important; flex-shrink: 0; box-sizing: border-box;">
                            <span style="font-size: 0.6rem; color: #555; width: 10px;"><?php echo $i; ?></span>
                            <?php if(isset($ausruestung[$i]) && isset($ausruestung[$i]['IstLeer']) && $ausruestung[$i]['IstLeer'] == false): ?>
                                <span style="color: #fff; font-size: 0.7rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    <?php echo $ausruestung[$i]['Name']; ?>
                                </span>
                            <?php else: ?>
                                <span style="color: #555; font-size: 0.7rem;">Leer</span>
                            <?php endif; ?>
                        </div>
                    <?php endfor; ?>
                </div>

            </div>

                <!-- =======================================================================
                Box für Inventar oder Schattendex je nach übergebenem context
                ======================================================================= -->

            <div style="width: 55%; display: flex; flex-direction: column; height: 100% !important;">              

                <?php if ($context != 'schatten'): ?>
                    
                    <div style="font-size: 0.7rem; color: #888; margin-bottom: 4px;">RUCKSACK:</div>
                    
                    <div class="log-scroll-box" style="width: 100%; height: 100% !important; gap: 3px; overflow-y: auto !important;">
                        <?php if (empty($inventarListe)): ?>
                            <div style="text-align: center; color: #555; margin-top: 40px;">Leer</div>
                        <?php else: ?>
                            <?php foreach($inventarListe as $item): ?>
                                <form method="POST" style="margin: 0;">
                                    <button type="submit" name="itemklick" value="<?php echo $item['Name']; ?>" 
                                            style="all: unset; display: block; width: 100%; cursor: pointer; text-align: left;">
                                    
                                        <div class="log-inset-panel" style="margin-bottom: 3px; justify-content: space-between; min-height: 32px !important; flex-shrink: 0; padding: 3px 5px; display: flex; align-items: center;">
                                            <div style="display: flex; align-items: center; gap: 8px;">
                                                <div style="color: #ccc; font-size: 0.6rem;"><?php echo $item['Name']; ?></div>
                                            </div>
                                            <span style="font-size: 0.6rem; color: var(--btn-bg);">x<?php echo $item['Anzahl']; ?></span>
                                        </div>

                                    </button>
                                </form>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                <?php else: ?>

                    <div style="font-size: 0.7rem; color: #9c27b0; margin-bottom: 4px;">SCHATTENDEX:</div>
                    
                    <div class="log-scroll-box" style="width: 100%; height: 100% !important; gap: 3px; overflow-y: auto !important;">
                        <?php if (empty($schattenListe)): ?>
                            <div style="text-align: center; color: #555; margin-top: 40px; font-size: 0.7rem;">- Keine Schatten -</div>
                        <?php else: ?>
                            <?php foreach($schattenListe as $index => $monster): ?>
                            <form method="POST" style="margin: 0;">
                                <button type="submit" name="itemklick" value="<?php echo $index; ?>" 
                                        style="all: unset; display: block; width: 100%; cursor: pointer; text-align: left;">
                                    
                                    <div class="log-inset-panel" style="margin-bottom: 3px; justify-content: space-between; min-height: 32px !important; flex-shrink: 0; padding: 3px 5px; display: flex; align-items: center;">
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <div style="color: #9c27b0; font-size: 0.6rem;"><?php echo $monster['Name']; ?></div>
                                        </div>
                                        <div style="font-size: 0.5rem; color: #888; display: flex; gap: 5px;">
                                            <span>HP <?php echo isset($monster['HP']) ? $monster['HP'] : '?'; ?></span>
                                        </div>
                                    </div>

                                </button>
                            </form>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                <?php endif; ?>
            </div>

        </div>
    </div>

<!-- =======================================================================
Box für Loot nach Kampf
======================================================================= -->

    <?php if ($context == 'loot'): ?>
        <div class="pixel-box" style="width: 100%; padding: 10px; box-sizing: border-box; height: auto !important; min-height: 0 !important;">
            
            <div style="display:flex; justify-content:space-between; margin: 8px; border-bottom: 2px solid #333; padding-bottom:5px;">
                <span style="color: #4caf50; font-size: 0.8rem; line-height: 1.5;">GEFUNDENE BEUTE</span>
            </div>
            
            <div style="display: flex; gap: 15px; height: 120px !important; margin: 8px; align-items: stretch;">
                
                <div style="flex-grow: 1; display: flex; flex-direction: column; height: 100% !important;">
                    <div class="log-scroll-box" style="width: 100%; height: 100% !important; overflow-y: auto !important;">
                        <?php if (empty($loot)): ?>
                            <div style="text-align: center; color: #555; margin-top: 40px; font-size: 0.6rem;">
                                - Leer -
                            </div>
                        <?php else: ?>        
                            <?php foreach($loot as $lootItem): ?>
                                <div class="log-inset-panel" style="margin-bottom: 3px; justify-content: space-between; min-height: 32px !important; flex-shrink: 0; padding: 2px 5px;">
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <span style="font-size: 0.6rem; color: #ccc;"><?php echo $lootItem['Name']; ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <form action="entnahme.php" method="POST" style="width: 30%; min-width: 110px; display: flex; flex-direction: column; height: 100%;">
                    <button type="submit" name="aktion" value="Entnahme" class="pixel-btn" style="width: 100%; height: 93% !important; font-size: 0.7rem; color: #3a2c11; display: flex; align-items: center; justify-content: center; text-align: center; padding: 0;">
                        ALLES<br>NEHMEN
                    </button>
                </form>

            </div>

        </div>
    <?php endif; ?>

</div>
</html>