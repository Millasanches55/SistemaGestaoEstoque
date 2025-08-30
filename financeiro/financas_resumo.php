<?php
// Inclui o arquivo de conexão do banco
include 'conexao.php'; 

// Inicia a sessão para obter o ID do terreiro
session_start();

$id_terreiro = $_SESSION['id_terreiro'] ?? 1; // Exemplo de uso de ID padrão se a sessão não estiver definida

// Prepara a consulta SQL para calcular o resumo financeiro
$sql = "SELECT 
            SUM(CASE WHEN tipo='arrecadacao' THEN valor ELSE 0 END) AS total_arrecadado,
            SUM(CASE WHEN tipo='despesa' THEN valor ELSE 0 END) AS total_despesas
        FROM financas
        WHERE id_terreiro = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_terreiro);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

$saldo = $result['total_arrecadado'] - $result['total_despesas'];

$stmt->close();
$conn->close();
?>

<h2>Resumo Financeiro</h2>
<p>Total Arrecadado: <b>R$ <?= number_format($result['total_arrecadado'], 2, ',', '.') ?></b></p>
<p>Total Despesas: <b>R$ <?= number_format($result['total_despesas'], 2, ',', '.') ?></b></p>
<p><strong>Saldo: R$ <?= number_format($saldo, 2, ',', '.') ?></strong></p>