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
$action = $_GET['action'] ?? 'saldo';

// Define a ação padrão como 'Saldo'.
$action = $_GET['action'] ?? 'saldo';

// --- INICIO DO CÁLCULO DO SALDO ---
$arrecadacoes = 0;
$despesas = 0;
$saldo_total = 0;

if ($action == 'saldo') {
    // Consulta para obter o total de arrecadações.
    $sql_arrecadacoes = "SELECT SUM(valor) AS total_arrecadacoes FROM financas WHERE id_terreiro = ? AND tipo = 'arrecadacao'";
    if ($stmt_arrecadacoes = $conn->prepare($sql_arrecadacoes)) {
        $stmt_arrecadacoes->bind_param("i", $id_terreiro);
        $stmt_arrecadacoes->execute();
        $result_arrecadacoes = $stmt_arrecadacoes->get_result();
        if ($row = $result_arrecadacoes->fetch_assoc()) {
            $arrecadacoes = $row['total_arrecadacoes'] ?? 0;
        }
        $stmt_arrecadacoes->close();
    }

    // Consulta para obter o total de despesas.
    $sql_despesas = "SELECT SUM(valor) AS total_despesas FROM financas WHERE id_terreiro = ? AND tipo = 'despesa'";
    if ($stmt_despesas = $conn->prepare($sql_despesas)) {
        $stmt_despesas->bind_param("i", $id_terreiro);
        $stmt_despesas->execute();
        $result_despesas = $stmt_despesas->get_result();
        if ($row = $result_despesas->fetch_assoc()) {
            $despesas = $row['total_despesas'] ?? 0;
        }
        $stmt_despesas->close();
    }

    // Calcula o saldo total.
    $saldo_total = $arrecadacoes - $despesas;
}
// --- FIM DO CÁLCULO DO SALDO ---
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Módulo Financeiro</title>
    <link rel="stylesheet" href="estilo.css">
</head>
<body>
    <div class="container">
        <div class="nav-menu">
            <a href="index.php?action=saldo">Saldo</a>
            <a href="index.php?action=resumo">Resumo</a>
            <a href="index.php?action=list">Listar Movimentações</a>
            <a href="index.php?action=add">Adicionar Movimentação</a>
            <a href="index.php?action=dashboard">Dashboard</a>
        </div>
        <hr>
        <div class="content">
        
            <?php
            // Lógica de roteamento simples
            switch ($action) {
                case 'saldo':
                    ?>
                    <div class="summary-box">
                        <h2>Saldo Financeiro</h2>
                        <div class="saldo-value">
                            <span class="<?php echo ($saldo_total >= 0) ? 'arrecadacoes' : 'despesas'; ?>">
                                R$ <?php echo number_format($saldo_total, 2, ',', '.'); ?>
                            </span>
                        </div>
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
        </div>
    </div>
</body>
</html>
<?php
$conn->close();
?>
