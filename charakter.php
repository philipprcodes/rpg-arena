<?php
// Session checken
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once('dbconfig.php');
include_once("funktionen.php");

// 1. BASIS-STATS LADEN (Nackt)
$id = $_SESSION['ID'];
$sql = "SELECT * FROM charakterliste WHERE AnwenderID = '$id'";
$abfrage = mysqli_query($con, $sql);
$spieler = mysqli_fetch_assoc($abfrage);

$baseStr = $spieler['Staerke'];
$baseDex = $spieler['Geschicklichkeit'];
$baseInt = $spieler['Intelligenz'];
$level   = $spieler['Level'];
$xp      = $spieler['XP'];

// 2. BONUS BERECHNEN (Ausrüstung)
$bonusStr = 0;
$bonusDex = 0;
$bonusInt = 0;

if (isset($_SESSION['Ausruestung'])) {
    foreach ($_SESSION['Ausruestung'] as $item) {
        // Nur wenn Item existiert und nicht als "leer" markiert ist
        if (isset($item['IstLeer']) && $item['IstLeer'] == false) {
            $bonusStr += isset($item['Staerke']) ? $item['Staerke'] : 0;
            $bonusDex += isset($item['Geschicklichkeit']) ? $item['Geschicklichkeit'] : 0;
            $bonusInt += isset($item['Intelligenz']) ? $item['Intelligenz'] : 0;
        }
    }
}

// Gesamtwerte
$totalStr = $baseStr + $bonusStr;
$totalDex = $baseDex + $bonusDex;
$totalInt = $baseInt + $bonusInt;

// Zurück Button
if (isset($_POST['aktion']) && $_POST['aktion'] == 'back') {
    unset($_SESSION['charakteroeffnen']); // Screen Variable löschen
    echo "<script>window.location.href='maingame.php';</script>";
    exit;
}
?>

<div style="height: 100%; display: flex; flex-direction: column; justify-content: center;">

    <div class="pixel-box" style="width: 100%; padding: 20px; box-sizing: border-box; height: auto !important; min-height: 0 !important;">
        
        <div style="border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px; text-align: center; display: flex; justify-content: space-between; align-items: center;">
            <h2 style="margin: 0; color: var(--btn-bg); text-shadow: 2px 2px 0 #000; font-size: 1.2rem;">
                CHARAKTER STATUS
            </h2>
            <form method="POST" style="margin:0;">
                <button type="submit" name="aktion" value="back" class="pixel-btn" style="padding: 2px 8px; font-size: 0.7rem;">X</button>
            </form>
        </div>

        <div style="display: flex; gap: 30px; align-items: flex-start;">

            <div style="width: 40%; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 15px;">
                
                <div style="text-align: center; margin-top: 20px;">
                    <div style="font-size: 4rem; margin-bottom: 10px;">👤</div>
                    <div style="color: #fff; font-size: 1rem; margin-bottom: 5px; text-transform: uppercase;">
                        <?php echo $spieler['Name']; ?>
                    </div>
                    
                    <div style="background: rgba(0,0,0,0.3); padding: 10px; border-radius: 5px; border: 1px solid #333;">
                        <div style="font-size: 0.7rem; color: #888;">LEVEL</div>
                        <div style="font-size: 2rem; color: var(--btn-bg); font-weight: bold; text-shadow: 2px 2px 0 #000;">
                            <?php echo $level; ?>
                        </div>
                        <div style="font-size: 0.6rem; color: #aaa; margin-top: 5px;">
                            <?php echo $xp; ?> XP
                        </div>
                    </div>
                </div>

            </div>

            <div style="width: 60%; display: flex; flex-direction: column; gap: 10px;">
                
                <div class="log-inset-panel" style="justify-content: space-between; padding: 10px; min-height: 50px; align-items: center;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span style="font-size: 1.5rem;">💪</span>
                        <div>
                            <div style="color: #fff; font-size: 0.8rem;">STÄRKE</div>
                            <div style="font-size: 0.5rem; color: #666;">Basis: <?php echo $baseStr; ?></div>
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <span style="font-size: 1.2rem; color: #fff;"><?php echo $totalStr; ?></span>
                        <?php if($bonusStr > 0): ?>
                            <br><span style="color: #4caf50; font-size: 0.6rem;">(+<?php echo $bonusStr; ?>)</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="log-inset-panel" style="justify-content: space-between; padding: 10px; min-height: 50px; align-items: center;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span style="font-size: 1.5rem;">🦶</span>
                        <div>
                            <div style="color: #fff; font-size: 0.8rem;">GESCHICK</div>
                            <div style="font-size: 0.5rem; color: #666;">Basis: <?php echo $baseDex; ?></div>
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <span style="font-size: 1.2rem; color: #fff;"><?php echo $totalDex; ?></span>
                        <?php if($bonusDex > 0): ?>
                            <br><span style="color: #4caf50; font-size: 0.6rem;">(+<?php echo $bonusDex; ?>)</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="log-inset-panel" style="justify-content: space-between; padding: 10px; min-height: 50px; align-items: center;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span style="font-size: 1.5rem;">🧠</span>
                        <div>
                            <div style="color: #fff; font-size: 0.8rem;">INTELLIGENZ</div>
                            <div style="font-size: 0.5rem; color: #666;">Basis: <?php echo $baseInt; ?></div>
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <span style="font-size: 1.2rem; color: #fff;"><?php echo $totalInt; ?></span>
                        <?php if($bonusInt > 0): ?>
                            <br><span style="color: #4caf50; font-size: 0.6rem;">(+<?php echo $bonusInt; ?>)</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div style="height: 1px; background: #333; margin: 5px 0;"></div>

                <div style="display: flex; justify-content: space-between; gap: 10px;">
                    <div class="log-inset-panel" style="flex: 1; flex-direction: column; align-items: center; padding: 5px;">
                        <span style="color: #4caf50; font-size: 0.6rem;">HP</span>
                        <span style="color: #fff; font-size: 0.8rem;"><?php echo $_SESSION['Spieler']['HPmax']; ?></span>
                    </div>
                    <div class="log-inset-panel" style="flex: 1; flex-direction: column; align-items: center; padding: 5px;">
                        <span style="color: #2196f3; font-size: 0.6rem;">MANA</span>
                        <span style="color: #fff; font-size: 0.8rem;"><?php echo $_SESSION['Spieler']['Manamax']; ?></span>
                    </div>
                    <div class="log-inset-panel" style="flex: 1; flex-direction: column; align-items: center; padding: 5px;">
                        <span style="color: #ff9800; font-size: 0.6rem;">AUSDAUER</span>
                        <span style="color: #fff; font-size: 0.8rem;"><?php echo $_SESSION['Spieler']['Ausdauermax']; ?></span>
                    </div>
                </div>

            </div>

        </div>

    </div>

</div>