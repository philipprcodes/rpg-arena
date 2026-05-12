<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once('dbconfig.php');
include_once("funktionen.php");

// Hintergrundlogik 
include_once('backgroundlogik.php'); 

// === RESPAWN LOGIK: WENN DER KNOPF GEDRÜCKT WURDE ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['respawn'])) {
    
    // Stage Wins zurücksetzen
    $_SESSION['stage_wins'] = 0;

    // Checkpoint berechnen
    $currentLevel = isset($_SESSION['stage_level']) ? $_SESSION['stage_level'] : 1;
    $checkpoint = 1;

    if ($currentLevel < 4) {
        $checkpoint = 1; // Wald Anfang
    } elseif ($currentLevel < 7) {
        $checkpoint = 4; // Goblinlager Anfang
    } elseif ($currentLevel < 10) {
        $checkpoint = 7; // Winterdorf Anfang
    } else {
        $checkpoint = 10; // Endgame Anfang
    }

    // Level auf Checkpoint setzen
    $_SESSION['stage_level'] = $checkpoint;

    // Kampf-Session löschen
    unset($_SESSION['Kampf']); // Löscht alle Kampfdaten (Gegner, Log, Status)

    // HP, Mana und Ausdauer in der DB wieder auf Max setzen
    if (isset($_SESSION['CharID'])) {
        $charID = $_SESSION['CharID'];
        
        $sql = "UPDATE charakterliste SET HP = HPmax, Mana = Manamax, Ausdauer = Ausdauermax, StageLevel = ?, StageWins = 0 WHERE CharakterID = ?";
        $stmt = mysqli_prepare($con, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $checkpoint, $charID);
        mysqli_stmt_execute($stmt);
        
        // Session mit neuen Werten füttern!
        $UserID = $_SESSION['ID'];
        loadplayer($con, $UserID);
        $_SESSION['stage_level'] = $checkpoint;
        $_SESSION['stage_wins'] = 0;
    }

    // Zurück ins Spiel
    $_SESSION['view'] = 'hub'; // Sicherstellen, dass der Spieler im Hub landet
    header("Location: maingame.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GAME OVER</title>
    <link rel="stylesheet" href="style_neu.css">
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
    
    <style>
                
        /* roter schleier */
        .gameover-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(100, 0, 0, 0.6); /* Dunkles, transparentes Rot */
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            animation: fadeInRed 2s ease-in;
        }

        /* text animation */
        .gameover-title {
            font-size: 3rem;
            color: #ff3333;
            text-shadow: 4px 4px 0px #000;
            margin-bottom: 20px;
            animation: pulse 1.5s infinite;
        }

        .gameover-text {
            color: #fff;
            margin-bottom: 40px;
            text-shadow: 2px 2px 0 #000;
            text-align: center;
            line-height: 1.5;
        }

        @keyframes fadeInRed {
            from { background: rgba(0,0,0,0); }
            to { background: rgba(100, 0, 0, 0.6); }
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .respawn-btn {
        font-size: 1.2rem;
        padding: 15px 30px;
        background-color: #ff3333;
        border-color: #ffffff;
        color: #ffffff;
        box-shadow: inset -4px -4px 0 #360808, inset 4px 4px 0 #300202;
        cursor: pointer; /* Zeigefinger-Mauszeiger */
        
        /* Opacity und weicher Übergang */
        opacity: 0.4; 
        transition: opacity 0.3s ease; 
    }

        .respawn-btn:hover {
        opacity: 0.8;
        }
    </style>
</head>
<body>

    <div class="gameover-overlay">
        
        <h1 class="gameover-title">GAME OVER</h1>
        
        <div class="gameover-text">
            Du wurdest besiegt.<br>
            Deine Reise endet hier... vorerst.
        </div>

        <form method="POST">
            <button type="submit" name="respawn" class="pixel-btn respawn-btn">
                FORTSETZEN (Checkpoint)
            </button>
        </form>

    </div>

</body>
</html>