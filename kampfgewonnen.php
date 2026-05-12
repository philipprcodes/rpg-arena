<?php
// Session prüfen
if (!isset($_SESSION['Kampf'])) {
    header("Location: maingame.php");
    exit;
}

// -------------------------------------------------------------------------
// BUTTON LOGIK 
// -------------------------------------------------------------------------
if (isset($_POST['aktion'])) {
    $aktion = $_POST['aktion'];

    if ($aktion == 'loot') {
        $_SESSION['inventarart'] = 'loot'; 
        $_SESSION['inventar'] = true; 
        header("Location: maingame.php"); 
        exit;
    }
    elseif ($aktion == 'levelup') {
        $_SESSION['levelup'] = true;
        header("Location: maingame.php");
        exit;
    }
    elseif ($aktion == 'continue') {
        unset($_SESSION['Kampf']);
        header("Location: maingame.php");
        exit;
    }
}

// -------------------------------------------------------------------------
// DATEN LADEN & BERECHNEN
// -------------------------------------------------------------------------

$kampfDaten = $_SESSION['Kampf'];
$gegnerListe = $kampfDaten['gegner'];
$spielerID = $_SESSION['ID']; 
$CharakterID = $_SESSION['CharID'];

// XP BERECHNEN
$wonXP = 0;
foreach ($gegnerListe as $monster) {
    $wonXP += isset($monster['XP']) ? $monster['XP'] : 0;
}

// -------------------------------------------------------------------------
// DATENBANK UPDATE (Mit F5-Schutz & Prepared Statements)
// -------------------------------------------------------------------------

if (!isset($_SESSION['Kampf']['xp_granted'])) {
    require_once('dbconfig.php');

    // Aktuelle XP und Level aus der DB holen
    $sql = "SELECT XP, Level FROM charakterliste WHERE AnwenderID = ?";
    $stmt = mysqli_prepare($con, $sql);
    mysqli_stmt_bind_param($stmt, "i", $spielerID);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $charData = mysqli_fetch_assoc($result);
    
    $currentXP = $charData['XP'];
    $currentLevel = $charData['Level'];
    $newXP = $currentXP + $wonXP;

    // Update in DB schreiben
    $updateSql = "UPDATE charakterliste SET XP = ? WHERE AnwenderID = ?";
    $updateStmt = mysqli_prepare($con, $updateSql);
    mysqli_stmt_bind_param($updateStmt, "ii", $newXP, $spielerID);
    mysqli_stmt_execute($updateStmt);

    $_SESSION['Kampf']['loot'] = []; 
    include_once "lootgenerieren.php";
    include_once "funktionen.php";
    
    // Sicherung setzen
    $_SESSION['Kampf']['xp_granted'] = true;
    $_SESSION['Kampf']['lootgeneriert'] = true;
    $_SESSION['Kampf']['new_total_xp'] = $newXP;
    $_SESSION['Kampf']['current_level'] = $currentLevel;
}

// Werte für die Anzeige
$newTotalXP = $_SESSION['Kampf']['new_total_xp'];
$currentLevel = $_SESSION['Kampf']['current_level'];

// LEVEL UP PRÜFUNG (Beispiel: Alle 100 XP)
$xpNeeded = $currentLevel * 500; 
$canLevelUp = ($newTotalXP >= $xpNeeded);

// LOOT STATUS PRÜFEN
$hatGelootet = isset($_SESSION['Kampf']['hat_gelootet']) && $_SESSION['Kampf']['hat_gelootet'] == true;

?>

<div class="pixel-box" style="height: 100%; display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center;">

    <h2 style="font-size: 1.8rem; color: #ffcc00; text-shadow: 3px 3px 0 #000; margin-bottom: 5px;">
         SIEGREICH! 
    </h2>
    
    <?php if (isset($_SESSION['Kampf']['fangmeldung']) && !empty($_SESSION['Kampf']['fangmeldung'])): ?>
        <div class="pixel-box" style="width: 90%; height: auto !important; min-height: 0 !important; flex: 0 0 auto; padding: 10px; margin: 10px 0; border: 2px solid #9c27b0; background: rgba(156, 39, 176, 0.1);">
            <div style="color: #9c27b0; font-size: 0.8rem; margin-bottom: 5px; font-weight: bold;">
                 SCHATTEN GEFANGEN!
            </div>
            <?php foreach ($_SESSION['Kampf']['fangmeldung'] as $msg): ?>
                <div style="font-size: 0.6rem; color: #e1bee7; margin-bottom: 3px;">
                    <?php echo $msg; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="loot-grid" style="margin: 15px 0;">
        <?php foreach ($gegnerListe as $monster): ?>
            <?php $xp = isset($monster['XP']) ? $monster['XP'] : 0; ?>
            <div class="loot-card">
                <div style="font-size: 2.5rem; margin-bottom: 5px;">👺</div>
                <div style="font-size: 0.5rem; color: #fff; margin-bottom: 2px;">
                    <?php echo $monster['Name']; ?>
                </div>
                <div class="xp-gain">+<?php echo $xp; ?> XP</div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="total-xp-box" style="margin-bottom: 30px; padding: 8px;">
        GESAMT: <span style="color:#fff"><?php echo $newTotalXP; ?> XP</span> 
        <span style="font-size:0.6rem; color:#888;">(Level <?php echo $currentLevel; ?>)</span>
    </div>

    <form method="POST" style="width: 100%; display: flex; flex-direction: row; gap: 15px; justify-content: center; align-items: stretch;">
        
        <button type="submit" name="aktion" value="levelup" class="pixel-btn" style="flex: 1; font-size: 0.7rem; padding: 20px 0;" <?php echo (!$canLevelUp) ? 'disabled' : ''; ?>>
            LEVEL UP
            <?php if(!$canLevelUp): ?>
                <br><span style="font-size: 0.5rem; opacity: 0.7;">(Fehlen: <?php echo $xpNeeded - $newTotalXP; ?>)</span>
            <?php else: ?>
                <br><span style="font-size: 0.5rem; opacity: 1;">(Verfügbar!)</span>
            <?php endif; ?>
        </button>

        <button type="submit" name="aktion" value="loot" class="pixel-btn" style="flex: 1; font-size: 0.7rem; padding: 20px 0;" <?php echo ($hatGelootet) ? 'disabled' : ''; ?>>
            LOOTEN
            <?php if($hatGelootet): ?>
                <br><span style="font-size: 0.5rem; opacity: 0.7;">(Leer)</span>
            <?php else: ?>
                <br><span style="font-size: 0.5rem; opacity: 0.7;">(Einsammeln)</span>
            <?php endif; ?>
        </button>

        <button type="submit" name="aktion" value="continue" class="pixel-btn" style="flex: 1; font-size: 0.7rem; padding: 20px 0;">
            WEITER
            <br><span style="font-size: 0.5rem; opacity: 0.7;">(Zum Hub)</span>
        </button>

    </form>

</div>