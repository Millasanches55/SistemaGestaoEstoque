<?php
session_start();
include __DIR__ . '/../conexao.php';

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
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $financeiras[] = $row;
    }
    $stmt->close();
}

// Movimentações de Estoque (cada linha = uma movimentação histórica)
// Estratégia:
// 1) buscar produtos do terreiro
// 2) para cada produto, buscar histórico ORDER BY data_registro DESC (do mais novo para o mais antigo)
// 3) iniciar 'remaining' com quantidade atual do estoque e "rebobinar" para calcular quantidade_anterior para cada movimento
$estoque_mov = [];

// Pega lista de produtos do terreiro
$sql_prod = "SELECT id, produto, quantidade FROM estoque WHERE id_terreiro = ? ORDER BY produto ASC";
$stmt_prod = $conn->prepare($sql_prod);
$stmt_prod->bind_param("i", $id_terreiro);
$stmt_prod->execute();
$res_prod = $stmt_prod->get_result();

while ($prod = $res_prod->fetch_assoc()) {
    $prod_id = (int)$prod['id'];
    $produto_nome = $prod['produto'];
    $quantidade_atual = (int)$prod['quantidade'];

    // Busca histórico deste produto: do mais novo para o mais antigo
   $sql_hist = "SELECT id, quantidade, tipo, data_registro 
             FROM estoque_historico 
             WHERE id_estoque = ? 
             ORDER BY data_registro ASC, id ASC";

    $stmt_hist = $conn->prepare($sql_hist);
    $stmt_hist->bind_param("i", $prod_id);
    $stmt_hist->execute();
    $res_hist = $stmt_hist->get_result();

    // 'remaining' representa o valor do estoque depois das movimentações que já percorremos (começa no atual)
    $remaining = $quantidade_atual;

    while ($h = $res_hist->fetch_assoc()) {
        $mov_qtd = (int)$h['quantidade'];
        $mov_tipo = $h['tipo']; // 'estoque_entrada' ou 'estoque_saida'
        $mov_dt = $h['data_registro'];

        // calcula quantidades antes e depois da movimentação
        if ($mov_tipo === 'estoque_entrada') {
            $q_after = $remaining;
            $q_before = $remaining - $mov_qtd;
            // atualiza remaining para a próxima iteração (mais antiga)
            $remaining = $q_before;
            $entrada = $mov_qtd;
            $saida = 0;
        } else { // estoque_saida
            $q_after = $remaining;
            $q_before = $remaining + $mov_qtd;
            $remaining = $q_before;
            $entrada = 0;
            $saida = $mov_qtd;
        }

        // Tratar data nula/0000-00-00
        if ($mov_dt && $mov_dt !== '0000-00-00 00:00:00') {
            $displayDate = date('d/m/Y H:i', strtotime($mov_dt));
        } else {
            $displayDate = '-';
        }

        $estoque_mov[] = [
            'produto' => $produto_nome,
            'quantidade_anterior' => $q_before,
            'quantidade_atual' => $q_after,
            'entrada' => $entrada,
            'saida' => $saida,
            'data_registro' => $displayDate
        ];
    }

    $stmt_hist->close();
}

// Reordena todas as movimentações do estoque em ordem cronológica (data e ID)
usort($estoque_mov, function ($a, $b) {
    $da = strtotime($a['data_registro']);
    $db = strtotime($b['data_registro']);
    if ($da === $db) return 0;
    return ($da < $db) ? -1 : 1;
});



$stmt_prod->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Lista de Movimentações</title>
    <link rel="stylesheet" href="estilo.css">
    <link rel="stylesheet" href="../style.css">
    <link href='https://cdn.boxicons.com/fonts/basic/boxicons.min.css' rel='stylesheet'>
    <style>
        /* Pequeno destaque visual */
        .entrada { color: green; font-weight: 600; }
        .saida { color: red; font-weight: 600; }
        .small-muted { font-size: 0.9rem; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <br>
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

        <br>
        <h2>📦 Movimentações de Estoque</h2>
        <table class="historico-table">
            <thead>
                <tr>
                    <th>Produto</th>
                    <th>Quantidade Anterior</th>
                    <th>Quantidade Atual</th>
                    <th>Entrada</th>
                    <th>Saída</th>
                    <th>Data</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($estoque_mov)): ?>
                    <tr><td colspan="6">Nenhuma movimentação de estoque encontrada.</td></tr>
                <?php else: ?>
                    <?php foreach ($estoque_mov as $mov): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($mov['produto']); ?></td>
                            <td class="small-muted"><?php echo (int)$mov['quantidade_anterior']; ?></td>
                            <td><?php echo (int)$mov['quantidade_atual']; ?></td>
                            <td class="entrada"><?php echo (int)$mov['entrada']; ?></td>
                            <td class="saida"><?php echo (int)$mov['saida']; ?></td>
                            <td><?php echo $mov['data_registro']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
