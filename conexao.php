
<?php
$host = 'localhost';
$dbname = 'db_agence_teste';
$user = 'root';
$pass = 'admin';
$port = 3307;

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET NAMES 'utf8'");
} catch (PDOException $e) {
    die("ERRO: Falha ao conectar ao banco de dados. " . $e->getMessage());
}
?>
