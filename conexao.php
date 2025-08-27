<?php
$host = "localhost";
$user = "root";      // usuário padrão do XAMPP
$pass = "";          // senha padrão (vazia no XAMPP)
$db   = "db_terreiro"; // nome do banco que você criou

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Erro de conexão: " . $conn->connect_error);
}
?>
