<?php
// Inclui o arquivo de conexão do banco.
include __DIR__ . '/../conexao.php';


// Verifica se o usuário está logado. Se não, redireciona para a página de login.
if (!isset($_SESSION['id_usuario']) || !isset($_SESSION['id_terreiro'])) {
    header("Location: ../index.php");
    exit();
}

$id_terreiro = $_SESSION['id_terreiro'];

// --- BUSCA DOS DADOS PARA O DASHBOARD ---
$movimentacoes = [];
$sql = "SELECT data, valor, tipo FROM financas WHERE id_terreiro = ? ORDER BY data ASC";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $id_terreiro);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $movimentacoes[] = $row;
    }
    $stmt->close();
}

$dados_json = json_encode($movimentacoes);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Financeiro</title>
    <script src="https://cdn.plot.ly/plotly-latest.min.js"></script>
    <link rel="stylesheet" href="estilo.css">
</head>
<body>
    <div class="container">
        <div class="dashboard-container">
            <h2>Dashboard Financeiro</h2>
            <div id="grafico_linha"></div>
            <div id="grafico_pizza"></div>
        </div>
    </div>

    <script>
        const movimentacoes = JSON.parse('<?php echo $dados_json; ?>');
        
        // Dados para o gráfico de linha (histórico de saldo)
        let historico_saldo = {};
        movimentacoes.forEach(mov => {
            if (!historico_saldo[mov.data]) {
                historico_saldo[mov.data] = 0;
            }
            if (mov.tipo === 'arrecadacao') {
                historico_saldo[mov.data] += parseFloat(mov.valor);
            } else {
                historico_saldo[mov.data] -= parseFloat(mov.valor);
            }
        });

        // Calculando o saldo cumulativo
        const datas = Object.keys(historico_saldo).sort();
        const saldos = [];
        let saldo_acumulado = 0;
        datas.forEach(data => {
            saldo_acumulado += historico_saldo[data];
            saldos.push(saldo_acumulado);
        });

        const trace1 = {
            x: datas,
            y: saldos,
            mode: 'lines+markers',
            type: 'scatter',
            name: 'Saldo Histórico'
        };
        const layout1 = {
            title: 'Histórico de Saldo ao Longo do Tempo',
            xaxis: { title: 'Data' },
            yaxis: { title: 'Saldo (R$)' }
        };
        Plotly.newPlot('grafico_linha', [trace1], layout1);

        // Dados para o gráfico de pizza (total de arrecadações e despesas)
        let total_arrecadacoes = 0;
        let total_despesas = 0;
        movimentacoes.forEach(mov => {
            if (mov.tipo === 'arrecadacao') {
                total_arrecadacoes += parseFloat(mov.valor);
            } else {
                total_despesas += parseFloat(mov.valor);
            }
        });

        const trace2 = {
            values: [total_arrecadacoes, total_despesas],
            labels: ['Arrecadações', 'Despesas'],
            type: 'pie',
            hole: .4,
            marker: {
                colors: ['#28a745', '#dc3545']
            }
        };
        const layout2 = {
            title: 'Arrecadações vs. Despesas',
            height: 400,
            width: 500
        };
        Plotly.newPlot('grafico_pizza', [trace2], layout2);
    </script>
</body>
</html>
