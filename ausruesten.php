<pre>
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once('dbconfig.php');
include_once("funktionen.php");

$Itemname=$_POST['itemname'];
$aktion=$_POST['aktion'];
$target=$_POST['target'];
if (isset($_POST['schattenindex'])){
$schattenindex=$_POST['schattenindex'];
}
// echo $aktion;
// echo $target;
// print_r($Itemname);
$Itemzwischen=array();

// =======================================================================
// SCHATTENMONSTER AUSRÜSTEN
// =======================================================================

if($aktion=='ausruestenschatten'){
    $Itemzwischen=$_SESSION['Schattendex'][$schattenindex];
} else {
$Itemzwischen=$_SESSION['Inventar'][$Itemname];
}



if ($aktion=='ausruesten' || $aktion=='ausruestenschatten'){

$art=$_SESSION['Inventar'][$Itemname]['Gegenstandsart'];

if ($_SESSION['Inventar'][$Itemname]['Anzahl']>1){
            $_SESSION['Inventar'][$Itemname]['Anzahl']--;
        } else {
            unset($_SESSION['Inventar'][$Itemname]);
        }

// echo $art;
if (isset($Itemzwischen['SchattenID']) && $Itemzwischen['SchattenID']>0){
    $Platz=5;
} elseif ($Itemzwischen['WaffenID']>0){
    $Platz=4;
} else {
if ($art == 'Kopf'){
    $Platz=1;
}
if ($art == 'Brust'){
    $Platz=2;
}
if ($art == 'Bein'){
    $Platz=3;
}
}

if ($Platz==5){
    $_SESSION['Ausruestung'][$Platz]=$Itemzwischen;
    $_SESSION['Ausruestung'][$Platz]['IstLeer']=false;
    $_SESSION['Schattenmonster']=$Itemzwischen;
} else {


// Wenn Platz leer ist...
if ($_SESSION['Ausruestung'][$Platz]['IstLeer']){
    $_SESSION['Ausruestung'][$Platz]=$Itemzwischen;
    $_SESSION['Ausruestung'][$Platz]['IstLeer']=false;
    if(array_key_exists($name,$_SESSION['Inventar'])){
       $_SESSION['Inventar'][$name]['Anzahl']--; 
    }

} else 
// Wenn Platz belegt
{
    // Altes Item in Session Inventar
    $name=$_SESSION['Ausruestung'][$Platz]['Name'];

        if(array_key_exists($name,$_SESSION['Inventar'])){
            $_SESSION['Inventar'][$name]['Anzahl']++;
            $_SESSION['Ausruestung'][$Platz]=$Itemzwischen;
            $_SESSION['Ausruestung'][$Platz]['IstLeer']=false;
        } else {
            $_SESSION['Inventar'][$name]=$_SESSION['Ausruestung'][$Platz];
            $_SESSION['Ausruestung'][$Platz]=$Itemzwischen;
            $_SESSION['Ausruestung'][$Platz]['IstLeer']=false;
        }
    
}
}
} else {
    // Trankart Check
if ($Itemzwischen['HP']>0){
$Potion="HP";
$Potioncheck="HPmax";
}
if ($Itemzwischen['Mana']>0){
$Potion="Mana";
$Potioncheck="Manamax";
}
if ($Itemzwischen['Ausdauer']>0){
$Potion="Ausdauer";
$Potioncheck="Ausdauermax";
}
if ($target=="Spieler"){
    // check ob trank was bringt
    if ($_SESSION['Spieler'][$Potion]<$_SESSION['Spieler'][$Potioncheck]){
        $_SESSION['Spieler'][$Potion]+=$Itemzwischen[$Potion];
        //falls neu > Max
        if ($_SESSION['Spieler'][$Potion]>$_SESSION['Spieler'][$Potioncheck]){
            $_SESSION['Spieler'][$Potion]=$_SESSION['Spieler'][$Potioncheck];
        }
    if ($_SESSION['Inventar'][$Itemname]['Anzahl']>1){
            $_SESSION['Inventar'][$Itemname]['Anzahl']--;
        } else {
            unset($_SESSION['Inventar'][$Itemname]);
        }    
    }
}
if ($target=="schattenmonster" && $Potion='HP' && $_SESSION[$target][$Potion]<$_SESSION[$target][$Potioncheck]){
      $_SESSION[$target][$Potion]+=$Itemzwischen[$Potion];
        //falls neu > Max
        if ($_SESSION[$target][$Potion]>$_SESSION[$target][$Potioncheck]){
            $_SESSION[$target][$Potion]=$_SESSION[$target][$Potioncheck];
        }  
}

// $target Spieler Schattenmonster




}
$CharID=$_SESSION['CharID'];
statsupdate($con, $CharID);
header("Location: maingame.php");
exit;
?>
</pre>