<?php
$host = "localhost";
$user = "root";     
$pass = "";          
$db   = "db_terreiro"; 

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Erro de conexão: " . $conn->connect_error);
}
?>
