<?php
    session_start();
    if (isset($_SESSION["id_usuario"])) {
        header("Location: painel.php");
        exit;
}

if (!isset($_SESSION['tema'])) {
    $_SESSION['tema'] = "style.css";
    $_SESSION['fontep'] = "15px";
    $_SESSION['fonteh2'] = "30px";
    $_SESSION['fonteh3'] = "25px";
    $_SESSION['icone-tema'] = "<i class='bx  bx-moon' style='font-size: 20px;' ></i>";
    $_SESSION['icone-fonte'] = "+A";
}

$tema = $_SESSION['tema'];
$fontep = $_SESSION['fontep'];
$fonteh2 = $_SESSION['fonteh2'];
$icone_tema = $_SESSION['icone-tema'];
$icone_fonte = $_SESSION['icone-fonte'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if ($_POST["tema"] == "alterar") {
        if ($tema == "style.css") {
            $tema = "styleTemaEscuro.css";
            $icone_tema = "<i class='bx  bx-sun' style='font-size: 20px;' ></i> ";
            $_SESSION['tema'] = $tema;
            $_SESSION["icone-tema"] = $icone_tema;
        }
        else {
            $tema = "style.css";
            $icone_tema = "<i class='bx  bx-moon' style='font-size: 20px;' ></i>";
            $_SESSION['tema'] = $tema;
            $_SESSION["icone-tema"] = $icone_tema;
        }
    }
    else if ($_POST["fonte"] == "alterar") {
        if ($fontep == "15px" && $fonteh2 == "25px") {
            $fontep = "19px";
            $fonteh2 = "30px";
            $icone_fonte = "-A";
            $_SESSION["fontep"] = $fontep;
            $_SESSION["fonteh2"] = $fonteh2;
            $_SESSION["icone-fonte"] = $icone_fonte;
        }
        else {
            $fontep = "15px";
            $fonteh2 = "25px";
            $icone_fonte = "+A";
            $_SESSION["fontep"] = $fontep;
            $_SESSION["fonteh2"] = $fonteh2;
            $_SESSION["icone-fonte"] = $icone_fonte;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Sistema de Gestão do Terreiro</title>
    <link rel="stylesheet" href="<?php echo $tema; ?>">
    <link href='https://cdn.boxicons.com/fonts/basic/boxicons.min.css' rel='stylesheet'>
</head>
<body>
    <?php
        echo "<style>";
        echo "p {";
        echo "font-size: $fontep;";
        echo "}";
        echo "h2 {";
        echo "font-size: $fonteh2;";
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
    <section>
        <h2>Login</h2>
        <form action="login.php" method="post">
            <p>Usuário:</p> <input type="text" name="usuario" class="input-texto" required><br><br>
            <p>Senha:</p> <input type="password" name="senha" class="input-texto" required><br><br>
            <button class="botao" type="submit">Entrar</button>
        </form>
        <br><br>
        <h2>Ainda não tem cadastro?</h2>
        <form action="cadastro.php" method="get">
            <button class="botao" type="submit">Cadastrar Administrador e Assistente</button>
        </form>
    </section>
    
</body>
</html>
