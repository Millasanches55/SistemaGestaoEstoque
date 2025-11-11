<?php
session_start();

if (!isset($_SESSION['id_usuario'])) {
    header("Location: index.php");
    exit();
}

$nome = $_SESSION['nome'];
$tipo = $_SESSION['tipo'];
$tema = $_SESSION['tema'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if ($tema == "style.css") {
        $tema = "styleTemaEscuro.css";
        $_SESSION["tema"] = $tema;
    }
    else {
        $tema = "style.css";
        $_SESSION["tema"] = $tema;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Painel</title>
    <link rel="stylesheet" href="<?php echo $tema; ?>">
    <link href='https://cdn.boxicons.com/fonts/basic/boxicons.min.css' rel='stylesheet'>
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
        <br>
        <br>
        <hr>
        <br>
        <div style="display: flex; justify-content: center; gap: 30px;">
            <a href="perfilDoUsuario.php" class='botao'><i class='bx  bx-user'  ></i> Perfil</a>
            <a href="logout.php" class='botao'><i class='bx  bx-door-open'  ></i> Sair</a>
        </div>
            
    </section>
    
    <form action="" method="post">
        <button class="botao" type="submit">Alterar tema</button>
    </form>
</body>
</html>
