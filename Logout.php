<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once('dbconfig.php');
include_once('funktionen.php');

if (isset($_SESSION['CharID'])) {
    saveGame($con, $_SESSION['CharID']);
}

session_unset();
session_destroy();

header("Location: login.php");
exit;
?>