<?php
$host = 'localhost';
$dbname = 'pataloco';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $c=$pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME='career'")->fetchColumn();
    if(!$c){$pdo->exec("ALTER TABLE users ADD COLUMN career VARCHAR(100) DEFAULT 'General'");}
} catch (PDOException $e) {
    die("Error BD: " . $e->getMessage());
}
?>
