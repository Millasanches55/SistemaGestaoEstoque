<?php
// Inclui o arquivo de conexão do banco
include 'conexao.php'; 

// Inicia a sessão para obter o ID do terreiro
session_start();

$id_terreiro = $_SESSION['id_terreiro'] ?? 1; // Exemplo de uso de ID padrão

// Consulta todas as movimentações do banco de dados para o ID do terreiro
$sql = "SELECT * FROM financas WHERE id_terreiro = ? ORDER BY data DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_terreiro);
$stmt->execute();
$result = $stmt->get_result();
$movimentacoes = $result->fetch_all(MYSQLI_ASSOC);

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Financeiro</title>
    <!-- Inclui a biblioteca Plotly.js -->
    <script src="https://cdn.plot.ly/plotly-latest.min.js"></script>
</head>
<body>

    <h2>📊 Dashboard Financeiro</h2>

    <label for="filtroTipo">Filtrar por:</label>
    <select id="filtroTipo" onchange="desenharGraficos()">
        <option value="todos">Todos</option>
        <option value="arrecadacao">Arrecadação</option>
        <option value="despesa">Despesa</option>
    </select>

    <div id="graficoLinha" style="width:100%;height:400px;"></div>
    <div id="graficoPizza" style="width:100%;height:400px;"></div>

    <script>
        // Dados vindos do PHP -> JSON
        const movimentacoes = <?= json_encode($movimentacoes); ?>;

        function prepararDados(filtro = "todos") {
            const dadosFiltrados = movimentacoes.filter(m => filtro === "todos" || m.tipo === filtro);
            
            // Mapeia e agrupa os dados por data para somar os valores
            const agrupadoPorData = dadosFiltrados.reduce((acc, curr) => {
                if (!acc[curr.data]) {
                    acc[curr.data] = { arrecadacao: 0, despesa: 0 };
                }
                if (curr.tipo === "arrecadacao") {
                    acc[curr.data].arrecadacao += parseFloat(curr.valor);
                } else {
                    acc[curr.data].despesa += parseFloat(curr.valor);
                }
                return acc;
            }, {});

            const datas = Object.keys(agrupadoPorData).sort();
            const arrecadacoes = datas.map(data => agrupadoPorData[data].arrecadacao);
            const despesas = datas.map(data => agrupadoPorData[data].despesa);

            return { datas, arrecadacoes, despesas };
        }

        function desenharGraficos() {
            const filtro = document.getElementById("filtroTipo").value;
            const { datas, arrecadacoes, despesas } = prepararDados(filtro);

            // Gráfico de Linha (Evolução Financeira)
            const traceLinhaArrecadacao = {
                x: datas,
                y: arrecadacoes,
                mode: 'lines+markers',
                name: 'Arrecadações',
                line: { color: 'green' }
            };
            const traceLinhaDespesa = {
                x: datas,
                y: despesas,
                mode: 'lines+markers',
                name: 'Despesas',
                line: { color: 'red' }
            };
            const layoutLinha = {
                title: 'Evolução Financeira',
                xaxis: { title: 'Data' },
                yaxis: { title: 'Valor (R$)' }
            };
            Plotly.newPlot('graficoLinha', [traceLinhaArrecadacao, traceLinhaDespesa], layoutLinha, { scrollZoom: true });

            // Gráfico de Pizza (Distribuição Geral)
            const totalArrecadacao = arrecadacoes.reduce((sum, val) => sum + val, 0);
            const totalDespesa = despesas.reduce((sum, val) => sum + val, 0);
            const dataPizza = [{
                labels: ['Arrecadações', 'Despesas'],
                values: [totalArrecadacao, totalDespesa],
                type: 'pie',
                textinfo: "label+percent",
                hole: .4
            }];
            const layoutPizza = { title: 'Distribuição Geral' };
            Plotly.newPlot('graficoPizza', dataPizza, layoutPizza);
        }

        // Inicializa os gráficos ao carregar a página
        desenharGraficos();
    </script>

</body>
</html>
