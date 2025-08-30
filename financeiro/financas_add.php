<?php
// Inclui o arquivo de conexão do banco
include 'conexao.php'; 

// Inicia a sessão para obter o ID do terreiro
session_start();

// Verifica se a sessão e a variável POST estão definidas para evitar erros
if (isset($_SESSION['id_terreiro']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_terreiro = $_SESSION['id_terreiro'];
    $tipo = $_POST['tipo'];
    $descricao = $_POST['descricao'];
    $valor = $_POST['valor'];
    $data = $_POST['data'];

    // Prepara a consulta SQL para evitar injeção de SQL
    $sql = "INSERT INTO financas (id_terreiro, tipo, descricao, valor, data) 
            VALUES (?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issds", $id_terreiro, $tipo, $descricao, $valor, $data);

    if ($stmt->execute()) {
        echo "Movimentação registrada com sucesso!";
    } else {
        echo "Erro ao registrar a movimentação: " . $stmt->error;
    }

    $stmt->close();
} else {
    echo "Erro: Dados não enviados ou sessão de usuário não encontrada.";
}

$conn->close();
?>

<!-- Formulário HTML para registrar a movimentação -->
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