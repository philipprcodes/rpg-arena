<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Sicherheitskontrolle
if (isset($_POST['aktion']) && $_POST['aktion'] == 'Entnahme') {
    if (isset($_SESSION['Kampf']['loot'])) {
        $loot = $_SESSION['Kampf']['loot'];
    } else {
        $loot = array();
        }
    if (!isset($_SESSION['Inventar'])) {
        $_SESSION['Inventar'] = array();
    }


    foreach ($loot as $lootItem) {        
        $name = $lootItem['Name'];
        // Item vorhanden?
        if (isset($_SESSION['Inventar'][$name])) {   
            if (isset($lootItem['Anzahl']) && ($lootItem['Anzahl'])>1) {
            $_SESSION['Inventar'][$name]['Anzahl'] += $lootItem['Anzahl'];
            } else {   
            $_SESSION['Inventar'][$name]['Anzahl'] += 1;
            }
        } else {
            $neuesItem = $lootItem;
            $neuesItem['Anzahl'] = 1;
            $_SESSION['Inventar'][$name] = $neuesItem;
        }
    }


    // Abschluss
    $_SESSION['Kampf']['hat_gelootet'] = true;
    $_SESSION['Kampf']['loot'] = [];
    // Kontext für inventar.php ändern
    $_SESSION['inventarart'] = 'inventar'; 
    unset($_SESSION['inv_context']);
    $_SESSION['inventar'] = true;
    echo "<script>window.location.href='maingame.php';</script>";
    exit;
} else {
    // Ende Sicherheitskontrolle
    header("Location: maingame.php");
    exit;
}
?>