<?php
header('Content-Type: application/json; charset=utf-8');
$retour = new \stdClass();
if (isset($_GET['long3857']) && isset($_GET['lat3857'])) {
    $long3857 = $_GET['long3857'];
    $lat3857 = $_GET['lat3857'];
    $X = 20037508.34;
    $longitude = ($long3857 * 180) / $X;
    $latitude = $lat3857 / ($X / 180);
    $exponent = (pi() / 180) * $latitude;
    $latitude = atan(pow(exp(1), $exponent));
    $latitude = $latitude / (pi() / 360);
    $latitude = $latitude - 90;
    $retour->{'long4326'} = $longitude;
    $retour->{'lat4326'} = $latitude;
}
else{
    $retour->{'error'} = "Erreur dans l'url";
}

echo json_encode($retour);
