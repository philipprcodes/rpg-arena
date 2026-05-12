<?php
// bg_logik.php

// Standardbild für Startseite und so

$bgImage = "BGStage13.webp"; 

// Prüfen, ob wir eingeloggt sind und einen Fortschritt haben
if (isset($_SESSION['stage_level'])) {
    $fortschritt = $_SESSION['stage_level'];

    if ($fortschritt <= 3) {
        $bgImage = "BGStage13.webp";      // Wald (Stage 1-3)
    } elseif ($fortschritt <= 6) {
        $bgImage = "BGStage6.webp";    // Goblinlager (Stage 4-6)
    } elseif ($fortschritt <= 9) {
        $bgImage = "BGStage9.webp";      // Winterdorf? (Stage 7-9)
    } elseif ($fortschritt <= 12) {
        $bgImage = "BGStage12.webp";      // Endgame
    } else {
        $bgImage = "BGStageGladiators.webp";
    }
}

// CSS-Style
?>
<style>
    body {
        background-image: 
            linear-gradient(rgba(0, 0, 0, 0.3), rgba(0, 0, 0, 0.3)),
            url("Images/Backgrounds/<?php echo $bgImage; ?>") !important; 
            background-size: cover;       
            background-position: center;  
            background-repeat: no-repeat; 
            background-attachment: fixed;
    }
</style>