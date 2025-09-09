<?php
// Inclui o arquivo de conexão do banco, que deve existir na mesma pasta ou em um caminho acessível
include __DIR__ . '/../conexao.php';

// Inicia a sessão para garantir que o ID do terreiro está disponível
session_start();

// Verifica se o usuário está logado. Se não, redireciona para a página de login.
if (!isset($_SESSION['id_usuario']) || !isset($_SESSION['id_terreiro'])) {
    header("Location: ../index.php");
    exit();
}

$id_terreiro = $_SESSION['id_terreiro'];

// --- Lógica para o resumo financeiro ---
$arrecadacoes = 0;
$despesas = 0;
$sql_financas = "SELECT tipo, SUM(valor) AS total FROM financas WHERE id_terreiro = ? GROUP BY tipo";
if ($stmt_financas = $conn->prepare($sql_financas)) {
    $stmt_financas->bind_param("i", $id_terreiro);
    $stmt_financas->execute();
    $result_financas = $stmt_financas->get_result();
    while ($row = $result_financas->fetch_assoc()) {
        if ($row['tipo'] == 'arrecadacao') {
            $arrecadacoes = $row['total'];
        } else if ($row['tipo'] == 'despesa') {
            $despesas = $row['total'];
        }
    }
    $stmt_financas->close();
}
$saldo = $arrecadacoes - $despesas;

// --- Lógica para o histórico financeiro ---
$historico = [];
$sql_historico = "SELECT tipo, descricao, valor, data, tipo_original FROM financas WHERE id_terreiro = ? ORDER BY data DESC LIMIT 10";
if ($stmt_historico = $conn->prepare($sql_historico)) {
    $stmt_historico->bind_param("i", $id_terreiro);
    $stmt_historico->execute();
    $result_historico = $stmt_historico->get_result();
    while ($row = $result_historico->fetch_assoc()) {
        $historico[] = $row;
    }
    $stmt_historico->close();
}

// --- Lógica para o resumo de estoque ---
$estoque = [];
$sql_estoque = "SELECT produto, SUM(quantidade) AS quantidade FROM estoque WHERE id_terreiro = ? GROUP BY produto";
if ($stmt_estoque = $conn->prepare($sql_estoque)) {
    $stmt_estoque->bind_param("i", $id_terreiro);
    $stmt_estoque->execute();
    $result_estoque = $stmt_estoque->get_result();
    while ($row = $result_estoque->fetch_assoc()) {
        $estoque[] = $row;
    }
    $stmt_estoque->close();
}

// Fecha a conexão com o banco de dados
$conn->close();

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Relatórios</title>
    <link rel="stylesheet" href="../style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <section class="card">
        <div class="nav-menu">
            <a href="../painel.php" class="botao">Voltar Ao Painel</a>
            <a href="index.php?action=resumo" class="botao">Resumo Financeiro</a>
        </div>
        
        <h2>Relatórios Detalhados</h2>
        
        <!-- Seção de Resumo Financeiro -->
        <div class="content">
            <h3>Resumo Financeiro</h3>
            <div class="summary-box">
                <p>Arrecadações: <span class="arrecadacoes">R$ <?php echo number_format($arrecadacoes, 2, ',', '.'); ?></span></p>
                <p>Despesas: <span class="despesas">R$ <?php echo number_format($despesas, 2, ',', '.'); ?></span></p>
                <p>Saldo Atual: <span class="saldo-value"><?php echo number_format($saldo, 2, ',', '.'); ?></span></p>
            </div>
        </div>
        
        <hr>
        
        <!-- Seção de Relatório Financeiro -->
        <div class="content">
            <h3>Histórico Financeiro</h3>
            <table id="tabela-usuarios">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Tipo</th>
                        <th>Descrição</th>
                        <th>Valor</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($historico) > 0): ?>
                        <?php foreach ($historico as $item): ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($item['data'])); ?></td>
                                <td>
                                    <?php 
                                        if ($item['tipo_original'] === 'estoque_entrada') {
                                            echo 'Entrada de Estoque';
                                        } else if ($item['tipo_original'] === 'estoque_saida') {
                                            echo 'Saída de Estoque';
                                        } else {
                                            echo ucfirst($item['tipo']);
                                        }
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($item['descricao']); ?></td>
                                <td>R$ <?php echo number_format($item['valor'], 2, ',', '.'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4">Nenhuma movimentação financeira encontrada.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <hr>
        
        <!-- Seção de Relatório de Estoque -->
        <div class="content">
            <h3>Resumo de Estoque</h3>
            <table id="tabela-usuarios">
                <thead>
                    <tr>
                        <th>Produto</th>
                        <th>Quantidade</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($estoque) > 0): ?>
                        <?php foreach ($estoque as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['produto']); ?></td>
                                <td><?php echo htmlspecialchars($item['quantidade']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="2">Nenhum item no estoque.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</body>
</html>
