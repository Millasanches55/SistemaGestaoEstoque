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
    <section>
    <h2>Bem-vindo, <?php echo $nome; ?>!</h2>
    <p>Você logou como: <b><?php echo strtoupper($tipo); ?></b></p>

        <br><br>

    <?php if ($tipo == 'adm') { ?>
            <a href="estoque.php" class='botao'>Gerenciar Estoque</a>
            <a href="financeiro/index.php" class='botao'>Gerenciar Finanças</a>
            <a href="financeiro/relatorios.php" class='botao'>Relatórios</a>
            <a href="usuarios.php" class='botao'>Gerenciar Auxiliar</a>
    <?php } else { ?>
            <a href="estoque.php" class='botao'>Gerenciar Estoque</a>
            <a href="financeiro/relatorios.php" class='botao'>Consultar Relatórios</a>
    <?php } ?>
        <a href="perfilDoUsuario.php" class='botao'>Perfil</a>

        <a href="logout.php" class='botao'>Sair</a>
    </section>
</body>
</html>
