<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once('dbconfig.php');

$meldung = "";
$erfolg = false;

if (isset($_POST["user"])){

    $benutzer = $_POST["user"];
    $passw = $_POST["pw"];
    $passw2 = $_POST["pw2"];

    if ($passw !== $passw2) {
        $meldung = "PASSWOERTER NICHT IDENTISCH!";
    } else {
        
        $sqlCheck = "SELECT ID FROM anwender WHERE Name = ?";
        $stmtCheck = mysqli_prepare($con, $sqlCheck);
        mysqli_stmt_bind_param($stmtCheck, "s", $benutzer);
        mysqli_stmt_execute($stmtCheck);
        $resultCheck = mysqli_stmt_get_result($stmtCheck);

        if(mysqli_fetch_assoc($resultCheck)) {
            $meldung = "NAME BEREITS VERGEBEN!";
        } else {
            $sicheresPasswort = password_hash($passw, PASSWORD_DEFAULT);

            $sqlInsert = "INSERT INTO anwender (Name, Passwort) VALUES (?,?)";
            $stmtInsert = mysqli_prepare($con,$sqlInsert);

            mysqli_stmt_bind_param($stmtInsert, "ss", $benutzer, $sicheresPasswort);
        
            if (mysqli_stmt_execute($stmtInsert)){
                $meldung = "ACCOUNT WURDE ANGELEGT!<br>Weiterleitung...";
                $erfolg = true;

                header("refresh:2;url=login.php");
            } else {
                $meldung = "Datenbankfehler!";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Registrieren</title>
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style_neu.css">
    <?php include_once 'backgroundlogik.php'; ?>
    <style>
        /* Spezielles Layout für das Formular */
        .form-wrapper {
            width: 450px;
            text-align: center;
        }

        h2 {
            color: var(--btn-bg);
            margin-bottom: 25px;
            text-shadow: 3px 3px 0 #000;
            font-size: 1.2rem;
            line-height: 1.5;
        }

        label {
            display: block;
            text-align: left;
            margin-bottom: 5px;
            font-size: 0.7rem;
            color: #aaa;
        }

        /* Inputs im dunklen Look */
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 15px;
            margin-bottom: 15px;
            background-color: #1a1a2a; 
            border: 4px solid var(--border-light);
            color: var(--text-color);
            font-family: var(--pixel-font);
            box-sizing: border-box;
            outline: none;
            box-shadow: inset 4px 4px 0 var(--border-dark);
        }
        input:focus { border-color: var(--btn-bg); }

        .link-text {
            margin-top: 20px;
            font-size: 0.6rem;
            line-height: 1.5;
        }
        a { color: var(--btn-bg); text-decoration: none; }
        a:hover { text-decoration: underline; }

        /* Nachrichten Box */
        .msg-box {
            padding: 10px;
            border: 2px solid;
            margin-bottom: 20px;
            font-size: 0.6rem;
            background-color: #3a1a1a;
            color: #ff5555;
            border-color: #ff5555;
        }
        /* Wenn erfolgreich, dann grün */
        .msg-success {
            background-color: #1a3a1a;
            color: #55ff55;
            border-color: #55ff55;
        }
    </style>
</head>
<body>

    <div class="pixel-box form-wrapper">
        
        <h2>NEUEN ACCOUNT<br>ERSTELLEN</h2>

        <?php if ($meldung != ""): ?>
            <div class="msg-box <?php if($erfolg) echo 'msg-success'; ?>">
                <?php echo $meldung; ?>
            </div>
        <?php endif; ?>

        <form action="" method="post">
            <label>NAME:</label>
            <input type="text" required name="user" placeholder="Name...">
            
            <label>PASSWORT:</label>
            <input type="password" required name="pw" placeholder="***">
            
            <label>WIEDERHOLEN:</label>
            <input type="password" required name="pw2" placeholder="***">
            
            <button type="submit" class="pixel-btn action-btn" style="width: 100%; margin-top: 10px;">
                REGISTRIEREN
            </button>
            
            </form>

        <div class="link-text">
            BEREITS EINEN ACCOUNT?<br>
            <a href="login.php">ZUM LOGIN</a>
        </div>

    </div>

</body>
</html>