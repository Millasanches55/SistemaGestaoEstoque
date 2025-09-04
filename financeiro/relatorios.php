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

// Variáveis para armazenar o saldo financeiro
$total_arrecadacoes = 0;
$total_despesas = 0;
$saldo_financeiro = 0;

// Consulta SQL para obter o resumo financeiro
$sql_financas = "SELECT SUM(CASE WHEN tipo = 'arrecadacao' THEN valor ELSE 0 END) AS arrecadado,
                       SUM(CASE WHEN tipo = 'despesa' THEN valor ELSE 0 END) AS despesas
                FROM financas
                WHERE id_terreiro = ?";

if ($stmt_financas = $conn->prepare($sql_financas)) {
    $stmt_financas->bind_param("i", $id_terreiro);
    $stmt_financas->execute();
    $result_financas = $stmt_financas->get_result();
    $resumo = $result_financas->fetch_assoc();
    
    $total_arrecadacoes = $resumo['arrecadado'] ?? 0;
    $total_despesas = $resumo['despesa'] ?? 0;
    $saldo_financeiro = $total_arrecadacoes - $total_despesas;
    
    $stmt_financas->close();
}

// Array para armazenar os dados de estoque
$estoque = [];
// Consulta SQL para obter o saldo do estoque por produto
$sql_estoque = "SELECT produto, SUM(quantidade) AS total_quantidade
                FROM estoque
                WHERE id_terreiro = ?
                GROUP BY produto";

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios</title>
    <link rel="stylesheet" href="estilo.css">
    <style>
        .container {
            max-width: 900px;
            margin: 20px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .report-section {
            margin-bottom: 40px;
        }
        h2 {
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
        }
        .summary-box {
            display: flex;
            justify-content: space-around;
            text-align: center;
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 8px;
            margin-top: 10px;
        }
        .summary-item strong {
            display: block;
            font-size: 1.2em;
        }
        .saldo-total {
            font-weight: bold;
            font-size: 1.5em;
        }
        .positivo { color: green; }
        .negativo { color: red; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #007bff;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Relatórios e Resumos</h1>
        <div class="nav-menu">
            <a href="../painel.php">Voltar</a>
            <a href="exportar.php">Exportar para Excel</a>
        </div>

        <!-- Seção de Relatório Financeiro -->
        <div class="report-section">
            <h2>Resumo Financeiro</h2>
            <div class="summary-box">
                <div class="summary-item">
                    <span>Arrecadações</span>
                    <strong class="positivo">R$ <?php echo number_format($total_arrecadacoes, 2, ',', '.'); ?></strong>
                </div>
                <div class="summary-item">
                    <span>Despesas</span>
                    <strong class="negativo">R$ <?php echo number_format($total_despesas, 2, ',', '.'); ?></strong>
                </div>
                <div class="summary-item">
                    <span>Saldo Total</span>
                    <strong class="saldo-total <?php echo ($saldo_financeiro >= 0) ? 'positivo' : 'negativo'; ?>">R$ <?php echo number_format($saldo_financeiro, 2, ',', '.'); ?></strong>
                </div>
            </div>

            <!-- Tabela de Movimentações Financeiras (Histórico) -->
            <h3>Histórico de Finanças</h3>
            <table>
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Tipo</th>
                        <th>Descrição</th>
                        <th>Valor</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Reabre a conexão para a segunda consulta
                    include __DIR__ . '/../conexao.php';
                    $historico_financeiro = [];
                    $sql_historico = "SELECT data, tipo, descricao, valor FROM financas WHERE id_terreiro = ? ORDER BY data DESC LIMIT 10";
                    if ($stmt_historico = $conn->prepare($sql_historico)) {
                        $stmt_historico->bind_param("i", $id_terreiro);
                        $stmt_historico->execute();
                        $result_historico = $stmt_historico->get_result();
                        while ($row = $result_historico->fetch_assoc()) {
                            $historico_financeiro[] = $row;
                        }
                        $stmt_historico->close();
                    }
                    $conn->close();

                    if (!empty($historico_financeiro)):
                        foreach ($historico_financeiro as $mov):
                    ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($mov['data'])); ?></td>
                            <td><?php echo htmlspecialchars(ucfirst($mov['tipo'])); ?></td>
                            <td><?php echo htmlspecialchars($mov['descricao']); ?></td>
                            <td class="<?php echo ($mov['tipo'] == 'arrecadacao') ? 'positivo' : 'negativo'; ?>">R$ <?php echo number_format($mov['valor'], 2, ',', '.'); ?></td>
                        </tr>
                    <?php
                        endforeach;
                    else:
                    ?>
                        <tr>
                            <td colspan="4">Nenhuma movimentação financeira recente encontrada.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Seção de Relatório de Estoque -->
        <div class="report-section">
            <h2>Resumo de Estoque</h2>
            <?php if (empty($estoque)): ?>
                <p>Nenhum item de estoque encontrado.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Produto</th>
                            <th>Quantidade Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($estoque as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['produto']); ?></td>
                                <td><?php echo htmlspecialchars($item['total_quantidade']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
