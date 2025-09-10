<?php
include("conexao.php");

// Se o formulário foi enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
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
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Cadastro do Terreiro</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <section>
        <h2>Cadastro do Administrador e do Terreiro</h2>
        <form method="post" action="">
            <fieldset>
                <legend>Dados do Terreiro</legend>
                Nome do Terreiro: <input type="text" name="nome_terreiro" required><br><br>
                Endereço: <input type="text" name="endereco"><br><br>
            </fieldset>

            <fieldset>
                <legend>Administrador</legend>
                Nome: <input type="text" name="nome_adm" required><br><br>
                Usuário: <input type="text" name="usuario_adm" required><br><br>
                Senha: <input type="password" name="senha_adm" required><br><br>
            </fieldset>

            <fieldset>
                <legend>Auxiliar (opcional)</legend>
                Nome: <input type="text" name="nome_aux"><br><br>
                Usuário: <input type="text" name="usuario_aux"><br><br>
                Senha: <input type="password" name="senha_aux"><br><br>
            </fieldset>
            <br>
            <button class="botao" type="submit">Cadastrar</button>
        </form>
        <br>
        <p>Já possui conta? <a href="index.php">Fazer login</a></p>
    </section>
</body>
</html>
