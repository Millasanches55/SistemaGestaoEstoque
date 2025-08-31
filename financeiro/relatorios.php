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

// --- CONSULTA SQL PARA OBTER AS MOVIMENTAÇÕES ---
// A consulta ordena as movimentações pela data em ordem decrescente (mais recente primeiro).
$sql_financas = "SELECT * FROM financas WHERE id_terreiro = ? ORDER BY data DESC, id DESC";
$movimentacoes = [];
if ($stmt_financas = $conn->prepare($sql_financas)) {
    $stmt_financas->bind_param("i", $id_terreiro);
    $stmt_financas->execute();
    $result_financas = $stmt_financas->get_result();
    while ($row = $result_financas->fetch_assoc()) {
        $movimentacoes[] = $row;
    }
    $stmt_financas->close();
}

// Fecha a conexão com o banco de dados.
$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Relatório Financeiro</title>
    <link rel="stylesheet" href="estilo.css">
</head>
<body>
    <div class="container">
        <div class="historico-table">
            <h2>Relatório de Movimentações Financeiras</h2>
            
            <?php if (empty($movimentacoes)): ?>
                <p>Nenhuma movimentação financeira encontrada.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tipo</th>
                            <th>Descrição</th>
                            <th>Valor</th>
                            <th>Data</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($movimentacoes as $movimentacao): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($movimentacao['id']); ?></td>
                                <td class="<?php echo ($movimentacao['tipo'] === 'arrecadacao') ? 'arrecadacao-cell' : 'despesa-cell'; ?>">
                                    <?php echo htmlspecialchars(ucfirst($movimentacao['tipo'])); ?>
                                </td>
                                <td><?php echo htmlspecialchars($movimentacao['descricao']); ?></td>
                                <td class="<?php echo ($movimentacao['tipo'] === 'arrecadacao') ? 'positivo' : 'negativo'; ?>">
                                    R$ <?php echo number_format($movimentacao['valor'], 2, ',', '.'); ?>
                                </td>
                                <td><?php echo htmlspecialchars(date('d/m/Y', strtotime($movimentacao['data']))); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
