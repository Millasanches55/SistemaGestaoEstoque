<?php
// Inclui o arquivo de conexão do banco.
include __DIR__ . '/../conexao.php';

// Inicia a sessão para garantir que o ID do terreiro está disponível.
session_start();

// Verifica se o usuário está logado. Se não, redireciona para a página de login.
if (!isset($_SESSION['id_usuario']) || !isset($_SESSION['id_terreiro'])) {
    header("Location: ../index.php");
    exit();
}

$id_terreiro = $_SESSION['id_terreiro'];

// Define o cabeçalho para download do arquivo CSV
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="relatorio_financeiro.csv"');

// Abre um ponteiro para o output do arquivo
$output = fopen('php://output', 'w');

// --- DADOS DA TABELA DE MOVIMENTAÇÕES ---
// Título da primeira tabela
fputcsv($output, ['Relatório de Movimentações Detalhadas']);
// Linha em branco para separar
fputcsv($output, ['']);

// Cabeçalho da tabela
fputcsv($output, ['ID', 'Descrição', 'Tipo', 'Valor', 'Data']);

// Consulta SQL para obter as movimentações
$sql_financas_export = "SELECT id, descricao, tipo, valor, data FROM financas WHERE id_terreiro = ? ORDER BY data DESC";
if ($stmt_export = $conn->prepare($sql_financas_export)) {
    $stmt_export->bind_param("i", $id_terreiro);
    $stmt_export->execute();
    $result_export = $stmt_export->get_result();

    while ($row = $result_export->fetch_assoc()) {
        fputcsv($output, [
            $row['id'],
            $row['descricao'],
            ucfirst($row['tipo']),
            number_format($row['valor'], 2, ',', '.'),
            date('d/m/Y', strtotime($row['data']))
        ]);
    }
    $stmt_export->close();
}

// --- DADOS DOS GRÁFICOS ---
// Linha em branco para separar
fputcsv($output, ['']);
// Título do gráfico de barras
fputcsv($output, ['Dados Mensais - Arrecadações vs. Despesas']);
// Linha em branco para separar
fputcsv($output, ['']);

// Cabeçalho da tabela
fputcsv($output, ['Mês', 'Arrecadações', 'Despesas']);

// Consulta SQL para obter os dados mensais
$sql_mensal = "SELECT
    DATE_FORMAT(data, '%Y-%m') as mes,
    SUM(CASE WHEN tipo = 'arrecadacao' THEN valor ELSE 0 END) AS arrecadacoes,
    SUM(CASE WHEN tipo = 'despesa' THEN valor ELSE 0 END) AS despesas
FROM financas
WHERE id_terreiro = ?
GROUP BY mes
ORDER BY mes";
if ($stmt_mensal = $conn->prepare($sql_mensal)) {
    $stmt_mensal->bind_param("i", $id_terreiro);
    $stmt_mensal->execute();
    $result_mensal = $stmt_mensal->get_result();
    while ($row = $result_mensal->fetch_assoc()) {
        fputcsv($output, [
            $row['mes'],
            number_format($row['arrecadacoes'], 2, ',', '.'),
            number_format($row['despesas'], 2, ',', '.')
        ]);
    }
    $stmt_mensal->close();
}

// Fecha a conexão e o ponteiro do arquivo
$conn->close();
fclose($output);
exit();
