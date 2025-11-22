<?php
session_start();
if (!isset($_SESSION["id_usuario"])) {
    header("Location: index.php");
    exit;
}

include("conexao.php");

$id_usuario = $_SESSION["id_usuario"];
$tipo = $_SESSION["tipo"];
$mensagem = "";

// Carregar dados do usuário (e do terreiro, se ADM)
if ($tipo == "adm") {
    $sql = "SELECT u.nome, u.usuario, u.senha, t.nome_terreiro, t.endereco
            FROM usuarios u
            INNER JOIN terreiro t ON u.id_terreiro = t.id
            WHERE u.id = ?";
} else {
    $sql = "SELECT nome, usuario, senha FROM usuarios WHERE id = ?";
}
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$dados = $stmt->get_result()->fetch_assoc();

// Atualizar dados
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = $_POST["nome"];
    $usuario = $_POST["usuario"];
    $senha_atual = $_POST["senha_atual"];
    $senha_nova = $_POST["senha_nova"];
    $confirmar_senha = $_POST["confirmar_senha"];

    // Verifica senha atual
    if (!password_verify($senha_atual, $dados["senha"])) {
        $mensagem = "<i class='bx  bx-x'  ></i>  Senha atual incorreta.";
    } elseif ($senha_nova !== $confirmar_senha) {
        $mensagem = "<i class='bx  bx-x'  ></i>  A nova senha e a confirmação não coincidem.";
    } else {
        $senha_hash = password_hash($senha_nova, PASSWORD_DEFAULT);

        if ($tipo == "adm") {
            $nome_terreiro = $_POST["nome_terreiro"];
            $endereco = $_POST["endereco"];

            // Atualizar usuário
            $sql_u = "UPDATE usuarios SET nome = ?, usuario = ?, senha = ? WHERE id = ?";
            $stmt_u = $conn->prepare($sql_u);
            $stmt_u->bind_param("sssi", $nome, $usuario, $senha_hash, $id_usuario);
            $stmt_u->execute();

            // Atualizar terreiro
            $sql_t = "UPDATE terreiro 
                      SET nome_terreiro = ?, endereco = ?
                      WHERE id = (SELECT id_terreiro FROM usuarios WHERE id = ?)";
            $stmt_t = $conn->prepare($sql_t);
            $stmt_t->bind_param("ssi", $nome_terreiro, $endereco, $id_usuario);
            $stmt_t->execute();
        } else {
            // Atualizar apenas usuário auxiliar
            $sql_u = "UPDATE usuarios SET nome = ?, usuario = ?, senha = ? WHERE id = ?";
            $stmt_u = $conn->prepare($sql_u);
            $stmt_u->bind_param("sssi", $nome, $usuario, $senha_hash, $id_usuario);
            $stmt_u->execute();
        }

        $_SESSION["nome"] = $nome;
        $mensagem = "<i class='bx  bx-check'  ></i>  Dados atualizados com sucesso!";
    }
}

$tema = $_SESSION['tema'];
$fontep = $_SESSION['fontep'];
$fonteh2 = $_SESSION['fonteh2'];
$fonteh3 = $_SESSION['fonteh3'];
$icone_tema = $_SESSION['icone-tema'];
$icone_fonte = $_SESSION['icone-fonte'];

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
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Perfil do Usuário</title>
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
        <h2><i class='bx  bx-user'  ></i> Perfil do Usuário</h2>
        <hr>
        <br>
        <a class="botao" href="painel.php"><i class='bx  bx-arrow-left-stroke-circle'  ></i>  Voltar</a>
        <br><br>
        <h3>Editar Perfil</h3>
        <?php if ($mensagem) echo "<p><b>$mensagem</b></p>"; ?>

        <form method="post">
            <label>Nome:</label><br>
            <input type="text" name="nome" value="<?php echo htmlspecialchars($dados['nome']); ?>" class="input-texto" required><br><br>

            <label>Usuário:</label><br>
            <input type="text" name="usuario" value="<?php echo htmlspecialchars($dados['usuario']); ?>" class="input-texto" required><br><br>

            <?php if ($tipo == "adm") { ?>
                <label>Nome do Terreiro:</label><br>
                <input type="text" name="nome_terreiro" value="<?php echo htmlspecialchars($dados['nome_terreiro']); ?>" class="input-texto" required><br><br>

                <label>Endereço:</label><br>
                <input type="text" name="endereco" value="<?php echo htmlspecialchars($dados['endereco']);?>" class="input-texto"><br><br>
            <?php } ?>

            <h3>Alterar Senha</h3>
            <p>Senha atual:</p> <input type="password" name="senha_atual" class="input-texto" required><br><br>
            <p>Nova senha:</p> <input type="password" name="senha_nova" class="input-texto" required><br><br>
            <p>Confirmar nova senha:</p> <input type="password" name="confirmar_senha" class="input-texto" required><br><br>

            <button class="botao" type="submit">Salvar Alterações</button>
        </form>
    </section>
</body>
</html>
