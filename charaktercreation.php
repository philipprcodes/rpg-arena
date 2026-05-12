<?php
// 1. PHP GANZ OBEN
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["ID"])){
    header("Location: login.php");
    exit;
}

require_once('dbconfig.php');
include_once("funktionen.php");

// 2. Variablen initialisieren
$staerke = 3;
$geschick = 3;
$int = 3;
$name = "";
$meldung = ""; 

if (isset($_POST["name"])){

    // Eingaben holen
    $name = $_POST["name"];
    $staerke = $_POST["staerke"];
    $geschick = $_POST["geschick"];
    $int = $_POST["int"];



    $kontrolle = $staerke + $geschick + $int;

    $werte = array('staerke' => $staerke, 'geschick' => $geschick, 'int' => $int);
    arsort($werte);
    $index=key($werte);

    if ($index=='staerke') $bild='krieger.png';
    elseif ($index=='geschick') $bild='dieb.png';
    elseif ($index=='int') $bild='zauberer.png';

    if ($kontrolle < 19) {
        $meldung = "Punkte nicht verbraucht (Summe: $kontrolle).";
    }
    elseif ($kontrolle > 19) {
        $meldung = "Zu viele Punkte verteilt (Summe: $kontrolle).";
    }
    elseif ($kontrolle == 19) {
        // Alles OK -> Speichern
        $ID = $_SESSION["ID"];
        $HP = 50 + $staerke * 5;
        $HPmax = $HP;
        $Ausdauer = 50 + $geschick * 5;
        $Ausdauermax = $Ausdauer;
        $Mana = 50 + $int * 5;
        $Manamax = $Mana;
        $level = 1;
        $xp = 0;
        
        $safeName = mysqli_real_escape_string($con, $name);

        


      
        $sql = "INSERT INTO `charakterliste`(`AnwenderID`, `Name`, `Staerke`, `Geschicklichkeit`, `Intelligenz`, `HP`, `HPmax`, `Ausdauer`, `Ausdauermax`, `Mana`, `Manamax`, `Level`, `XP`, `Bild`) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt= mysqli_prepare($con , $sql);
        mysqli_stmt_bind_param($stmt, "isiiiiiiiiiiis",
            $ID, 
            $name, 
            $staerke, 
            $geschick, 
            $int, 
            $HP, 
            $HPmax, 
            $Ausdauer, 
            $Ausdauermax, 
            $Mana, 
            $Manamax, 
            $level, 
            $xp, 
            $bild
        );

        if(mysqli_stmt_execute($stmt)) {
            $CharID = mysqli_insert_id($con); // Holt sich die ID des gerade erstellten Charakters
            
            // Starterpaket und Session laden
            starterPaket($con, $CharID);
            loadinventar($con, $CharID);
            loadausruestung($con, $CharID);
            loadplayer($con, $ID);
            loadshadow($con, $ID);
            loadschattendex($con, $CharID);
            statsupdate($con, $CharID);
            
            // In die Session speichern
            $_SESSION["CharID"] = $CharID;
            
            header("Location: maingame.php");
            exit;
        } else {
            $meldung = "Datenbankfehler: " . mysqli_error($con);
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Charakter erstellen</title>
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style_neu.css">
     <?php include_once 'backgroundlogik.php'; ?>
</head>
<body>

    <div class="pixel-box form-wrapper">
        
        <h2>HELD ERSTELLEN</h2>

        <?php if ($meldung != ""): ?>
            <div class="msg-box">
                <?php echo $meldung; ?>
            </div>
        <?php endif; ?>

        <p>
            Verteile <strong>10 Punkte</strong>.<br>
            <span style="color: #666; font-size: 0.5rem;">(Startwert ist 3, Zielsumme: 19)</span>
        </p>

        <form action="" method="POST">
            
            <label>HELDENNAME:</label>
            <input type="text" name="name" placeholder="Name eingeben..." required value="<?php echo $name; ?>">
            
            <div style="height: 15px;"></div> <div class="form-row">
                <label>STÄRKE </label>
                <input type="number" name="staerke" min="3" max="13" value="<?php echo $staerke; ?>">
            </div>

            <div class="form-row">
                <label>GESCHICK </label>
                <input type="number" name="geschick" min="3" max="13" value="<?php echo $geschick; ?>">
            </div>

            <div class="form-row">
                <label>INTELLIGENZ </label>
                <input type="number" name="int" min="3" max="13" value="<?php echo $int; ?>">
            </div>

            <br>
            <button type="submit" class="pixel-btn action-btn" style="width: 100%;">
                FERTIG & STARTEN
            </button>
            
        </form>
    </div>

</body>
</html>