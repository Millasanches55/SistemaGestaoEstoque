<?php
require_once 'conexao.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['nome'])) {
    $nome = trim($_POST['nome']);
    $stmt = $pdo->prepare("INSERT INTO nomes (nome) VALUES (:nome)");
    $stmt->execute(['nome' => $nome]);

    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Cadastrar Nome</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Cadastrar Nome</h1>

    <form method="POST">
        <input type="text" name="nome" placeholder="Digite um nome" required>
        <button type="submit">Cadastrar</button>
    </form>

    <a href="index.php"><button>Voltar</button></a>
</body>
</html>