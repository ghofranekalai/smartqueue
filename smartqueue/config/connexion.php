<?php
// ============================================================
// SmartQueue – Connexion PDO à la base de données MySQL
// ============================================================
<?php
// config/connexion.php

$host = getenv('DB_HOST');       // e.g. ep-mute-wildflower-xxx.us-east-2.db.netlify.com
$dbname = getenv('DB_NAME');     // netlifydb
$user = getenv('DB_USER');
$pass = getenv('DB_PASS');

try {
    $conn = new PDO(
        "pgsql:host=$host;port=5432;dbname=$dbname;sslmode=require",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (Exception $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
?>
