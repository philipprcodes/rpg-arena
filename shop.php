<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if(!isset($_SESSION['Shopinventar'])){
include "shopinventar.php";
}
// Button "Zurück" (Geht zum Hub)
if (isset($_POST['aktion']) && $_POST['aktion'] == 'back') {
    unset($_SESSION['shop']); // Shop Status beenden!
    unset($_SESSION['Shopinventar']);
    echo "<script>window.location.href='maingame.php';</script>";
    exit;
}

// -------------------------------------------------------
// DATEN LADEN
// -------------------------------------------------------

// Spieler Inventar laden
$inventarListe = isset($_SESSION['Inventar']) ? $_SESSION['Inventar'] : [];

// Ausrüstung laden
$ausruestung = isset($_SESSION['Ausruestung']) ? $_SESSION['Ausruestung'] : [];

// Shop Inventar 
$shopListe = isset($_SESSION['Shopinventar']) ? $_SESSION['Shopinventar'] : [];


// -------------------------------------------------------
// AUSWAHL LOGIK
// -------------------------------------------------------
$selectedItem = null;
$modus = ''; // 'kaufen' oder 'verkaufen'

// Klick im SHOP-Fenster
if (isset($_POST['shopklick'])) {
    $klickName = $_POST['shopklick'];
    if (isset($shopListe[$klickName])) {
        $selectedItem = $shopListe[$klickName];
        $modus = 'kaufen';
    }
}
// -------------------------------------------------------
// Shop Bild
// -------------------------------------------------------

$shop='';
if ($_SESSION['stage_level']<7) $shop="Shop1.png";
elseif ($_SESSION['stage_level']<10) $shop="shop3.png";
else $shop="shop4.png";

// KLick im SPIELER-Fenster
if (isset($_POST['itemklick'])) {
    $klickName = $_POST['itemklick'];
    if (isset($inventarListe[$klickName])) {
        $selectedItem = $inventarListe[$klickName];
        $modus = 'verkaufen';
    }
}
?>

<div style="height: 100%; display: flex; flex-direction: column; gap: 15px; justify-content: flex-start;">

    <div class="pixel-box" style="width: 100%; padding: 10px; box-sizing: border-box; height: auto !important; min-height: 0 !important;">
        
        <div style="display:flex; justify-content:space-between; margin: 8px; border-bottom: 2px solid #333; padding-bottom:5px;">
            <span style="color: gold; font-size: 0.8rem; line-height: 1.5;">HÄNDLER ANGEBOT</span>
            <form method="POST" style="margin:0;">
                <button type="submit" name="aktion" value="back" class="pixel-btn" style="padding: 1px 5px; font-size: 0.6rem;">X</button>
            </form>
        </div>

        <div style="display: flex; margin: 8px; gap: 15px; height: 200px !important;">
            
            <div style="width: 45%; display: flex; flex-direction: column; height: 100% !important; justify-content: center; align-items: center; border-right: 1px solid #333;">
                
                <?php if ($modus == 'kaufen' && $selectedItem): ?>
                    <div style="text-align: center;">
                        <div style="font-size: 2rem; margin-bottom: 10px;">💰</div>
                        <div style="color: gold; font-size: 0.8rem; text-transform: uppercase;"><?php echo $selectedItem['Name']; ?></div>
                        <div style="color: #ccc; font-size: 0.6rem; margin-top: 5px;">Preis: <?php echo $selectedItem['Gold']; ?> G</div>
                        <div style="font-size: 0.6rem; color: #4caf50; margin-top: 5px; display: flex; flex-direction: column; gap: 3px;">
                            <?php 
                                // 1. Rüstungen Stats)
                                if(isset($selectedItem['Staerke']) && $selectedItem['Staerke'] > 0) 
                                    echo "<span>+".$selectedItem['Staerke']." Stärke</span>";
                                
                                if(isset($selectedItem['Intelligenz']) && $selectedItem['Intelligenz'] > 0) 
                                    echo "<span>+".$selectedItem['Intelligenz']." Intelligenz</span>";
                                
                                if(isset($selectedItem['Geschicklichkeit']) && $selectedItem['Geschicklichkeit'] > 0) 
                                    echo "<span>+".$selectedItem['Geschicklichkeit']." Geschick</span>";

                                // 2. Tränke
                                if(isset($selectedItem['HP']) && $selectedItem['HP'] > 0) 
                                    echo "<span>+".$selectedItem['HP']." HP</span>";
                                
                                if(isset($selectedItem['Mana']) && $selectedItem['Mana'] > 0) 
                                    echo "<span>+".$selectedItem['Mana']." Mana</span>";

                                if(isset($selectedItem['Ausdauer']) && $selectedItem['Ausdauer'] > 0) 
                                    echo "<span>+".$selectedItem['Ausdauer']." Ausdauer</span>";

                                // 3. WAFFEN ANGRIFFE (Einfach unten dran hängen)
                                if (isset($selectedItem['Attacken']) && is_array($selectedItem['Attacken']) && !empty($selectedItem['Attacken'])) {
                                    
                                    // Kleine Trennlinie
                                    echo "<div style='border-top: 1px solid #444; margin: 2px 0;'></div>";

                                    foreach($selectedItem['Attacken'] as $atk) {
                                        // Kosten zusammenbauen (nur wenn > 0)
                                        $kosten = "";
                                        if($atk['Manakosten'] > 0) $kosten .= " <span style='color:#2196f3'>".$atk['Manakosten']." MP</span>";
                                        if($atk['Ausdauerkosten'] > 0) $kosten .= " <span style='color:#ff9800'>".$atk['Ausdauerkosten']." AP</span>";

                                        // Ausgabe: Name + Schaden + Kosten
                                        echo "<div style='line-height: 1.2;'>";
                                        echo "<span style='color: #ddd;'>• ".$atk['Name']."</span>";
                                        echo " <span style='color: #888; font-size: 0.5rem;'>(".$atk['MinSchaden']."-".$atk['MaxSchaden'].")</span>";
                                        echo $kosten;
                                        echo "</div>";
                                    }
                                }
                            ?>
                        </div>
                        
                        <form action="shopinteraktion.php" method="POST" style="margin-top: 15px;">
                            <input type="hidden" name="itemname" value="<?php echo $selectedItem['Name']; ?>">
                            <input type="hidden" name="aktion" value="kaufen">
                            <button type="submit" class="pixel-btn" style="width: 200px; font-size: 0.6rem; padding: 8px;">KAUFEN</button>
                        </form>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; color: #888;">
                        <img src="Images/Monsters/Shop1.png" style="max-height: 120px; max-width: 120px; image-rendering: pixelated; filter: drop-shadow(2px 2px 0 #000);">
                        <div style="font-size: 0.8rem; color: gold;">WILLKOMMEN</div>
                        <div style="font-size: 0.6rem; margin-top: 5px;">"Schau dich ruhig um!"</div>
                    </div>
                <?php endif; ?>

            </div>

            <div style="width: 55%; display: flex; flex-direction: column; height: 100% !important;">
                <div style="font-size: 0.7rem; color: #888; margin-bottom: 4px;">WAREN:</div>
                
                <div class="log-scroll-box" style="width: 100%; height: 100% !important; gap: 3px; overflow-y: auto !important;">
    <?php if (empty($shopListe)): ?>
        <div style="text-align: center; color: #555; margin-top: 40px;">Leer</div>
    <?php else: ?>
        <?php foreach($shopListe as $item): ?>
            <form method="POST" style="margin: 0;">
                <button type="submit" name="shopklick" value="<?php echo $item['Name']; ?>" style="all: unset; display: block; width: 100%; cursor: pointer; text-align: left;">
                    <div class="log-inset-panel" style="margin-bottom: 3px; justify-content: space-between; min-height: 32px !important; flex-shrink: 0; padding: 3px 5px; display: flex; align-items: center;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <div style="color: #ccc; font-size: 0.6rem;"><?php echo $item['Name']; ?></div>
                        </div>
                        
                        <span style="font-size: 0.6rem; color: gold;">
                            <?php 
                            if ($item['Name'] == '*Gold') {
                                echo $item['Anzahl']; // Zeigt Bestand
                            } else {
                                echo $item['Gold'];   // Zeigt Preis
                            }
                            ?> G
                        </span>
                        
                    </div>
                </button>
            </form>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
            </div>

        </div>
    </div>


    <div class="pixel-box" style="width: 100%; padding: 10px; box-sizing: border-box; height: auto !important; min-height: 0 !important;">
        
        <div style="display:flex; justify-content:space-between; margin: 8px; border-bottom: 2px solid #333; padding-bottom:5px;">
            <span style="color: var(--btn-bg); font-size: 0.8rem; line-height: 1.5;">DEIN INVENTAR (VERKAUFEN)</span>
        </div>
        
        <div style="display: flex; margin: 8px; gap: 15px; height: 200px !important;">
            
            <div style="width: 45%; display: flex; flex-direction: column; height: 100% !important; position: relative;">
                
                <?php if ($modus == 'verkaufen' && $selectedItem): ?>
                    <div class="log-inset-panel" style="position: absolute; top: -5px; left: 0; width: 100%; height: calc(100% + 5px); z-index: 10; margin: 0; padding: 10px; box-sizing: border-box; display: flex; flex-direction: column; gap: 10px;">
                        
                        <div style="text-align: center; margin-top: 20px;">
                            <div style="font-size: 2rem; margin-bottom: 5px;">
                                <?php 
                                    if (isset($selectedItem['WaffenID']) && $selectedItem['WaffenID'] > 0) echo '🗡️';
                                    elseif (isset($selectedItem['GegenstandsID']) && $selectedItem['GegenstandsID'] > 0) echo '🧪'; 
                                    else echo '📦';
                                ?>
                            </div>
                            <div style="color: #ffcc00; font-size: 0.9rem; text-transform: uppercase;">
                                <?php echo $selectedItem['Name']; ?>
                            </div>
                            <div style="color: #888; font-size: 0.6rem; margin-top: 2px;">
                                <?php echo isset($selectedItem['Gegenstandsart']) ? $selectedItem['Gegenstandsart'] : ''; ?>
                                <div style="color: #ccc; font-size: 0.6rem; margin-top: 5px;">Preis: <?php echo $selectedItem['Gold']; ?> G</div>
                            </div>
                            <div style="font-size: 0.6rem; color: #4caf50; margin-top: 5px; display: flex; flex-direction: column; gap: 3px;">
                            <?php 
                                // 1. Rüstungen Stats)
                                if(isset($selectedItem['Staerke']) && $selectedItem['Staerke'] > 0) 
                                    echo "<span>+".$selectedItem['Staerke']." Stärke</span>";
                                
                                if(isset($selectedItem['Intelligenz']) && $selectedItem['Intelligenz'] > 0) 
                                    echo "<span>+".$selectedItem['Intelligenz']." Intelligenz</span>";
                                
                                if(isset($selectedItem['Geschicklichkeit']) && $selectedItem['Geschicklichkeit'] > 0) 
                                    echo "<span>+".$selectedItem['Geschicklichkeit']." Geschick</span>";

                                // 2. Tränke
                                if(isset($selectedItem['HP']) && $selectedItem['HP'] > 0) 
                                    echo "<span>+".$selectedItem['HP']." HP</span>";
                                
                                if(isset($selectedItem['Mana']) && $selectedItem['Mana'] > 0) 
                                    echo "<span>+".$selectedItem['Mana']." Mana</span>";

                                if(isset($selectedItem['Ausdauer']) && $selectedItem['Ausdauer'] > 0) 
                                    echo "<span>+".$selectedItem['Ausdauer']." Ausdauer</span>";

                                // 3. WAFFEN ANGRIFFE (Einfach unten dran hängen)
                                if (isset($selectedItem['Attacken']) && is_array($selectedItem['Attacken']) && !empty($selectedItem['Attacken'])) {
                                    
                                    // Kleine Trennlinie
                                    echo "<div style='border-top: 1px solid #444; margin: 2px 0;'></div>";

                                    foreach($selectedItem['Attacken'] as $atk) {
                                        // Kosten zusammenbauen (nur wenn > 0)
                                        $kosten = "";
                                        if($atk['Manakosten'] > 0) $kosten .= " <span style='color:#2196f3'>".$atk['Manakosten']." MP</span>";
                                        if($atk['Ausdauerkosten'] > 0) $kosten .= " <span style='color:#ff9800'>".$atk['Ausdauerkosten']." AP</span>";

                                        // Ausgabe: Name + Schaden + Kosten
                                        echo "<div style='line-height: 1.2;'>";
                                        echo "<span style='color: #ddd;'>• ".$atk['Name']."</span>";
                                        echo " <span style='color: #888; font-size: 0.5rem;'>(".$atk['MinSchaden']."-".$atk['MaxSchaden'].")</span>";
                                        echo $kosten;
                                        echo "</div>";
                                    }
                                }
                            ?>
                        </div>
                        </div>

                        <div style="margin-top: auto; width: 100%; display: flex; gap: 5px;">
                            <form action="shopinteraktion.php" method="POST" style="flex: 1; margin: 0;">
                                <input type="hidden" name="itemname" value="<?php echo $selectedItem['Name']; ?>">
                                <input type="hidden" name="aktion" value="verkaufen">
                                <button type="submit" class="pixel-btn" style="width: 100%; font-size: 0.6rem; padding: 8px;">
                                    VERKAUFEN
                                </button>
                            </form>
                            
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
                <div style="font-size: 0.7rem; color: #888; margin-bottom: 4px;">TASCHE:</div>
                <div class="log-scroll-box" style="width: 100%; height: 100% !important; gap: 3px; overflow-y: auto !important;">
                    <?php if (empty($inventarListe)): ?>
                        <div style="text-align: center; color: #555; margin-top: 40px;">Leer</div>
                    <?php else: ?>
                        <?php foreach($inventarListe as $item): ?>
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