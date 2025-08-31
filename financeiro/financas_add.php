<?php
// Inclui o arquivo de conexão do banco
include __DIR__ . '/../conexao.php';

// Inicia a sessão para garantir que o ID do terreiro está disponível
session_start();

// Verifica se o usuário está logado. Se não, redireciona para a página de login.
if (!isset($_SESSION['id_usuario']) || !isset($_SESSION['id_terreiro'])) {
    header("Location: ../index.php");
    exit();
}

$id_terreiro = $_SESSION['id_terreiro'] ?? 1;

// Processa o formulário se a requisição for POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validação e sanitização dos dados
    $tipo = $_POST['tipo'] ?? null;
    $descricao = $_POST['descricao'] ?? null;
    $valor = $_POST['valor'] ?? null;
    $data = $_POST['data'] ?? null;

    if ($tipo && $descricao && $valor && $data) {
        $sql = "INSERT INTO financas (descricao, tipo, valor, data, id_terreiro) VALUES (?, ?, ?, ?, ?)";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("ssdsi", $descricao, $tipo, $valor, $data, $id_terreiro);
            if ($stmt->execute()) {
                // Redireciona com uma mensagem de sucesso
                header("Location: financas.php?status=success");
                exit();
            } else {
                // Redireciona com uma mensagem de erro
                header("Location: financas.php?status=error");
                exit();
            }
            $stmt->close();
        }
    } else {
        // Redireciona com uma mensagem de erro de dados
        header("Location: financas.php?status=error_data");
        exit();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Registrar Movimentação</title>
    <link rel="stylesheet" href="estilo.css">
</head>
<body>
    <div class="container">
        <h2>Registrar Movimentação</h2>
        <form method="POST" action="financas_add.php">
            <label>Tipo:</label>
            <select name="tipo">
                <option value="arrecadacao">Arrecadação</option>
                <option value="despesa">Despesa</option>
            </select><br><br>

            <label>Descrição:</label>
            <input type="text" name="descricao" required><br><br>

            <label>Valor:</label>
            <input type="number" step="0.01" name="valor" required><br><br>

            <label>Data:</label>
            <input type="date" name="data" required><br><br>

            <button type="submit">Salvar</button>
        </form>
    </div>
</body>
</html>
