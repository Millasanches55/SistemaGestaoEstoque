let dados = movimentacoes;

function prepararDados(filtro = "todos") {
    let datas = [];
    let arrecadacoes = [];
    let despesas = [];

    dados.forEach(m => {
        if (filtro === "todos" || m.tipo === filtro) {
            if (!datas.includes(m.data)) datas.push(m.data);
        }
    });

    datas.sort();

    datas.forEach(data => {
        let totalArrec = 0, totalDesp = 0;
        dados.forEach(m => {
            if (m.data === data) {
                if (m.tipo === "arrecadacao" && (filtro === "todos" || filtro === "arrecadacao")) {
                    totalArrec += parseFloat(m.valor);
                } else if (m.tipo === "despesa" && (filtro === "todos" || filtro === "despesa")) {
                    totalDesp += parseFloat(m.valor);
                }
            }
        });
        arrecadacoes.push(totalArrec);
        despesas.push(totalDesp);
    });

    return { datas, arrecadacoes, despesas };
}

function desenharGraficos(filtro = "todos") {
    const { datas, arrecadacoes, despesas } = prepararDados(filtro);

    // 📈 Gráfico de Linha
    let trace1 = {
        x: datas,
        y: arrecadacoes,
        mode: 'lines+markers',
        name: 'Arrecadações',
        line: { color: 'green' }
    };

    let trace2 = {
        x: datas,
        y: despesas,
        mode: 'lines+markers',
        name: 'Despesas',
        line: { color: 'red' }
    };

    let layoutLinha = {
        title: 'Evolução Financeira',
        xaxis: { title: 'Data' },
        yaxis: { title: 'Valor (R$)' },
        dragmode: 'zoom'
    };

    Plotly.newPlot('graficoLinha', [trace1, trace2], layoutLinha, {scrollZoom: true});

    // 🍕 Gráfico de Pizza
    let totalArrec = arrecadacoes.reduce((a, b) => a + b, 0);
    let totalDesp = despesas.reduce((a, b) => a + b, 0);

    let dataPizza = [{
        labels: ['Arrecadações', 'Despesas'],
        values: [totalArrec, totalDesp],
        type: 'pie',
        textinfo: "label+percent",
        hole: .4
    }];

    let layoutPizza = { title: 'Distribuição Geral' };

    Plotly.newPlot('graficoPizza', dataPizza, layoutPizza);
}

function filtrarDados() {
    let filtro = document.getElementById("filtroTipo").value;
    desenharGraficos(filtro);
}

// Inicializa
desenharGraficos();