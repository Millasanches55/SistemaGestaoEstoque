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
$arrecadacoes = 0;
$despesas = 0;

$sql_resumo = "SELECT tipo, SUM(valor) AS total FROM financas WHERE id_terreiro = ? GROUP BY tipo";
if ($stmt = $conn->prepare($sql_resumo)) {
    $stmt->bind_param("i", $id_terreiro);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        if ($row['tipo'] == 'arrecadacao') $arrecadacoes = $row['total'];
        else if ($row['tipo'] == 'despesa') $despesas = $row['total'];
    }
    $stmt->close();
}

$saldo = $arrecadacoes - $despesas;

// -------------------------
// Prepara os dados para a planilha
// -------------------------
$data = [];

// 💰 Resumo Financeiro
$data[] = ['💰 RESUMO FINANCEIRO'];
$data[] = [];
$data[] = ['Arrecadações','Despesas','Saldo'];
$data[] = [$arrecadacoes, $despesas, $saldo];
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
        $tipoEmoji = $row['tipo'] == 'arrecadacao' ? '💰' : '📉';
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
