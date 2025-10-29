<?php
// Inclui o arquivo de conexão do banco.
include __DIR__ . '/../conexao.php';

// Inicia a sessão para garantir que o ID do terreiro está disponível.
session_start();

// Verifica se o usuário está logado. Se não, redireciona para a página de login.
if (!isset($_SESSION['id_usuario']) || $_SESSION["tipo"] !== "adm") {
    header("Location: ../index.php");
    exit();
}

$id_terreiro = $_SESSION['id_terreiro'] ?? 0;
$action = $_GET['action'] ?? 'saldo';

// --- INÍCIO DO CÁLCULO DO SALDO (inclui movimentações de estoque) ---
$arrecadacoes = 0;
$despesas = 0;
$saldo_total = 0;

if ($action === 'saldo') {
    // Aqui somamos:
    // - como RECEITAS: 'arrecadacao' + 'estoque_saida'
    // - como DESPESAS: 'despesa' + 'estoque_entrada'
    $sql_saldo = "
        SELECT
            COALESCE(SUM(CASE WHEN tipo IN ('arrecadacao','estoque_saida') THEN valor ELSE 0 END), 0) AS total_receitas,
            COALESCE(SUM(CASE WHEN tipo IN ('despesa','estoque_entrada') THEN valor ELSE 0 END), 0) AS total_despesas,
            COALESCE(SUM(CASE WHEN tipo IN ('arrecadacao','estoque_saida') THEN valor ELSE 0 END), 0)
              - COALESCE(SUM(CASE WHEN tipo IN ('despesa','estoque_entrada') THEN valor ELSE 0 END), 0) AS saldo
        FROM financas
        WHERE id_terreiro = ?
    ";

    if ($stmt = $conn->prepare($sql_saldo)) {
        $stmt->bind_param("i", $id_terreiro);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $arrecadacoes  = (float) ($row['total_receitas'] ?? 0);
            $despesas      = (float) ($row['total_despesas'] ?? 0);
            $saldo_total   = (float) ($row['saldo'] ?? 0);
        }
        $stmt->close();
    } else {
        // Em caso de erro na preparação, você pode logar $conn->error
        $arrecadacoes = $despesas = $saldo_total = 0;
    }
}
// --- FIM DO CÁLCULO DO SALDO ---
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Módulo Financeiro</title>
    <link rel="stylesheet" href="../style.css">
    <link href='https://cdn.boxicons.com/fonts/basic/boxicons.min.css' rel='stylesheet'>
    <style>
        /* estilo só para a página de saldo */
        .saldo-financeiro {
            text-align: center;
            margin-top: 40px;
        }
        .saldo-financeiro .valor {
            font-size: 3rem;
            font-weight: bold;
        }
        .botoes-container .botao { margin-right: 8px; }
    </style>
</head>
<body>
    <section>
        <div class="botoes-container">
            <a class="botao" href="../painel.php"><i class='bx  bx-arrow-left-stroke-circle'  ></i> Voltar</a>
            <a class="botao" href="index.php?action=saldo">Saldo</a>
            <a class="botao" href="index.php?action=resumo">Resumo</a>
            <a class="botao" href="index.php?action=list">Listar Movimentações</a>
            <a class="botao" href="index.php?action=add">Adicionar Movimentação</a>
            <a class="botao" href="index.php?action=dashboard">Dashboard</a>
        </div>
        <br><hr>
        <section>
            <?php
            // Lógica de roteamento simples
            switch ($action) {
                case 'saldo':
                    ?>
                    <div class="saldo-financeiro">
                        <h2><i class="bxr bx-dollar" style="font-size: 1.5em;"></i>Saldo Financeiro</h2>

                        <div class="valor" style="color: <?php echo ($saldo_total >= 0) ? 'green' : 'red'; ?>">
                            R$ <?php echo number_format($saldo_total, 2, ',', '.'); ?>
                        </div>
                        <p><small style="color: green;">Total Receitas (arrecadação + estoque_saida): R$ <?php echo number_format($arrecadacoes, 2, ',', '.'); ?></small></p>
                        <p><small style="color: red;">Total Despesas (despesa + estoque_entrada): R$ <?php echo number_format($despesas, 2, ',', '.'); ?></small></p>
                    </div>
                    <?php
                    break;
                case 'resumo':
                    // O resumo é exibido diretamente aqui.
                    include __DIR__. '/financas_resumo.php';
                    break;
                case 'list':
                    include __DIR__ . '/financas_list.php';
                    break;
                case 'add':
                    include __DIR__ . '/financas_add.php';
                    break;
                case 'dashboard':
                    include __DIR__ . '/financas_dashboard.php';
                    break;
                default:
                    echo "Página não encontrada.";
                    break;
            }
            ?>
            <br><br>
        </section>
    </section>
</body>
</html>
