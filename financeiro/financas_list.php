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
$sql = "SELECT f.id, f.descricao, f.tipo, e.produto, e.quantidade, f.data
        FROM financas f
        JOIN estoque e ON f.id_terreiro = e.id_terreiro
        WHERE f.id_terreiro = ? AND (f.tipo = 'entrada_estoque' OR f.tipo = 'saida_estoque')
        ORDER BY f.data DESC";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $id_terreiro);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $estoque_mov[] = $row;
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
                    <th>Data</th>
                    <th>Produto</th>
                    <th>Quantidade</th>
                    <th>Tipo</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($estoque_mov)): ?>
                    <tr><td colspan="4">Nenhuma movimentação de estoque encontrada.</td></tr>
                <?php else: ?>
                    <?php foreach ($estoque_mov as $mov): ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($mov['data'])); ?></td>
                            <td><?php echo htmlspecialchars($mov['produto']); ?></td>
                            <td><?php echo $mov['quantidade']; ?></td>
                            <td>
                                <?php echo ($mov['tipo'] == 'entrada_estoque') ? 'Entrada' : 'Saída'; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
