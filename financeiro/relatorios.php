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
$sql_historico = "SELECT tipo, descricao, valor, data FROM financas WHERE id_terreiro = ? ORDER BY data DESC LIMIT 10";
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

$tema = $_SESSION['tema'];
$fontep = $_SESSION['fontep'];
$fonteh2 = $_SESSION['fonteh2'];
$fonteh3 = $_SESSION['fonteh3'];
$icone_tema = "<i class='bx  bx-moon' style='font-size: 20px;' ></i>";
$icone_fonte = "+A";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if ($_POST["tema"] == "alterar") {
        if ($tema == "style.css") {
            $tema = "styleTemaEscuro.css";
            $icone_tema = "<i class='bx  bx-sun' style='font-size: 20px;' ></i> ";
        }
        else {
            $tema = "style.css";
            $icone_tema = "<i class='bx  bx-moon' style='font-size: 20px;' ></i>";
        }
        $_SESSION["tema"] = $tema;
    }
    else if ($_POST["fonte"] == "alterar") {
        if ($fontep == "15px" && $fonteh2 == "25px") {
            $fontep = "19px";
            $fonteh2 = "30px";
            $fonteh3 = "25px";
            $icone_fonte = "-A";
            $_SESSION["fontep"] = $fontep;
            $_SESSION["fonteh2"] = $fonteh2;
        }
        else {
            $fontep = "15px";
            $fonteh2 = "25px";
            $fonteh3 = "20px";
            $icone_fonte = "+A";
            $_SESSION["fontep"] = $fontep;
            $_SESSION["fonteh2"] = $fonteh2;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Relatórios</title>
    <link rel="stylesheet" href="../<?php echo $tema; ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href='https://cdn.boxicons.com/fonts/basic/boxicons.min.css' rel='stylesheet'>
</head>
<?php
        echo "<style>";
        echo "p {";
        echo "font-size: $fontep;";
        echo "}";
        echo "h2 {";
        echo "font-size: $fonteh2;";
        echo "}";
        echo "h3 {";
        echo "font-size: $fonteh3;";
        echo "}";
        echo "</style>";
    ?>
    <div style="display: flex; position: fixed; top: 10px; right: 10px; gap: 15px;">
        <form action="" method="post">
            <input type="hidden" name="fonte" value="alterar" />
            <button class="botao" style="font-size: 20px; width: 60px;" type="submit"><?php echo $icone_fonte; ?></button>
        </form>
        <form action="" method="post">
            <input type="hidden" name="tema" value="alterar" />
            <button class="botao" style="width: 60px;" type="submit"><?php echo $icone_tema; ?></button>
        </form>
    </div>
<body>
    <section>
        <div class="nav-menu">
            <a href="../painel.php" class="botao"><i class='bx  bx-arrow-left-stroke-circle'  ></i> Voltar Ao Painel</a>
            <?php if ($_SESSION["tipo"] == "adm"): ?>
                <a href="index.php?action=resumo" class="botao">Resumo Financeiro</a>
            <?php endif;?>
            <a href="exportar.php" class="botao">Exportar para Excel<i class='bxr  bx-arrow-to-bottom-stroke'  ></i> </a>
        </div>
        <br>
        <h2><i class='bx  bx-list-ul-square'  ></i> Relatórios Detalhados</h2>
        <!-- Seção de Resumo Financeiro -->
        <div class="content">
            <h3>Resumo Financeiro</h3>
            <div class="summary-box">
                <p>Arrecadações e Entradas: <span style="color: #138000;">R$ <?php echo number_format($arrecadacoes, 2, ',', '.'); ?></span></p>
                <p>Despesas e Saídas: <span class="despesas">R$ <?php echo number_format($despesas, 2, ',', '.'); ?></span></p>
                <p>Saldo Atual: <span class="saldo-value"><?php echo number_format($saldo, 2, ',', '.'); ?></span></p>
            </div>
        </div>
        
        <hr><br>
        
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
                                    <?php echo ucfirst($item['tipo']); ?>
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
        
        <hr><br>
        
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
