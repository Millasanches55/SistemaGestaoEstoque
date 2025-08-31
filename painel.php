<?php
session_start();

if (!isset($_SESSION['id_usuario'])) {
    header("Location: index.php");
    exit();
}

$nome = $_SESSION['nome'];
$tipo = $_SESSION['tipo'];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Painel</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h2>Bem-vindo, <?php echo $nome; ?>!</h2>
    <p>Você logou como: <b><?php echo strtoupper($tipo); ?></b></p>

    <?php if ($tipo == 'adm') { ?>
        <a href="gerenciar_estoque.php">Gerenciar Estoque</a><br>
        <a href="financeiro/index.php">Gerenciar Finanças</a><br>
        <a href="financeiro/relatorios.php">Relatórios</a><br>
        <a href="usuarios.php">Gerenciar Auxiliar</a><br>
    <?php } else { ?>
        <a href="gerenciar_estoque.php">Gerenciar Estoque</a><br>
        <a href="relatorios.php">Consultar Relatórios</a><br>
    <?php } ?>

    <br><a href="logout.php">Sair</a>
</body>
</html>
