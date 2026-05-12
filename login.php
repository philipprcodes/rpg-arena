<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once('dbconfig.php');
include_once("funktionen.php");
// Variable Fehler
$meldung = "";

if (isset($_POST["user"])){
    // trim() entfernt versehentliche Leerzeichen am Anfang/Ende der Eingabe
    $benutzer = trim($_POST["user"]);
    $passw = $_POST["pw"];
    
    // ---------------------------------------------------------------------
    // BEDIENUNG & SICHERHEIT: Prepared Statement für den Login-Check
    // ---------------------------------------------------------------------
   
    $sql = "SELECT ID, Passwort FROM anwender WHERE Name = ?";
    $stmt = mysqli_prepare($con, $sql);
    
  
    mysqli_stmt_bind_param($stmt, "s", $benutzer);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    
    if ($row = mysqli_fetch_assoc($result)) {
        
        // Passwort prüfen
        if (password_verify($passw, $row["Passwort"])){
            
            $ID = $row["ID"];
            $_SESSION["ID"] = $ID;

            // ---------------------------------------------------------------------
            // PERFORMANCE: Charakter vorhanden?
            // ---------------------------------------------------------------------
            // Gezielte Abfrage
            $sqlChar = "SELECT CharakterID FROM charakterliste WHERE AnwenderID = ?";
            $stmtChar = mysqli_prepare($con, $sqlChar);
            mysqli_stmt_bind_param($stmtChar, "i", $ID); 
            mysqli_stmt_execute($stmtChar);
            $resultChar = mysqli_stmt_get_result($stmtChar);

            // Wenn wir etwas finden, hat er einen Charakter
            if ($rowChar = mysqli_fetch_assoc($resultChar)) {
                $CharID = $rowChar["CharakterID"];
                $_SESSION["CharID"] = $CharID;

                // Alle Funktionen laden
                loadinventar($con, $CharID);
                loadausruestung($con, $CharID);
                loadplayer($con, $ID);
                loadshadow($con, $ID);
                loadschattendex($con, $CharID);
                statsupdate($con, $CharID);
                
                header("Location: maingame.php");
                exit; 
            } else {
                // Kein Charakter gefunden -> ab zur Erstellung
                header("Location: charaktercreation.php");
                exit;
            }
        
        } else { 
            $meldung = "Falsches Passwort!";
        }
    } else {
        $meldung = "Benutzername nicht vorhanden!";
    }
}

include_once 'backgroundlogik.php';
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login - RPG</title>
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="style_neu.css">
    
    <style>
        
        body {
            background-image: 
                linear-gradient(rgba(0, 0, 0, 0.3), rgba(0, 0, 0, 0.3)),
                url("Images/Backgrounds/<?php echo $bgImage; ?>") !important;
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            margin: 0;
            overflow: hidden; /* Verhindert Scrollbalken während Intro */
        }

        /* DAS INTRO OVERLAY */
        .intro-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 9999; /* Ganz oben liegen */
            
            /* Logo-Bild */
            background-image: url("Images/Backgrounds/Startscreen_Logo.webp");
            background-size: cover;
            background-position: center;
            
            
            /* DIE ANIMATION: Name | Dauer | Kurve | Verzögerung | Ende-Status */
            animation: fadeOutOverlay 1.5s ease-in-out 2s forwards;
            
            /* WICHTIG damit man nicht vorher klicken kann */
        }

        /* DIE LOGIN BOX */
        .form-wrapper {
            opacity: 0;
            transform: translateY(30px); /* Weiter unten starten */
            
            /* Animation: Auftauchen nach 2.2 Sekunden (etwas nach dem Overlay) */
            animation: slideInBox 1s ease-out 2.5s forwards;
        }

        /* === KEYFRAMES  === */
        
        /* Overlay verschwinden lassen */
        @keyframes fadeOutOverlay {
            0% { 
                opacity: 1; 
                visibility: visible;
            }
            100% { 
                opacity: 0; 
                visibility: hidden; 
            }
        }

        /* Login Box erscheinen lassen */
        @keyframes slideInBox {
            0% {
                opacity: 0;
                transform: translateY(30px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>

</head>
<body>

    <div class="intro-overlay"></div>

    <div class="pixel-box form-wrapper">
        
        <h2>LOGIN</h2>

        <?php if ($meldung != ""): ?>
            <div class="msg-box">
                <?php echo $meldung; ?>
            </div>
        <?php endif; ?>

        <form action="" method="post">
            <label>NAME:</label>
            <input type="text" required name="user" placeholder="...">
            
            <label>KENNWORT:</label>
            <input type="password" required name="pw" placeholder="***">
            
            <button type="submit" class="pixel-btn action-btn" style="width: 100%; margin-top: 10px;">
                SPIEL STARTEN
            </button>
        </form>

        <div class="link-text">
            NOCH KEINEN ACCOUNT?<br>
            <a href="registrieren.php">HIER REGISTRIEREN</a>
        </div>

    </div>

</body>
</html>