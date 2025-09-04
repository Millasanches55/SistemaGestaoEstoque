<?php
// Inclui o arquivo de conexão do banco
include __DIR__ . '/../conexao.php';

// Garante sessão ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verifica se o usuário está logado
if (!isset($_SESSION['id_usuario']) || !isset($_SESSION['id_terreiro'])) {
    header("Location: ../index.php");
    exit();
}

$id_terreiro = $_SESSION['id_terreiro'];

// --- CONSULTAS SQL ---
// Arrecadações
$arrecadacoes = 0;
$sql_arrecadacoes = "SELECT SUM(valor) AS total FROM financas WHERE id_terreiro = ? AND tipo = 'arrecadacao'";
if ($stmt = $conn->prepare($sql_arrecadacoes)) {
    $stmt->bind_param("i", $id_terreiro);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $arrecadacoes = $row['total'] ?? 0;
    }
    $stmt->close();
}

// Despesas
$despesas = 0;
$sql_despesas = "SELECT SUM(valor) AS total FROM financas WHERE id_terreiro = ? AND tipo = 'despesa'";
if ($stmt = $conn->prepare($sql_despesas)) {
    $stmt->bind_param("i", $id_terreiro);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $despesas = $row['total'] ?? 0;
    }
    $stmt->close();
}

// Saldo total
$saldo_total = $arrecadacoes - $despesas;

// Histórico de movimentações
$historico = [];
$sql_historico = "SELECT descricao, valor, data FROM financas WHERE id_terreiro = ? ORDER BY data DESC LIMIT 10";
if ($stmt = $conn->prepare($sql_historico)) {
    $stmt->bind_param("i", $id_terreiro);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $historico[] = $row;
    }
    $stmt->close();
}
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
            <h3>Total de Arrecadações:</h3>
            <span class="arrecadacoes">R$ <?php echo number_format($arrecadacoes, 2, ',', '.'); ?></span>
        </div>

        <div class="summary-box">
            <strong>Total de Despesas:</strong>
            <span class="despesas">R$ <?php echo number_format($despesas, 2, ',', '.'); ?></span>
        </div>

        <hr>

        <div class="summary-box">
            <strong>Saldo Total:</strong>
            <span class="<?php echo ($saldo_total >= 0) ? 'positivo' : 'negativo'; ?>">
                R$ <?php echo number_format($saldo_total, 2, ',', '.'); ?>
            </span>
        </div>
        <h2>Histórico Recente</h2>
            <?php if (count($historico) > 0): ?>
                <div>
                    <table>
                        <thead>
                            <tr>
                                <th class="tabela-header">Descrição</th>
                                <th class="tabela-header">Valor</th>
                                <th class="tabela-header">Data</th>
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
                </div>
            <?php else: ?>
                <p>Nenhuma movimentação recente encontrada.</p>
            <?php endif; ?>
    </div>
</body>
</html>
