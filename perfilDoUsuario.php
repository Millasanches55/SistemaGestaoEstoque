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
        $mensagem = "❌ Senha atual incorreta.";
    } elseif ($senha_nova !== $confirmar_senha) {
        $mensagem = "❌ A nova senha e a confirmação não coincidem.";
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
        $mensagem = "✅ Dados atualizados com sucesso!";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Perfil do Usuário</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <section>
        <h1>Perfil do Usuário</h1>
        <hr>
        <br>
        <a class="botao" href="painel.php">⬅ Voltar</a>

        <h2>Editar Perfil</h2>
        <?php if ($mensagem) echo "<p><b>$mensagem</b></p>"; ?>

        <form method="post">
            <label>Nome:</label><br>
            <input type="text" name="nome" value="<?php echo htmlspecialchars($dados['nome']); ?>" required><br><br>

            <label>Usuário:</label><br>
            <input type="text" name="usuario" value="<?php echo htmlspecialchars($dados['usuario']); ?>" required><br><br>

            <?php if ($tipo == "adm") { ?>
                <label>Nome do Terreiro:</label><br>
                <input type="text" name="nome_terreiro" value="<?php echo htmlspecialchars($dados['nome_terreiro']); ?>" required><br><br>

                <label>Endereço:</label><br>
                <input type="text" name="endereco" value="<?php echo htmlspecialchars($dados['endereco']); ?>"><br><br>
            <?php } ?>

            <h3>Alterar Senha</h3>
            Senha atual: <input type="password" name="senha_atual" required><br><br>
            Nova senha: <input type="password" name="senha_nova" required><br><br>
            Confirmar nova senha: <input type="password" name="confirmar_senha" required><br><br>

            <button class="botao" type="submit">Salvar Alterações</button>
        </form>
    </section>
</body>
</html>
