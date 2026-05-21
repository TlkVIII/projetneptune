<?php

$host = "kodama.proxy.rlwy.net";
$port = "50211";
$dbname = "railway";
$user = "root";
$password = "VKzdYWoMMlKNRRFAfPTINNfTTCEknviI";

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname",
        $user,
        $password
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch(PDOException $e) {
    die("Erreur connexion : " . $e->getMessage());
}
?>
