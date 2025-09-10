<?php
// Inclui o arquivo de conexão do banco e inicia a sessão.
include __DIR__ . '/../conexao.php';

// Verifica se o usuário está logado. Se não, redireciona para a página de login.
if (!isset($_SESSION['id_usuario']) || $_SESSION["tipo"] !== "adm") {
    header("Location: ../index.php");
    exit();
}

$id_terreiro = $_SESSION['id_terreiro'];

// Array para armazenar os dados do gráfico de linha (histórico de saldo).
$dados_saldo = [];

// Arrays para armazenar os dados dos gráficos de barras (desempenho mensal).
$arrecadacoes_mensais = [];
$despesas_mensais = [];
$entradas_estoque_mensais = [];
$saidas_estoque_mensais = [];
$meses = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];

try {
    // --- Consulta para o gráfico de saldo (gráfico de linha) ---
    // Considera apenas arrecadações e despesas, pois são movimentações de caixa.
    $sql_saldo_hist = "
        SELECT data, tipo, valor
        FROM financas
        WHERE id_terreiro = ? AND (tipo = 'arrecadacao' OR tipo = 'despesa')
        ORDER BY data ASC
    ";
    if ($stmt_saldo_hist = $conn->prepare($sql_saldo_hist)) {
        $stmt_saldo_hist->bind_param("i", $id_terreiro);
        $stmt_saldo_hist->execute();
        $result_saldo_hist = $stmt_saldo_hist->get_result();
        $saldo_acumulado = 0;
        while ($row = $result_saldo_hist->fetch_assoc()) {
            $saldo_acumulado += ($row['tipo'] == 'arrecadacao') ? $row['valor'] : -$row['valor'];
            $dados_saldo[] = [
                'data' => $row['data'],
                'saldo' => $saldo_acumulado
            ];
        }
        $stmt_saldo_hist->close();
    }

    // --- Consulta para o gráfico de barras (desempenho mensal) ---
    // A função DATE_FORMAT() é compatível com MySQL/MariaDB.
    $sql_mensal = "
        SELECT
            DATE_FORMAT(data, '%Y-%m') as mes_ano,
            SUM(CASE WHEN tipo = 'arrecadacao' THEN valor ELSE 0 END) as total_arrecadacoes,
            SUM(CASE WHEN tipo = 'despesa' THEN valor ELSE 0 END) as total_despesas,
            SUM(CASE WHEN tipo = 'entrada_estoque' THEN valor ELSE 0 END) as total_entradas_estoque,
            SUM(CASE WHEN tipo = 'saida_estoque' THEN valor ELSE 0 END) as total_saidas_estoque
        FROM financas
        WHERE id_terreiro = ?
        GROUP BY mes_ano
        ORDER BY mes_ano ASC
    ";
    if ($stmt_mensal = $conn->prepare($sql_mensal)) {
        $stmt_mensal->bind_param("i", $id_terreiro);
        $stmt_mensal->execute();
        $result_mensal = $stmt_mensal->get_result();
        while ($row = $result_mensal->fetch_assoc()) {
            $mes_numero = date('n', strtotime($row['mes_ano'] . '-01')) - 1; // Ajusta para o índice do array de meses.
            $arrecadacoes_mensais[] = ['mes' => $meses[$mes_numero], 'valor' => $row['total_arrecadacoes']];
            $despesas_mensais[] = ['mes' => $meses[$mes_numero], 'valor' => $row['total_despesas']];
            $entradas_estoque_mensais[] = ['mes' => $meses[$mes_numero], 'valor' => $row['total_entradas_estoque']];
            $saidas_estoque_mensais[] = ['mes' => $meses[$mes_numero], 'valor' => $row['total_saidas_estoque']];
        }
        $stmt_mensal->close();
    }

    // --- Consulta para o gráfico de pizza (tipos de movimentação) ---
    $sql_tipos = "
        SELECT tipo, SUM(valor) AS total
        FROM financas
        WHERE id_terreiro = ?
        GROUP BY tipo
    ";
    $dados_tipos = [];
    if ($stmt_tipos = $conn->prepare($sql_tipos)) {
        $stmt_tipos->bind_param("i", $id_terreiro);
        $stmt_tipos->execute();
        $result_tipos = $stmt_tipos->get_result();
        while ($row = $result_tipos->fetch_assoc()) {
            $dados_tipos[$row['tipo']] = $row['total'];
        }
        $stmt_tipos->close();
    }

} catch (Exception $e) {
    // Tratamento de erro em caso de falha na consulta ou conexão.
    echo "<p style='color:red;'>Erro ao carregar os dados: " . $e->getMessage() . "</p>";
} finally {
    // Garante que a conexão é fechada.
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Financeiro</title>
    <link rel="stylesheet" href="../estilo.css">
    <script src="https://cdn.plot.ly/plotly-latest.min.js"></script>
    <link href='https://cdn.boxicons.com/fonts/basic/boxicons.min.css' rel='stylesheet'>
</head>
<body>
    <div class="container">
        <h2><i class='bx  bx-dashboard-alt'  ></i> Dashboard Financeiro</h2>

        <div class="dashboard-grid">
            <!-- Gráfico de Saldo Histórico (Linha) -->
            <div id="saldo-historico-chart" class="chart-container"></div>

            <!-- Gráfico de Desempenho Mensal (Barras) - Agora inclui as movimentações de estoque -->
            <div id="desempenho-mensal-chart" class="chart-container"></div>

            <!-- Gráfico de Arrecadações vs Despesas vs Estoque (Pizza) -->
            <div id="tipos-chart" class="chart-container"></div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {

            // Dados do PHP para os gráficos
            const dadosSaldo = <?php echo json_encode($dados_saldo); ?>;
            const arrecadacoesMensais = <?php echo json_encode($arrecadacoes_mensais); ?>;
            const despesasMensais = <?php echo json_encode($despesas_mensais); ?>;
            const entradasEstoqueMensais = <?php echo json_encode($entradas_estoque_mensais); ?>;
            const saidasEstoqueMensais = <?php echo json_encode($saidas_estoque_mensais); ?>;
            const dadosTipos = <?php echo json_encode($dados_tipos); ?>;

            // --- Gráfico de Saldo Histórico (Linha) ---
            if (dadosSaldo.length > 0) {
                const trace1 = {
                    x: dadosSaldo.map(d => d.data),
                    y: dadosSaldo.map(d => d.saldo),
                    type: 'scatter',
                    mode: 'lines+markers',
                    name: 'Saldo Acumulado',
                    line: { color: '#4CAF50' }
                };
                const layout1 = {
                    title: 'Saldo Acumulado ao Longo do Tempo',
                    xaxis: { title: 'Data' },
                    yaxis: { title: 'Saldo (R$)' }
                };
                Plotly.newPlot('saldo-historico-chart', [trace1], layout1, {responsive: true});
            } else {
                document.getElementById('saldo-historico-chart').innerHTML = '<p class="no-data">Nenhum dado de saldo encontrado.</p>';
            }

            // --- Gráfico de Movimentações Mensais (Barras) ---
            if (arrecadacoesMensais.length > 0 || despesasMensais.length > 0 || entradasEstoqueMensais.length > 0 || saidasEstoqueMensais.length > 0) {
                const arrecadacoes = {
                    x: arrecadacoesMensais.map(d => d.mes),
                    y: arrecadacoesMensais.map(d => d.valor),
                    name: 'Arrecadações',
                    type: 'bar',
                    marker: { color: '#4CAF50' }
                };
                const despesas = {
                    x: despesasMensais.map(d => d.mes),
                    y: despesasMensais.map(d => d.valor),
                    name: 'Despesas',
                    type: 'bar',
                    marker: { color: '#F44336' }
                };
                const entradasEstoque = {
                    x: entradasEstoqueMensais.map(d => d.mes),
                    y: entradasEstoqueMensais.map(d => d.valor),
                    name: 'Entradas de Estoque',
                    type: 'bar',
                    marker: { color: '#3498db' }
                };
                const saidasEstoque = {
                    x: saidasEstoqueMensais.map(d => d.mes),
                    y: saidasEstoqueMensais.map(d => d.valor),
                    name: 'Saídas de Estoque',
                    type: 'bar',
                    marker: { color: '#6e0861ff' }
                };
                const layout2 = {
                    title: 'Movimentações Financeiras e de Estoque Mensais',
                    xaxis: { title: 'Mês' },
                    yaxis: { title: 'Valor (R$)' },
                    barmode: 'group'
                };
                Plotly.newPlot('desempenho-mensal-chart', [arrecadacoes, despesas, entradasEstoque, saidasEstoque], layout2, {responsive: true});
            } else {
                 document.getElementById('desempenho-mensal-chart').innerHTML = '<p class="no-data">Nenhum dado mensal encontrado.</p>';
            }

            // --- Gráfico de Arrecadações vs Despesas vs Estoque (Pizza) ---
            if (Object.keys(dadosTipos).length > 0) {
                const tipoLabels = Object.keys(dadosTipos).map(label => {
                    if (label === 'arrecadacao') return 'Arrecadações';
                    if (label === 'despesa') return 'Despesas';
                    if (label === 'entrada_estoque') return 'Entradas de Estoque';
                    if (label === 'saida_estoque') return 'Saídas de Estoque';
                    return label;
                });
                
                const colorMap = {
                    'arrecadacao': '#4CAF50',
                    'despesa': '#F44336',
                    'entrada_estoque': '#3498db',
                    'saida_estoque': '#6e0861ff'
                };
                const tipoColors = Object.keys(dadosTipos).map(key => colorMap[key]);

                const data2 = [{
                    values: Object.values(dadosTipos),
                    labels: tipoLabels,
                    type: 'pie',
                    marker: { colors: tipoColors }
                }];
                const layout3 = {
                    title: 'Distribuição de Movimentações',
                    height: 400,
                    width: 500
                };
                Plotly.newPlot('tipos-chart', data2, layout3, {responsive: true});
            } else {
                document.getElementById('tipos-chart').innerHTML = '<p class="no-data">Nenhum dado de tipo encontrado.</p>';
            }
        });
    </script>
</body>
</html>
