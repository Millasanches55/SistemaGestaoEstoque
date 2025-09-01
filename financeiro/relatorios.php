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

// --- CONSULTA SQL PARA OBTER AS MOVIMENTAÇÕES ---
// A consulta ordena as movimentações pela data em ordem decrescente (mais recente primeiro).
$sql_financas = "SELECT * FROM financas WHERE id_terreiro = ? ORDER BY data DESC, id DESC";
$movimentacoes = [];
if ($stmt_financas = $conn->prepare($sql_financas)) {
    $stmt_financas->bind_param("i", $id_terreiro);
    $stmt_financas->execute();
    $result_financas = $stmt_financas->get_result();
    while ($row = $result_financas->fetch_assoc()) {
        $movimentacoes[] = $row;
    }
    $stmt_financas->close();
}

// Lógica de exportação para Excel (CSV)
if (isset($_GET['exportar']) && $_GET['exportar'] === 'excel') {
    // --- CONSULTA SQL PARA OBTER AS MOVIMENTAÇÕES PARA EXPORTAÇÃO ---
    $sql_financas_export = "SELECT id, descricao, tipo, valor, data FROM financas WHERE id_terreiro = ? ORDER BY data DESC";
    $movimentacoes_export = [];
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($stmt_export = $conn->prepare($sql_financas_export)) {
        $stmt_export->bind_param("i", $id_terreiro);
        $stmt_export->execute();
        $result_export = $stmt_export->get_result();
        while ($row = $result_export->fetch_assoc()) {
            $movimentacoes_export[] = $row;
        }
    $stmt_export->close();
}

// Headers para forçar o download do arquivo CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=relatorio_financeiro_' . date('Y-m-d') . '.csv');

$output = fopen('php://output', 'w');
    
// Escreve os cabeçalhos
fputcsv($output, array('ID', 'Descricao', 'Tipo', 'Valor', 'Data'));

// Escreve os dados
foreach ($movimentacoes_export as $row) {
    fputcsv($output, $row);
}
    
fclose($output);
exit();
}

// --- CONSULTA SQL PARA O GRÁFICO DE BARRAS POR MÊS ---
$sql_mensal = "SELECT
    DATE_FORMAT(data, '%Y-%m') as mes,
    SUM(CASE WHEN tipo = 'arrecadacao' THEN valor ELSE 0 END) AS arrecadacoes,
    SUM(CASE WHEN tipo = 'despesa' THEN valor ELSE 0 END) AS despesas
FROM financas
WHERE id_terreiro = ?
GROUP BY mes
ORDER BY mes";
$dados_mensais = [];
if ($stmt_mensal = $conn->prepare($sql_mensal)) {
    $stmt_mensal->bind_param("i", $id_terreiro);
    $stmt_mensal->execute();
    $result_mensal = $stmt_mensal->get_result();
    while ($row = $result_mensal->fetch_assoc()) {
        $dados_mensais[] = $row;
    }
    $stmt_mensal->close();
}

// Fecha a conexão com o banco de dados.
$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Relatórios</title>
    <link rel="stylesheet" href="estilo.css">
    <!-- Adiciona a biblioteca Plotly para os gráficos -->
    <script src="https://cdn.plot.ly/plotly-2.20.0.min.js"></script>
</head>
<body>
    <div class="container">
        <h2>Relatórios Financeiros</h2>
        
        <div class="nav-menu">
            <a href="exportar.php">Exportar para Excel</a>
        </div>

        <div class="content">

            <h3>Histórico Detalhado</h3>
            <table class="historico-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Descrição</th>
                        <th>Tipo</th>
                        <th>Valor</th>
                        <th>Data</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($movimentacoes) > 0): ?>
                        <?php foreach ($movimentacoes as $mov): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($mov['id']); ?></td>
                                <td><?php echo htmlspecialchars($mov['descricao']); ?></td>
                                <td><?php echo htmlspecialchars($mov['tipo']); ?></td>
                                <td class="<?php echo ($mov['tipo'] == 'arrecadacao') ? 'arrecadacoes' : 'despesas'; ?>">
                                    R$ <?php echo number_format($mov['valor'], 2, ',', '.'); ?>
                                </td>
                                <td><?php echo htmlspecialchars($mov['data']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5">Nenhuma movimentação encontrada.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <br>  
        <hr>
        </div>
        <div class="chart-container">
            <div id="grafico-barras"></div>
        </div>
    </div>
    

    <script>
        // Dados para o gráfico de barras
        const dadosMensais = <?php echo json_encode($dados_mensais); ?>;
        
        const meses = dadosMensais.map(d => d.mes);
        const arrecadacoes = dadosMensais.map(d => d.arrecadacoes);
        const despesas = dadosMensais.map(d => d.despesas);

        // Cria o gráfico de barras
        const trace1 = {
            x: meses,
            y: arrecadacoes,
            name: 'Arrecadações',
            type: 'bar',
            marker: {
                color: 'rgba(50, 171, 96, 0.6)'
            }
        };

        const trace2 = {
            x: meses,
            y: despesas,
            name: 'Despesas',
            type: 'bar',
            marker: {
                color: 'rgba(219, 64, 82, 0.6)'
            }
        };

        const layout = {
            barmode: 'group',
            title: 'Arrecadações e Despesas Mensais',
            xaxis: {
                title: 'Mês'
            },
            yaxis: {
                title: 'Valor (R$)'
            }
        };

        const data = [trace1, trace2];

        Plotly.newPlot('grafico-barras', data, layout);
    </script>
</body>
</html>