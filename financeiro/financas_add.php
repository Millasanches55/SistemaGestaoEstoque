<?php
// Inclui o arquivo de conexão do banco.
include __DIR__ . '/../conexao.php';


// Verifica se o usuário está logado. Se não, redireciona para a página de login.
if (!isset($_SESSION['id_usuario']) || !isset($_SESSION['id_terreiro'])) {
    header("Location: ../index.php");
    exit();
}

$id_terreiro = $_SESSION['id_terreiro'];

// Lógica para processar o formulário
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $tipo = $_POST['tipo'];
    $descricao = $_POST['descricao'];
    $valor = $_POST['valor'];
    $data = $_POST['data'];

    // Prepara a query SQL para inserir os dados.
    $sql = "INSERT INTO financas (id_terreiro, tipo, descricao, valor, data) VALUES (?, ?, ?, ?, ?)";
    
    // Usa declarações preparadas para evitar injeção de SQL.
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("isssd", $id_terreiro, $tipo, $descricao, $valor, $data);
        
        if ($stmt->execute()) {
            // Sucesso na inserção. Redireciona para o painel.
            header("Location: financeiro/index.php?action=resumo");
            exit();
        } else {
            echo "Erro: " . $stmt->error;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adicionar Movimentação</title>
    <link rel="stylesheet" href="/estilo.css">
</head>
<body>
    <div class="container">
        <h2>Adicionar Movimentação Financeira</h2>
        
        <form class="form-movimentacao" method="POST" action="financas_add.php">
            <div class="form-group">
                <label for="tipo">Tipo:</label>
                <select id="tipo" name="tipo">
                    <option value="arrecadacao">Arrecadação</option>
                    <option value="despesa">Despesa</option>
                </select>
            </div>

            <div class="form-group">
                <label for="descricao">Descrição:</label>
                <input type="text" id="descricao" name="descricao" required>
            </div>

            <div class="form-group">
                <label for="valor">Valor:</label>
                <input type="number" id="valor" name="valor" step="0.01" required>
            </div>

            <div class="form-group">
                <label for="data">Data:</label>
                <input type="date" id="data" name="data" required>
            </div>

            <button type="submit" class="btn-submit">Salvar</button>
        </form>
    </div>
</body>
</html>
