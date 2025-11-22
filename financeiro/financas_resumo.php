<?php
include __DIR__ . '/../conexao.php';

if (!isset($_SESSION['id_usuario']) || $_SESSION["tipo"] !== "adm") {
    header("Location: ../index.php");
    exit();
}

$id_terreiro = $_SESSION['id_terreiro'];

// --- Resumo financeiro completo ---
$sql = "
SELECT
  COALESCE(SUM(CASE WHEN tipo = 'arrecadacao' THEN valor END), 0) AS total_arrecadacao,
  COALESCE(SUM(CASE WHEN tipo = 'despesa' THEN valor END), 0) AS total_despesa,
  COALESCE(SUM(CASE WHEN tipo = 'estoque_entrada' THEN valor END), 0) AS total_estoque_entrada,
  COALESCE(SUM(CASE WHEN tipo = 'estoque_saida' THEN valor END), 0) AS total_estoque_saida,
  COALESCE(SUM(CASE WHEN tipo IN ('arrecadacao','estoque_saida') THEN valor END), 0) AS total_receitas,
  COALESCE(SUM(CASE WHEN tipo IN ('despesa','estoque_entrada') THEN valor END), 0) AS total_despesas,
  COALESCE(SUM(CASE WHEN tipo IN ('arrecadacao','estoque_saida') THEN valor END), 0)
    - COALESCE(SUM(CASE WHEN tipo IN ('despesa','estoque_entrada') THEN valor END), 0) AS saldo
FROM financas
WHERE id_terreiro = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_terreiro);
$stmt->execute();
$res = $stmt->get_result();
$tot = $res->fetch_assoc();
$stmt->close();

$total_arrecadacao = (float) ($tot['total_arrecadacao'] ?? 0);
$total_despesa = (float) ($tot['total_despesa'] ?? 0);
$total_estoque_entrada = (float) ($tot['total_estoque_entrada'] ?? 0);
$total_estoque_saida = (float) ($tot['total_estoque_saida'] ?? 0);
$total_receitas = (float) ($tot['total_receitas'] ?? 0);
$total_despesas = (float) ($tot['total_despesas'] ?? 0);
$saldo = (float) ($tot['saldo'] ?? 0);

// --- Histórico recente ---
$sql_hist = "
    SELECT id, tipo, descricao, valor, data
    FROM financas
    WHERE id_terreiro = ?
    ORDER BY data DESC, id DESC
    LIMIT 10
";
$stmt = $conn->prepare($sql_hist);
$stmt->bind_param("i", $id_terreiro);
$stmt->execute();
$res_hist = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Resumo Financeiro</title>
    <link rel="stylesheet" href="../<?php echo $tema; ?>">
    <link href='https://cdn.boxicons.com/fonts/basic/boxicons.min.css' rel='stylesheet'>
</head>
<body>

<div class="card">
    <h2><i class='bxr  bx-dollar-circle' style='font-size: 1.5em;' ></i> Resumo Financeiro</h2>
    <div style="max-width: 400px; display: flex; justify-content: center; margin: auto;">
        <div>
            <p><strong>Arrecadações:</strong> <span style='color: green;'>R$ <?php echo number_format($total_arrecadacao, 2, ',', '.'); ?></span></p>
            <p><strong>Despesas:</strong> <span class="despesas">R$ <?php echo number_format($total_despesa, 2, ',', '.'); ?></span></p>
            <p><strong>Entrada de Estoque:</strong> <span class="despesas">R$ <?php echo number_format($total_estoque_entrada, 2, ',', '.'); ?></span></p>
            <p><strong>Saída de Estoque:</strong> <span style='color: green;'>R$ <?php echo number_format($total_estoque_saida, 2, ',', '.'); ?></span></p>
            <hr>
            <p><strong>Total Receitas:</strong> <span style='color: green;'>R$ <?php echo number_format($total_receitas, 2, ',', '.'); ?></span></p>
            <p><strong>Total Despesas:</strong> <span class="despesas">R$ <?php echo number_format($total_despesas, 2, ',', '.'); ?></span></p>
            <p><strong>Saldo:</strong>
                <span class="saldo-value" style="color: <?php echo ($saldo >= 0) ? 'green' : 'red'; ?>;">
                    R$ <?php echo number_format($saldo, 2, ',', '.'); ?>
                </span>
            </p>
        </div>
    </div>
</div>

<div class='card' style="margin-top: 20px;">
    <h2>Últimas Movimentações</h2>
    <table id="tabela-usuarios">
        <thead>
            <tr>
                <th>ID</th>
                <th>Data</th>
                <th>Tipo</th>
                <th>Descrição</th>
                <th>Valor</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $res_hist->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo date('d/m/Y', strtotime($row['data'])); ?></td>
                    <td><?php echo ucfirst($row['tipo']); ?></td>
                    <td><?php echo htmlspecialchars($row['descricao']); ?></td>
                    <td style="color: <?php echo in_array($row['tipo'], ['arrecadacao','estoque_saida']) ? 'green' : 'red'; ?>;">
                        R$ <?php echo number_format($row['valor'], 2, ',', '.'); ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

</body>
</html>
