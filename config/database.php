<?php

$host = "mysql.railway.internal";
$port = "3306";
$dbname = "railway";
$user = "root";
$password = "vlWIQWXKxLDhTjZoRXyxVneMSlcWQzJl";

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
