<?php
require_once 'conexao.php';

$id = $_GET['id'] ?? null;

if ($id) {
    $stmt = $pdo->prepare("DELETE FROM nomes WHERE id = :id");
    $stmt->execute(['id' => $id]);
}

header("Location: index.php");
exit;