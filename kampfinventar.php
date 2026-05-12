<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ZURÜCK BUTTON: Schließt nur das Kampfinventar, nicht den Kampf!
if (isset($_POST['aktion']) && $_POST['aktion'] == 'back') {
    unset($_SESSION['Kampf']['inventar_offen']);
    header("Location: maingame.php");
    exit;
}

// DATEN LADEN
$inventarListe = isset($_SESSION['Inventar']) ? $_SESSION['Inventar'] : [];
$ausruestung   = isset($_SESSION['Ausruestung']) ? $_SESSION['Ausruestung'] : [];

// ZIEL-AUSWAHL (Spieler oder Schatten)
if (isset($_POST['target_select'])) {
    $selectedTarget = $_POST['target_select'];
    $_SESSION['current_target'] = $selectedTarget;
} elseif (isset($_SESSION['current_target'])) {
    $selectedTarget = $_SESSION['current_target'];
} else {
    $selectedTarget = 'Spieler';
}

// ITEM AUSWAHL
$selectedItem = null;
if (isset($_POST['itemklick'])) {
    $klickWert = $_POST['itemklick'];
    if (isset($inventarListe[$klickWert])) {
        $selectedItem = $inventarListe[$klickWert];
    }
}
?>

<div style="height: 100%; display: flex; flex-direction: column; gap: 15px; justify-content: flex-start;">

    <div class="pixel-box" style="width: 100%; padding: 10px; box-sizing: border-box; flex-shrink: 0; min-height: 0;">
        
        <div style="display:flex; justify-content:space-between; margin: 8px; border-bottom: 2px solid #333; padding-bottom:5px;">
             <span style="color: #2196f3; font-size: 0.8rem; line-height: 1.5;">ZIEL WÄHLEN</span>
        </div>

        <div style="display: flex; gap: 10px; width: 100%; padding: 0 8px;">
        <!-- Wenn Spieler gewählt  -->
            <?php $pStyle = ($selectedTarget == 'Spieler') ? 'border-right: 2px solid #ffcc00;' : 'border-right: 2px solid transparent; opacity: 0.7;'; ?>
            <div style="flex: 1; position: relative; transition: all 0.2s; <?php echo $pStyle; ?> padding: 2px;">
               <!-- Formular  -->
                <form method="POST" style="margin:0; width:100%; height:100%;">
                    <!-- Fake Button Test??? -->
                    <button type="submit" name="target_select" value="Spieler" style="all: unset; display: block; width: 100%; height: 100%; cursor: pointer;">
                        <div style="display: flex; flex-direction: column; gap: 4px;">
                            <div style="color: #ffcc00; font-size: 0.9rem; text-transform: uppercase; margin-bottom: 4px;">
                                <?php echo isset($_SESSION['Kampf']['spieler']['Name']) ? $_SESSION['Kampf']['spieler']['Name'] : 'Spieler'; ?>
                            </div>
                            <div style="display: flex; justify-content: space-between; font-size: 0.6rem; color: #ccc; background: rgba(0,0,0,0.2); padding: 2px;">
                                <span style="color: #ff5555">HP</span>
                                <span><?php echo $_SESSION['Kampf']['spieler']['HP'] ?? 0; ?> / <?php echo $_SESSION['Kampf']['spieler']['HPmax'] ?? 100; ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between; font-size: 0.6rem; color: #ccc; background: rgba(0,0,0,0.2); padding: 2px;">
                                <span style="color: #2196f3">Mana</span>
                                <span><?php echo $_SESSION['Kampf']['spieler']['Mana'] ?? 0; ?> / <?php echo $_SESSION['Kampf']['spieler']['Manamax'] ?? 50; ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between; font-size: 0.6rem; color: #ccc; background: rgba(0,0,0,0.2); padding: 2px;">
                                <span style="color: #ff9800">Ausdauer</span>
                                <span><?php echo $_SESSION['Kampf']['spieler']['Ausdauer'] ?? 0; ?> / <?php echo $_SESSION['Kampf']['spieler']['Ausdauermax'] ?? 50; ?></span>
                            </div>
                        </div>
                    </button>
                </form>
            </div>

            <div style="width: 2px; background-color: #333; margin: 0 5px;"></div>

            <?php 
                $hasShadow = isset($_SESSION['Kampf']['schattenmonster']['Name']) && !empty($_SESSION['Kampf']['schattenmonster']['Name']);
                $sStyle = ($selectedTarget == 'Schattenmonster') ? 'border-left: 2px solid #9c27b0;' : 'border-left: 2px solid transparent; opacity: 0.5;';
            ?>
            <div style="flex: 1; position: relative; transition: all 0.2s; <?php echo $sStyle; ?> padding: 2px;">
                <?php if($hasShadow): ?>
                <form method="POST" style="margin:0; width:100%; height:100%;">
                    <button type="submit" name="target_select" value="Schattenmonster" style="all: unset; display: block; width: 100%; height: 100%; cursor: pointer;">
                        <div style="display: flex; flex-direction: column; gap: 4px; padding-right: 16px">
                            <div style="color: #9c27b0; font-size: 0.9rem; text-transform: uppercase; margin-bottom: 4px;">
                                <?php echo $_SESSION['Kampf']['schattenmonster']['Name']; ?>
                            </div>
                            <div style="display: flex; justify-content: space-between; font-size: 0.6rem; color: #ccc; background: rgba(0,0,0,0.2); padding: 2px;">
                                <span>HP</span>
                                <span>
                                    <?php echo isset($_SESSION['Kampf']['schattenmonster']['HP']) ? $_SESSION['Kampf']['schattenmonster']['HP'] : '-'; ?> /
                                    <?php echo isset($_SESSION['Kampf']['schattenmonster']['HPmax']) ? $_SESSION['Kampf']['schattenmonster']['HPmax'] : '-'; ?>
                                </span>
                            </div>
                            <div style="padding: 2px; font-size: 0.6rem;">&nbsp;</div>
                            <div style="padding: 2px; font-size: 0.6rem;">&nbsp;</div>
                        </div>
                    </button>
                </form>
                <?php else: ?>
                    <div style="color: #555; font-size: 0.8rem; padding-top: 10px; text-align: center;">Kein Schatten</div>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <div class="pixel-box" style="width: 100%; padding: 10px; box-sizing: border-box; height: auto !important; min-height: 0 !important;">
        
        <div style="display:flex; justify-content:space-between; margin: 8px; border-bottom: 2px solid #333; padding-bottom:5px;">
            <span style="color: var(--btn-bg); font-size: 0.8rem; line-height: 1.5;">DEINE TASCHE</span>
            <form method="POST" style="margin:0;">
                <button type="submit" name="aktion" value="back" class="pixel-btn" style="padding: 1px 5px; font-size: 0.6rem;">X</button>
            </form>
        </div>
        
        <div style="display: flex; margin: 8px; gap: 15px; height: 200px !important;">
            
            <div style="width: 45%; display: flex; flex-direction: column; height: 100% !important; position: relative;">
                
                <?php if ($selectedItem): ?>
                    <div class="log-inset-panel" 
                         style="position: absolute; top: -5px; left: 0; width: 100%; height: calc(100% + 5px); z-index: 10; margin: 0; padding: 10px; box-sizing: border-box; display: flex; flex-direction: column; gap: 10px;">
                        
                        <div style="text-align: center; margin-top: 20px;">
                            <div style="font-size: 2rem; margin-bottom: 5px;">
                                <?php 
                                    if (isset($selectedItem['Gegenstandsart']) && $selectedItem['Gegenstandsart'] == 'Trank') echo '🧪';
                                    else echo '📦';
                                ?>
                            </div>
                            <div style="color: #ffcc00; font-size: 0.9rem; text-transform: uppercase;">
                                <?php echo $selectedItem['Name']; ?>
                            </div>
                            <div style="color: #888; font-size: 0.6rem; margin-top: 2px;">
                                <?php echo isset($selectedItem['Gegenstandsart']) ? $selectedItem['Gegenstandsart'] : ''; ?>
                            </div>
                            <div style="font-size: 0.6rem; color: #4caf50; margin-top: 5px;">
                                <?php 
                                    if(isset($selectedItem['HP']) && $selectedItem['HP'] > 0) echo "+".$selectedItem['HP']." HP ";
                                    if(isset($selectedItem['Mana']) && $selectedItem['Mana'] > 0) echo "+".$selectedItem['Mana']." MP ";
                                ?>
                            </div>
                        </div>

                        <?php 
                            // Prüfen, ob Item nutzbar ist
                            $art = isset($selectedItem['Gegenstandsart']) ? $selectedItem['Gegenstandsart'] : '';
                            $isUsable = ($art == 'Gebrauch' || $art == 'Trank');
                        ?>

                        <!-- ============================================
                        Übergabe an die Logik 
                        ============================================= -->

                        <div style="margin-top: auto; width: 100%; display: flex; gap: 5px;">
                            <?php if ($isUsable): ?>
                                <form action="kampflogik.php" method="POST" style="flex: 1; margin: 0;">
                                    <input type="hidden" name="itemname" value="<?php echo $selectedItem['Name']; ?>">
                                    <input type="hidden" name="target" value="<?php echo $selectedTarget; ?>">
                                    <button type="submit" name="aktion" value="itembenutzen" class="pixel-btn" style="width: 100%; font-size: 0.6rem; padding: 8px;">
                                        BENUTZEN
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
            
            <div style="width: 55%; display: flex; flex-direction: column; height: 100% !important;">
                
                <div style="font-size: 0.7rem; color: #888; margin-bottom: 4px;">RUCKSACK:</div>
                
                <div class="log-scroll-box" style="width: 100%; height: 100% !important; gap: 3px; overflow-y: auto !important;">
                    <?php if (empty($inventarListe)): ?>
                        <div style="text-align: center; color: #555; margin-top: 40px;">Leer</div>
                    <?php else: ?>
                        <?php foreach($inventarListe as $item): ?>
                            
                            <?php 
                                // FILTER: Nur Gebrauch/Tränke anzeigen!
                                $art = isset($item['Gegenstandsart']) ? $item['Gegenstandsart'] : '';
                                if ($art != 'Gebrauch' && $art != 'Trank') continue;
                                if ($item['Name'] == '*Gold') continue;
                            ?>
                            <!-- ============================================
                             Item Fakey Buttons --- all:unset
                            ============================================= -->
                            <form method="POST" style="margin: 0;">
                                <button type="submit" name="itemklick" value="<?php echo $item['Name']; ?>" style="all: unset; display: block; width: 100%; cursor: pointer; text-align: left;">
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
            </div>

        </div>
    </div>

</div>