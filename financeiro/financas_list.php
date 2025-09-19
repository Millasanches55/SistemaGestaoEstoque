<?php
include __DIR__ . '/../conexao.php';
session_start();

// Verifica se o usuário está logado e é ADM
if (!isset($_SESSION['id_usuario']) || $_SESSION["tipo"] !== "adm") {
    header("Location: ../index.php");
    exit();
}

$id_terreiro = $_SESSION['id_terreiro'] ?? 1;

// Movimentações Financeiras (arrecadação e despesa)
$financeiras = [];
$sql = "SELECT id, descricao, tipo, valor, data 
        FROM financas 
        WHERE id_terreiro = ? AND (tipo = 'arrecadacao' OR tipo = 'despesa')
        ORDER BY data DESC";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $id_terreiro);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $financeiras[] = $row;
    }
    $stmt->close();
}

// Movimentações de Estoque (entrada e saída)
$estoque_mov = [];
$sql = "
SELECT 
    e.produto,
    e.quantidade AS quantidade_atual,
    COALESCE(SUM(CASE WHEN h.tipo = 'estoque_entrada' THEN h.quantidade END), 0) AS entrada,
    COALESCE(SUM(CASE WHEN h.tipo = 'estoque_saida' THEN h.quantidade END), 0) AS saida,
    MAX(h.data_registro) AS data_movimentacao
FROM estoque e
LEFT JOIN estoque_historico h 
    ON e.id = h.id_estoque
WHERE e.id_terreiro = ?
GROUP BY e.id, e.produto, e.quantidade
ORDER BY e.produto ASC
";


$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_terreiro);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $estoque_mov[] = $row;
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
        <h2>💰 Movimentações Financeiras</h2>
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
                <?php if (empty($financeiras)): ?>
                    <tr><td colspan="4">Nenhuma movimentação financeira encontrada.</td></tr>
                <?php else: ?>
                    <?php foreach ($financeiras as $mov): ?>
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
                    <th>Produto</th>
                    <th>Quantidade Atual</th>
                    <th>Entrada</th>
                    <th>Saída</th>
                    <th>Data da Última Movimentação</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($estoque_mov)): ?>
                    <tr><td colspan="5">Nenhum produto encontrado.</td></tr>
                <?php else: ?>
                    <?php foreach ($estoque_mov as $mov): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($mov['produto']); ?></td>
                            <td><?php echo $mov['quantidade_atual']; ?></td>
                            <td><?php echo $mov['entrada']; ?></td>
                            <td><?php echo $mov['saida']; ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($mov['data_movimentacao'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
