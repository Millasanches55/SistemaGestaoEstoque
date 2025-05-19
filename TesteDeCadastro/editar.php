<?php
require_once 'conexao.php';

$id = $_GET['id'] ?? null;

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM nomes WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $nomeAtual = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$nomeAtual) {
        die("Nome não encontrado.");
    }
} else {
    die("ID inválido.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['nome'])) {
    $novoNome = trim($_POST['nome']);
    $stmt = $pdo->prepare("UPDATE nomes SET nome = :nome WHERE id = :id");
    $stmt->execute(['nome' => $novoNome, 'id' => $id]);

    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Editar Nome</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Editar Nome</h1>

    <form method="POST">
        <input type="text" name="nome" value="<?= htmlspecialchars($nomeAtual['nome']) ?>" required>
        <button type="submit">Salvar Alterações</button>
    </form>

    <a href="index.php"><button>Voltar</button></a>
</body>
</html>
