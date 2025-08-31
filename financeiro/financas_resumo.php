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

// --- CONSULTAS SQL PARA CÁLCULO DO SALDO ---
// Consulta para obter o total de arrecadações.
$sql_arrecadacoes = "SELECT SUM(valor) AS total_arrecadacoes FROM financas WHERE id_terreiro = ? AND tipo = 'arrecadacao'";
$arrecadacoes = 0;
if ($stmt_arrecadacoes = $conn->prepare($sql_arrecadacoes)) {
    $stmt_arrecadacoes->bind_param("i", $id_terreiro);
    $stmt_arrecadacoes->execute();
    $result_arrecadacoes = $stmt_arrecadacoes->get_result();
    if ($row = $result_arrecadacoes->fetch_assoc()) {
        $arrecadacoes = $row['total_arrecadacoes'] ?? 0;
    }
    $stmt_arrecadacoes->close();
}

// Consulta para obter o total de despesas.
$sql_despesas = "SELECT SUM(valor) AS total_despesas FROM financas WHERE id_terreiro = ? AND tipo = 'despesa'";
$despesas = 0;
if ($stmt_despesas = $conn->prepare($sql_despesas)) {
    $stmt_despesas->bind_param("i", $id_terreiro);
    $stmt_despesas->execute();
    $result_despesas = $stmt_despesas->get_result();
    if ($row = $result_despesas->fetch_assoc()) {
        $despesas = $row['total_despesas'] ?? 0;
    }
    $stmt_despesas->close();
}

// Calcula o saldo total.
$saldo_total = $arrecadacoes - $despesas;

// --- CONSULTA PARA O HISTÓRICO DE MOVIMENTAÇÕES ---
// Seleciona as últimas 10 movimentações do terreiro.
$sql_historico = "SELECT descricao, valor, data FROM financas WHERE id_terreiro = ? ORDER BY data DESC LIMIT 10";
$historico = [];
if ($stmt_historico = $conn->prepare($sql_historico)) {
    $stmt_historico->bind_param("i", $id_terreiro);
    $stmt_historico->execute();
    $result_historico = $stmt_historico->get_result();
    while ($row = $result_historico->fetch_assoc()) {
        $historico[] = $row;
    }
    $stmt_historico->close();
}

// Fecha a conexão com o banco de dados.
$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Resumo Financeiro</title>
    <link rel="stylesheet" href="estilo.css">
</head>
<body>
    <div class="container">
        <h2>Resumo Financeiro</h2>
        <div class="summary-box">
            <div class="summary-box"><strong>Total de Arrecadações:</strong> <span class="arrecadacoes">R$ <?php echo number_format($arrecadacoes, 2, ',', '.'); ?></span></div>
            <div class="summary-box"><strong>Total de Despesas:</strong> <span class="despesas">R$ <?php echo number_format($despesas, 2, ',', '.'); ?></span></div>
            <hr>
            <div class="summary-box">
                <strong>Saldo Total:</strong>
                <span class="<?php echo ($saldo_total >= 0) ? 'positivo' : 'negativo'; ?>">R$ <?php echo number_format($saldo_total, 2, ',', '.'); ?></span>
            </div>
        </div>

        <div class="historico-table">
            <h3>Histórico Recente</h3>
            <?php if (count($historico) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Descrição</th>
                            <th>Valor</th>
                            <th>Data</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historico as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['descricao']); ?></td>
                                <td>R$ <?php echo number_format($item['valor'], 2, ',', '.'); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($item['data'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Nenhuma movimentação recente encontrada.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
