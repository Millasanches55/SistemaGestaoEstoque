<?php
include __DIR__ . '/SimpleXLSXGen.php';
include __DIR__ . '/../conexao.php';
session_start();

if (!isset($_SESSION['id_usuario']) || !isset($_SESSION['id_terreiro']) || $_SESSION['tipo'] !== 'adm') {
    header("Location: ../index.php");
    exit();
}

$id_terreiro = $_SESSION['id_terreiro'];

// -------------------------
// Resumo Financeiro
// -------------------------
$total_arrecadacao = 0;
$total_despesa = 0;
$total_estoque_entrada = 0;
$total_estoque_saida = 0;
$total_receitas = 0;
$total_despesas = 0;
$saldo = 0;

$sql_resumo = "
    SELECT
        COALESCE(SUM(CASE WHEN tipo = 'arrecadacao' THEN valor ELSE 0 END), 0) AS total_arrecadacao,
        COALESCE(SUM(CASE WHEN tipo = 'despesa' THEN valor ELSE 0 END), 0) AS total_despesa,
        COALESCE(SUM(CASE WHEN tipo = 'estoque_entrada' THEN valor ELSE 0 END), 0) AS total_estoque_entrada,
        COALESCE(SUM(CASE WHEN tipo = 'estoque_saida' THEN valor ELSE 0 END), 0) AS total_estoque_saida,
        COALESCE(SUM(CASE WHEN tipo IN ('arrecadacao', 'estoque_saida') THEN valor ELSE 0 END), 0) AS total_receitas,
        COALESCE(SUM(CASE WHEN tipo IN ('despesa', 'estoque_entrada') THEN valor ELSE 0 END), 0) AS total_despesas,
        COALESCE(SUM(CASE WHEN tipo IN ('arrecadacao', 'estoque_saida') THEN valor ELSE 0 END), 0)
            - COALESCE(SUM(CASE WHEN tipo IN ('despesa', 'estoque_entrada') THEN valor ELSE 0 END), 0) AS saldo
    FROM financas
    WHERE id_terreiro = ?
";

if ($stmt = $conn->prepare($sql_resumo)) {
    $stmt->bind_param("i", $id_terreiro);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $total_arrecadacao = (float) ($row['total_arrecadacao'] ?? 0);
        $total_despesa = (float) ($row['total_despesa'] ?? 0);
        $total_estoque_entrada = (float) ($row['total_estoque_entrada'] ?? 0);
        $total_estoque_saida = (float) ($row['total_estoque_saida'] ?? 0);
        $total_receitas = (float) ($row['total_receitas'] ?? 0);
        $total_despesas = (float) ($row['total_despesas'] ?? 0);
        $saldo = (float) ($row['saldo'] ?? 0);
    }

    $stmt->close();
}

// -------------------------
// Prepara os dados para a planilha
// -------------------------
$data = [];

// 💰 Resumo Financeiro
$data[] = ['💰 RESUMO FINANCEIRO'];
$data[] = [];
$data[] = ['Arrecadações', 'Despesas', 'Entradas de Estoque', 'Saídas de Estoque', 'Total de Receitas', 'Total de Despesas', 'Saldo'];
$data[] = [$total_arrecadacao, $total_despesa, $total_estoque_entrada, $total_estoque_saida, $total_receitas, $total_despesas, $saldo];
$data[] = [];

// 📄 Movimentações Detalhadas
$data[] = ['📄 MOVIMENTAÇÕES DETALHADAS'];
$data[] = [];
$data[] = ['ID','Descrição','Tipo','Valor','Data'];

$sql_financas = "SELECT id, descricao, tipo, valor, data FROM financas WHERE id_terreiro = ? ORDER BY data DESC";
if ($stmt = $conn->prepare($sql_financas)) {
    $stmt->bind_param("i", $id_terreiro);
    $stmt->execute();
    $result = $stmt->get_result();
    $linha_num = 0;
    while ($row = $result->fetch_assoc()) {
        $tipoEmoji = in_array($row['tipo'], ['arrecadacao', 'estoque_saida']) ? '💰' : '📉';
        // Zebra striping: adiciona prefixo alternado
        $prefixo = $linha_num % 2 === 0 ? '• ' : '- ';
        $data[] = [
            $prefixo . $row['id'],
            $prefixo . $row['descricao'],
            $prefixo . $tipoEmoji . ' ' . ucfirst($row['tipo']),
            $row['valor'],
            date('d/m/Y', strtotime($row['data']))
        ];
        $linha_num++;
    }
    $stmt->close();
}

$data[] = [];

// 📦 Estoque Completo
$data[] = ['📦 RESUMO COMPLETO DE ESTOQUE'];
$data[] = [];

$result_colunas = $conn->query("SHOW COLUMNS FROM estoque");
$colunas = [];
while ($col = $result_colunas->fetch_assoc()) $colunas[] = $col['Field'];
$data[] = $colunas; // cabeçalho do estoque

$sql_estoque = "SELECT * FROM estoque WHERE id_terreiro = ?";
if ($stmt = $conn->prepare($sql_estoque)) {
    $stmt->bind_param("i", $id_terreiro);
    $stmt->execute();
    $result = $stmt->get_result();
    $linha_num = 0;
    while ($row = $result->fetch_assoc()) {
        $linha = [];
        $prefixo = $linha_num % 2 === 0 ? '• ' : '- ';
        foreach ($colunas as $col) {
            $linha[] = $prefixo . $row[$col];
        }
        $data[] = $linha;
        $linha_num++;
    }
    $stmt->close();
}

$conn->close();

// -------------------------
// Cria XLSX e força download
// -------------------------
$xlsx = new \Shuchkin\SimpleXLSXGen();
$xlsx->addSheet($data,'Relatorio');
$xlsx->downloadAs('relatorio_financeiro.xlsx');
exit();
?>
