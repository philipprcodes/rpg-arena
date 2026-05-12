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

// ---------------------------------------------------------------------
// Includes
// ---------------------------------------------------------------------
require_once('dbconfig.php');
include_once("funktionen.php");

// ---------------------------------------------------------------------
// Char ID besorgen sonst zu Charakter Creation
// ---------------------------------------------------------------------
if (!isset($_SESSION["CharID"])){
    $ID = $_SESSION["ID"];
    
    $sql = "SELECT CharakterID FROM charakterliste WHERE AnwenderID = ?";
    $stmt = mysqli_prepare($con, $sql);
    mysqli_stmt_bind_param($stmt, "i", $ID);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        $_SESSION["CharID"] = $row["CharakterID"];
    } else {
        // Zurück zur Char Creation
        header("Location: charaktercreation.php");
        exit;
    }
}


// ---------------------------------------------------------------------
// HUB / Navigation
// ---------------------------------------------------------------------
if (isset($_POST['hub_aktion'])) {
    // Normales Inventar öffnen
    if ($_POST['hub_aktion'] == 'inventar_oeffnen') {
        $_SESSION['inventar'] = true;          
        $_SESSION['inventarart'] = 'inventar'; 
        header("Location: maingame.php");      
        exit;
    }

    // Schattendex öffnen 
    elseif ($_POST['hub_aktion'] == 'schattendex_oeffnen') {
        $_SESSION['inventar'] = true;          
        $_SESSION['inventarart'] = 'schatten'; // Das triggert die Monster-Ansicht
        header("Location: maingame.php");      
        exit;
    }
    // Charakter öffnen
    elseif ($_POST['hub_aktion'] == 'charakter_oeffnen') {
        $_SESSION['charakteroeffnen'] = true;          
        header("Location: maingame.php");      
        exit;
    }
    // Shop öffnen & Runde beenden
    elseif ($_POST['hub_aktion'] == 'shop_oeffnen') { 
        
        // --- LOGIK: RUNDE BEENDET ---
        if (isset($_POST['loop_reset'])) {
            // 1. Wins zurücksetzen
            $_SESSION['stage_wins'] = 0; 
            
            // 2. Durchgang (Stage) erhöhen
            if (!isset($_SESSION['stage_level'])) {
                $_SESSION['stage_level'] = 1;
            }
            $_SESSION['stage_level']++;
            saveGame($con, $_SESSION['CharID']);
            
        }

        // ---------------------------------------------------

        $_SESSION['shop'] = true;       
        header("Location: maingame.php");       
        exit;
    }
}
// Kampfinventar! Post aus Kampflog...
if (isset($_POST['aktion']) && $_POST['aktion'] == 'kampfinventar') {
    $_SESSION['Kampf']['inventar_offen'] = true;
    header("Location: maingame.php");
    exit;
}

// ---------------------------------------------------------------------
// DATEN LADEN FÜR ANZEIGE
// ---------------------------------------------------------------------
// Charakterdaten für die Sidebar
$CharStats = Charakterwerte($con, $_SESSION["ID"]);

if (!$CharStats) {
    die("Fehler: Keine Charakterdaten gefunden. Bitte Administrator kontaktieren.");
}

// Status-Prüfungen für die Ansichtswechsel im <main> Bereich
$kampfAktiv = isset($_SESSION['Kampf']) && $_SESSION['Kampf']['aktiv'] == true;
$kampfinventarOffen = isset($_SESSION['Kampf']['inventar_offen']) && $_SESSION['Kampf']['inventar_offen'] == true;
$kampfgewonnen = isset($_SESSION['Kampf']) && $_SESSION['Kampf']['gewonnen'] == true;
$kampfverloren = isset($_SESSION['Kampf']) && $_SESSION['Kampf']['verloren'] == true;
$inventaroffen = isset($_SESSION['inventar']) && $_SESSION['inventar'] == true;
$levelupaktiv = isset($_SESSION['levelup']) && $_SESSION['levelup'] == true;
$shopoffen = isset($_SESSION['shop']) && $_SESSION['shop'] == true;
$charakterScreenOffen = isset($_SESSION['charakteroeffnen']) && $_SESSION['charakteroeffnen'] == true;
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Retro RPG</title>
    <link rel="stylesheet" href="style_neu.css">
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
    <?php include_once 'backgroundlogik.php'; ?>
</head>
<body>

<div class="game-container">
    
    <aside class="sidebar">
        
        <div class="menu-wrapper">
            <form method="POST" style="margin:0; width: 100%;">
                <button type="submit" name="hub_aktion" value="charakter_oeffnen" class="btn-reset">
                    <div class="menu-item">
                        <div class="pixel-btn icon-btn" style="color: gold;">👤</div>
                        <span class="label">Stats</span>
                    </div>
                </button>
            </form>
        </div>

        <div class="menu-wrapper">
            <form method="POST" style="margin:0; width: 100%;">
                <button type="submit" name="hub_aktion" value="inventar_oeffnen" class="btn-reset">
                    <div class="menu-item">
                        <div class="pixel-btn icon-btn">🎒</div>
                        <span class="label">Inventar</span>
                    </div>
                </button>
            </form>
        </div>

        <div class="menu-wrapper">
            <form method="POST" style="margin:0; width: 100%;">
                <button type="submit" name="hub_aktion" value="schattendex_oeffnen" class="btn-reset">
                    <div class="menu-item">
                        <div class="pixel-btn icon-btn" style="color: #9c27b0;">🐺</div>
                        <span class="label">DEX</span>
                    </div>
                </button>
            </form>
        </div>

        <div class="menu-wrapper">
             <a href="logout.php" style="text-decoration: none; color: inherit; width: 100%;">
                 <div class="menu-item">
                    <button type="button" class="pixel-btn icon-btn" style="color: #ff5555;">💾 </button>
                    <span class="label">SAVE/LOGOUT</span>
                </div>
            </a>
        </div>

    </aside>


    <main class="main-content">

    <?php 
    if ($shopoffen) {
        include("shop.php");
    } elseif ($inventaroffen) {
        include("inventar.php");
    } elseif ($charakterScreenOffen) { 
        include("charakter.php");
    } elseif ($levelupaktiv) { 
        include("levelup.php");
    } elseif ($kampfAktiv && $kampfinventarOffen) {
        include("kampfinventar.php");
    } elseif ($kampfAktiv) {
        include("kampflog.php");
    } elseif ($kampfgewonnen) {
        include("kampfgewonnen.php");
    } else {
        // Zähler initialisieren falls nicht vorhanden
        if (!isset($_SESSION['stage_wins'])) { $_SESSION['stage_wins'] = 0; }
        $wins = $_SESSION['stage_wins'];
        $maxWins = 5;
    ?>

        <div class="pixel-box hub-container">
            <?php $currentStage = $_SESSION['stage_level'] ?? 1; ?>
                
            <h2 class="hub-title">
                ADVENTURE HUB
            </h2>
            <span style="font-size: 0.8rem; padding-top: 3px; color: #55ff55;">(Stage <?php echo $currentStage; ?>)</span>
            
            <div class="progress-wrapper">
                <p style="font-size: 0.7rem; color: #aaa; margin-bottom: 10px;">FORTSCHRITT BIS ZUM HÄNDLER</p>
                
                <div class="progress-bar-bg">
                    <?php 
                        $width = ($wins / $maxWins) * 100;
                        $barColor = ($wins >= $maxWins) ? '#55ff55' : '#4caf50';
                    ?>
                    <div style="width: <?php echo $width; ?>%; height: 100%; background: <?php echo $barColor; ?>; transition: width 0.5s;"></div>
                    <span class="progress-text">
                        <?php echo $wins; ?> / <?php echo $maxWins; ?> KÄMPFE
                    </span>
                </div>
            </div>

            <div class="hub-actions">
                
                <?php if ($wins < $maxWins): ?>
                    <div class="hub-btn-wrapper">
                        <a href="kampfstart.php" class="pixel-btn action-btn hub-btn hub-action-btn" style="text-decoration: none;">
                            <?php 
                            if ($wins == 0) echo "⚔️ ERSTER KAMPF";
                            else echo "⚔️ NÄCHSTER KAMPF"; 
                            ?>
                        </a>
                    </div>
                <?php else: ?>
                    <form method="POST" class="hub-btn-wrapper">
                        <input type="hidden" name="loop_reset" value="1">
                        <button type="submit" name="hub_aktion" value="shop_oeffnen" class="pixel-btn action-btn hub-btn hub-action-btn hub-shop-border">
                            💰 ZUM HÄNDLER (Runde beenden)
                        </button>
                    </form>
                <?php endif; ?>

                <form method="POST" class="hub-btn-wrapper">
                    <button type="submit" name="hub_aktion" value="inventar_oeffnen" class="pixel-btn action-btn hub-btn hub-action-btn">
                        🎒 Inventar
                    </button>
                </form>

                <form method="POST" class="hub-btn-wrapper">
                    <button type="submit" name="hub_aktion" value="schattendex_oeffnen" class="pixel-btn action-btn hub-btn hub-action-btn hub-dex-color">
                        🐺 Schattendex
                    </button>
                </form>
                
            </div>
        </div>
    <?php 
    } 
    ?>
    

</main>
</div>

</body>
</html>