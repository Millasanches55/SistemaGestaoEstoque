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

// --- CONSULTA SQL PARA OBTER AS MOVIMENTAÇÕES FINANCEIRAS ---
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

// --- CONSULTA SQL PARA O GRÁFICO DE BARRAS POR MÊS (FINANÇAS) ---
$sql_mensal_financas = "SELECT
    DATE_FORMAT(data, '%Y-%m') as mes,
    SUM(CASE WHEN tipo = 'arrecadacao' THEN valor ELSE 0 END) AS arrecadacoes,
    SUM(CASE WHEN tipo = 'despesa' THEN valor ELSE 0 END) AS despesas
FROM financas
WHERE id_terreiro = ?
GROUP BY mes
ORDER BY mes";
$dados_mensais_financas = [];
if ($stmt_mensal_financas = $conn->prepare($sql_mensal_financas)) {
    $stmt_mensal_financas->bind_param("i", $id_terreiro);
    $stmt_mensal_financas->execute();
    $result_mensal_financas = $stmt_mensal_financas->get_result();
    while ($row = $result_mensal_financas->fetch_assoc()) {
        $dados_mensais_financas[] = $row;
    }
    $stmt_mensal_financas->close();
}

// --- CONSULTA SQL PARA O RELATÓRIO DE ESTOQUE ---
$sql_estoque = "SELECT * FROM estoque WHERE id_terreiro = ? ORDER BY data_registro DESC, id DESC";
$movimentacoes_estoque = [];
if ($stmt_estoque = $conn->prepare($sql_estoque)) {
    $stmt_estoque->bind_param("i", $id_terreiro);
    $stmt_estoque->execute();
    $result_estoque = $stmt_estoque->get_result();
    while ($row = $result_estoque->fetch_assoc()) {
        $movimentacoes_estoque[] = $row;
    }
    $stmt_estoque->close();
}

// --- CONSULTA SQL PARA O GRÁFICO DE ESTOQUE ---
// A consulta agrupa as movimentações por tipo (compra/doacao) e por mês
$sql_mensal_estoque = "SELECT
    DATE_FORMAT(data_registro, '%Y-%m') as mes,
    SUM(CASE WHEN origem = 'compra' THEN quantidade ELSE 0 END) AS compras,
    SUM(CASE WHEN origem = 'doacao' THEN quantidade ELSE 0 END) AS doacoes
FROM estoque
WHERE id_terreiro = ?
GROUP BY mes
ORDER BY mes";
$dados_mensais_estoque = [];
if ($stmt_mensal_estoque = $conn->prepare($sql_mensal_estoque)) {
    $stmt_mensal_estoque->bind_param("i", $id_terreiro);
    $stmt_mensal_estoque->execute();
    $result_mensal_estoque = $stmt_mensal_estoque->get_result();
    while ($row = $result_mensal_estoque->fetch_assoc()) {
        $dados_mensais_estoque[] = $row;
    }
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
        <h2>Relatórios</h2>
        
        <div class="nav-menu">
            <a href="../painel.php">Voltar para o Painel</a>
            <a href="exportar.php">Exportar para Excel</a>
        </div>
        
        <!-- === RELATÓRIO FINANCEIRO === -->
        <div class="content">
            <h3>Relatório Financeiro</h3>
            <div class="chart-container">
                <div id="grafico-barras-financas"></div>
            </div>
            
            <h3>Histórico Detalhado (Finanças)</h3>
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
                            <td colspan="5">Nenhuma movimentação financeira encontrada.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <br><br><hr>

        <!-- === RELATÓRIO DE ESTOQUE === -->
        <div class="content">
            <h3>Relatório de Estoque</h3>
            <div class="chart-container">
                <div id="grafico-barras-estoque"></div>
            </div>
            
            <h3>Histórico Detalhado (Estoque)</h3>
            <table class="historico-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Produto</th>
                        <th>Quantidade</th>
                        <th>Origem</th>
                        <th>Data</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($movimentacoes_estoque) > 0): ?>
                        <?php foreach ($movimentacoes_estoque as $mov): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($mov['id']); ?></td>
                                <td><?php echo htmlspecialchars($mov['produto']); ?></td>
                                <td><?php echo htmlspecialchars($mov['quantidade']); ?></td>
                                <td><?php echo htmlspecialchars($mov['origem']); ?></td>
                                <td><?php echo htmlspecialchars($mov['data_registro']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5">Nenhuma movimentação de estoque encontrada.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <script>
        // Dados para o gráfico de barras financeiro
        const dadosMensaisFinancas = <?php echo json_encode($dados_mensais_financas); ?>;
        
        const mesesFinancas = dadosMensaisFinancas.map(d => d.mes);
        const arrecadacoes = dadosMensaisFinancas.map(d => d.arrecadacoes);
        const despesas = dadosMensaisFinancas.map(d => d.despesas);

        // Cria o gráfico de barras financeiro
        const trace1 = {
            x: mesesFinancas,
            y: arrecadacoes,
            name: 'Arrecadações',
            type: 'bar',
            marker: {
                color: 'rgba(50, 171, 96, 0.6)'
            }
        };

        const trace2 = {
            x: mesesFinancas,
            y: despesas,
            name: 'Despesas',
            type: 'bar',
            marker: {
                color: 'rgba(219, 64, 82, 0.6)'
            }
        };

        const layoutFinancas = {
            barmode: 'group',
            title: 'Arrecadações e Despesas Mensais',
            xaxis: {
                title: 'Mês'
            },
            yaxis: {
                title: 'Valor (R$)'
            }
        };

        const dataFinancas = [trace1, trace2];

        Plotly.newPlot('grafico-barras-financas', dataFinancas, layoutFinancas);

        // Dados para o gráfico de barras de estoque
        const dadosMensaisEstoque = <?php echo json_encode($dados_mensais_estoque); ?>;
        
        const mesesEstoque = dadosMensaisEstoque.map(d => d.mes);
        const compras = dadosMensaisEstoque.map(d => d.compras);
        const doacoes = dadosMensaisEstoque.map(d => d.doacoes);

        // Cria o gráfico de barras de estoque
        const trace3 = {
            x: mesesEstoque,
            y: compras,
            name: 'Compras',
            type: 'bar',
            marker: {
                color: 'rgba(50, 171, 96, 0.6)'
            }
        };

        const trace4 = {
            x: mesesEstoque,
            y: doacoes,
            name: 'Doações',
            type: 'bar',
            marker: {
                color: 'rgba(54, 162, 235, 0.6)'
            }
        };

        const layoutEstoque = {
            barmode: 'group',
            title: 'Entradas de Estoque por Mês',
            xaxis: {
                title: 'Mês'
            },
            yaxis: {
                title: 'Quantidade'
            }
        };

        const dataEstoque = [trace3, trace4];

        Plotly.newPlot('grafico-barras-estoque', dataEstoque, layoutEstoque);
    </script>
</body>
</html>
