<?php
// ------------------------------------------------------------------
// Sicherheitscheck
// ------------------------------------------------------------------
if (!isset($_SESSION['levelup']) || $_SESSION['levelup'] !== true) {
    header("Location: maingame.php");
    exit;
}

require_once('dbconfig.php');
include_once("funktionen.php");

$id = $_SESSION['ID'];
$CharID = $_SESSION['CharID'];
$errorMsg = "";

// Speichern
saveGame($con, $CharID);
// Falls die Logik einen Fehler zurückgegeben hat (z.B. falsche Punktzahl)
if (isset($_SESSION['levelup_error'])) {
    $errorMsg = $_SESSION['levelup_error'];
    unset($_SESSION['levelup_error']);
}

// Aktuelle Stats laden
$sql = "SELECT * FROM charakterliste WHERE AnwenderID = ?";
$stmt = mysqli_prepare($con, $sql);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);


$abfrage = mysqli_stmt_get_result($stmt);
$spieler = mysqli_fetch_assoc($abfrage);

$lvl = $spieler['Level'];
$str = $spieler['Staerke'];
$dex = $spieler['Geschicklichkeit'];
$int = $spieler['Intelligenz'];


$punkte = 5;
?>

<form action="leveluplogik.php" method="POST" style="height: 100%; display: flex; flex-direction: column; justify-content: center;">

    <div class="pixel-box" style="width: 100%; padding: 20px; box-sizing: border-box; height: auto !important; min-height: 0 !important;">
        
        <div style="border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px; text-align: center;">
            <h2 style="margin: 0; color: var(--btn-bg); text-shadow: 2px 2px 0 #000; font-size: 1.2rem;">
                LEVEL AUFSTIEG!
            </h2>
        </div>

        <?php if ($errorMsg != ""): ?>
            <div class="msg-box" style="text-align: center; margin-bottom: 20px;">
                <?php echo $errorMsg; ?>
            </div>
        <?php else: ?>
            <div style="text-align: center; margin-bottom: 20px; font-size: 0.7rem; color: #aaa;">
                Du erhältst <strong style="color: #fff;"><?php echo $punkte; ?> Punkte</strong>.<br>
                Verteile sie weise!
            </div>
        <?php endif; ?>

        <div style="display: flex; gap: 30px; align-items: flex-start;">

            <div style="width: 40%; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 15px;">
                
                <div style="text-align: center; margin-top: 20px;">
                    <div style="font-size: 0.7rem; color: #888; margin-bottom: 10px;">STUFE</div>
                    
                    <div style="display: flex; flex-direction: column; align-items: center; gap: 5px;">
                        <span style="font-size: 1.5rem; color: #fff; opacity: 0.7;"><?php echo $lvl; ?></span>
                        
                        <span style="color: var(--btn-bg); font-size: 1.2rem;">⬇</span>
                        
                        <span style="font-size: 2.5rem; color: var(--btn-bg); font-weight: bold; text-shadow: 2px 2px 0 #000;"><?php echo $lvl + 1; ?></span>
                    </div>
                </div>

            </div>

            <div style="width: 60%; display: flex; flex-direction: column; gap: 10px;">
                
                <div class="log-inset-panel" style="justify-content: space-between; padding: 10px; min-height: 50px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span style="font-size: 1.5rem;">💪</span>
                        <div>
                            <div style="color: #fff; font-size: 0.8rem;">STÄRKE</div>
                            <div style="font-size: 0.5rem; color: #666;">Aktuell: <?php echo $str; ?></div>
                        </div>
                    </div>
                    <input type="number" name="staerke" min="<?php echo $str; ?>" value="<?php echo $str; ?>" style="width: 70px; margin: 0; padding: 5px; text-align: center;">
                </div>

                <div class="log-inset-panel" style="justify-content: space-between; padding: 10px; min-height: 50px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span style="font-size: 1.5rem;">🦶</span>
                        <div>
                            <div style="color: #fff; font-size: 0.8rem;">GESCHICK</div>
                            <div style="font-size: 0.5rem; color: #666;">Aktuell: <?php echo $dex; ?></div>
                        </div>
                    </div>
                    <input type="number" name="geschick" min="<?php echo $dex; ?>" value="<?php echo $dex; ?>" style="width: 70px; margin: 0; padding: 5px; text-align: center;">
                </div>

                <div class="log-inset-panel" style="justify-content: space-between; padding: 10px; min-height: 50px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span style="font-size: 1.5rem;">🧠</span>
                        <div>
                            <div style="color: #fff; font-size: 0.8rem;">INTELLIGENZ</div>
                            <div style="font-size: 0.5rem; color: #666;">Aktuell: <?php echo $int; ?></div>
                        </div>
                    </div>
                    <input type="number" name="int" min="<?php echo $int; ?>" value="<?php echo $int; ?>" style="width: 70px; margin: 0; padding: 5px; text-align: center;">
                </div>

                <button type="submit" name="save_stats" class="pixel-btn" style="width: 100%; padding: 15px; margin-top: 10px; font-size: 0.9rem;">
                    BESTÄTIGEN
                </button>

            </div>

        </div>

    </div>

</form>