<?php
// ============================================================
// SmartQueue – Connexion PDO à la base de données MySQL
// ============================================================
$host = "localhost";
$db   = "smartqueue_db";
$user = "root";      // à adapter selon votre configuration
$pass = "";          // mot de passe MySQL

try {
    $conn = new PDO(
        "mysql:host=$host;dbname=$db;charset=utf8mb4",
        $user,
        $pass
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
?>
