<?php
session_start();
include __DIR__ . '/../conexao.php';

// Verifica se o usuário é adm
if (!isset($_SESSION['id_usuario']) || $_SESSION["tipo"] !== "adm") {
    header("Location: ../index.php");
    exit();
}

$id_terreiro = $_SESSION['id_terreiro'] ?? 1;

// Movimentações financeiras (arrecadação e despesa)
$movimentacoes_fin = [];
$sql_fin = "SELECT id, descricao, tipo, valor, data 
            FROM financas 
            WHERE id_terreiro = ? 
              AND (tipo = 'arrecadacao' OR tipo = 'despesa')
            ORDER BY data DESC";
if ($stmt = $conn->prepare($sql_fin)) {
    $stmt->bind_param("i", $id_terreiro);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $movimentacoes_fin[] = $row;
    }
    $stmt->close();
}

// Movimentações de estoque (entrada e saída)
$movimentacoes_estoque = [];
$sql_est = "SELECT id, descricao, tipo, valor, data 
            FROM financas 
            WHERE id_terreiro = ? 
              AND (tipo = 'estoque_entrada' OR tipo = 'estoque_saida')
            ORDER BY data DESC";
if ($stmt = $conn->prepare($sql_est)) {
    $stmt->bind_param("i", $id_terreiro);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $movimentacoes_estoque[] = $row;
    }
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Lista de Movimentações</title>
    <link rel="stylesheet" href="estilo.css">
</head>
<body>
    <div class="container">
        <h2>📊 Movimentações Financeiras</h2>
        <table class="historico-table">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Descrição</th>
                    <th>Tipo</th>
                    <th>Valor</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($movimentacoes_fin)): ?>
                    <tr><td colspan="4">Nenhuma movimentação financeira encontrada.</td></tr>
                <?php else: ?>
                    <?php foreach ($movimentacoes_fin as $mov): ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($mov['data'])); ?></td>
                            <td><?php echo htmlspecialchars($mov['descricao']); ?></td>
                            <td><?php echo ucfirst($mov['tipo']); ?></td>
                            <td>
                                <span style="color: <?php echo ($mov['tipo'] == 'arrecadacao') ? 'green' : 'red'; ?>;">
                                    R$ <?php echo number_format($mov['valor'], 2, ',', '.'); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <h2>📦 Movimentações de Estoque</h2>
        <table class="historico-table">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Descrição</th>
                    <th>Tipo</th>
                    <th>Valor</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($movimentacoes_estoque)): ?>
                    <tr><td colspan="4">Nenhuma movimentação de estoque encontrada.</td></tr>
                <?php else: ?>
                    <?php foreach ($movimentacoes_estoque as $mov): ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($mov['data'])); ?></td>
                            <td><?php echo htmlspecialchars($mov['descricao']); ?></td>
                            <td><?php echo ($mov['tipo'] === 'estoque_entrada') ? 'Entrada' : 'Saída'; ?></td>
                            <td>
                                <span style="color: <?php echo ($mov['tipo'] == 'estoque_entrada') ? 'blue' : 'orange'; ?>;">
                                    R$ <?php echo number_format($mov['valor'], 2, ',', '.'); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
