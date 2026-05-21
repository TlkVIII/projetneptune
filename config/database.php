<?php

$host = "kodama.proxy.rlwy.net";
$port = "50211";
$dbname = "railway";
$user = "root";
$password = "VKzdYWoMMlKNRRFAfPTINNfTTCEknviI";

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8",
        $user,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_PERSISTENT => false,
            PDO::ATTR_TIMEOUT => 5
        ]
    );

} catch (PDOException $e) {
    die("Erreur connexion : " . $e->getMessage());
}
