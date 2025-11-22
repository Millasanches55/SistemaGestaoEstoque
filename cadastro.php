<?php
session_start();
include("conexao.php");

$tema = $_SESSION['tema'];
$fontep = $_SESSION['fontep'];
$fonteh2 = $_SESSION['fonteh2'];
$fonteh3 = $_SESSION['fonteh3'];
$icone_tema = $_SESSION['icone-tema'];
$icone_fonte = $_SESSION['icone-fonte'];

// Se o formulário foi enviado
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
        $_SESSION["icone-tema"] = $icone_tema;
    }
    else if ($_POST["fonte"] == "alterar") {
        if ($fontep == "15px" && $fonteh2 == "25px") {
            $fontep = "19px";
            $fonteh2 = "30px";
            $fonteh3 = "25px";
            $icone_fonte = "-A";
            $_SESSION["fontep"] = $fontep;
            $_SESSION["fonteh2"] = $fonteh2;
            $_SESSION["icone-fonte"] = $icone_fonte;
        }
        else {
            $fontep = "15px";
            $fonteh2 = "25px";
            $fonteh3 = "20px";
            $icone_fonte = "+A";
            $_SESSION["fontep"] = $fontep;
            $_SESSION["fonteh2"] = $fonteh2;
            $_SESSION["icone-fonte"] = $icone_fonte;
        }
    }
    else {
        $nome_terreiro = $_POST['nome_terreiro'];
        $endereco      = $_POST['endereco'];

        $nome_adm      = $_POST['nome_adm'];
        $usuario_adm   = $_POST['usuario_adm'];
        $senha_adm     = password_hash($_POST['senha_adm'], PASSWORD_DEFAULT);

        // --- Transação para garantir consistência ---
        $conn->begin_transaction();

        try {
            // 1. Insere o terreiro
            $sql = "INSERT INTO terreiro (nome_terreiro, endereco) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $nome_terreiro, $endereco);
            $stmt->execute();
            $id_terreiro = $stmt->insert_id;

            // 2. Insere o Administrador
            $sql = "INSERT INTO usuarios (id_terreiro, nome, usuario, senha, tipo) VALUES (?, ?, ?, ?, 'adm')";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isss", $id_terreiro, $nome_adm, $usuario_adm, $senha_adm);
            $stmt->execute();

            // 3. Se tiver dados do auxiliar, insere também
            if (!empty($_POST['nome_aux']) && !empty($_POST['usuario_aux']) && !empty($_POST['senha_aux'])) {
                $nome_aux    = $_POST['nome_aux'];
                $usuario_aux = $_POST['usuario_aux'];
                $senha_aux   = password_hash($_POST['senha_aux'], PASSWORD_DEFAULT);

                $sql = "INSERT INTO usuarios (id_terreiro, nome, usuario, senha, tipo) VALUES (?, ?, ?, ?, 'auxiliar')";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("isss", $id_terreiro, $nome_aux, $usuario_aux, $senha_aux);
                $stmt->execute();
            }

            // Finaliza transação
            $conn->commit();

            echo "<p>Cadastro realizado com sucesso! <a href='index.php'>Fazer login</a></p>";

        } catch (Exception $e) {
            $conn->rollback();
            echo "Erro ao cadastrar: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Cadastro do Terreiro</title>
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
    <section>
        <h2>Cadastro do Administrador e do Terreiro</h2>
        <form method="post" action="">
            <fieldset>
                <legend>Dados do Terreiro</legend>
                <p>Nome do Terreiro:</p><input type="text" name="nome_terreiro" required><br><br>
                <p>Endereço:</p> <input type="text" name="endereco"><br><br>
            </fieldset>

            <fieldset>
                <legend>Administrador</legend>
                <p>Nome:</p> <input type="text" name="nome_adm" required><br><br>
                <p>Usuário:</p> <input type="text" name="usuario_adm" required><br><br>
                <p>Senha:</p> <input type="password" name="senha_adm" required><br><br>
            </fieldset>

            <fieldset>
                <legend>Auxiliar (opcional)</legend>
                <p>Nome:</p> <input type="text" name="nome_aux"><br><br>
                <p>Usuário:</p> <input type="text" name="usuario_aux"><br><br>
                <p>Senha:</p> <input type="password" name="senha_aux"><br><br>
            </fieldset>
            <br>
            <button class="botao" type="submit">Cadastrar</button>
        </form>
        <br>
        <p>Já possui conta? <a class="link-estoque" href="index.php">Fazer login</a></p>
    </section>
</body>
</html>
