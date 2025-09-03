<?php
session_start();
if (!isset($_SESSION["id_usuario"]) || $_SESSION["tipo"] !== "adm") {
    header("Location: index.php");
    exit;
}

include("conexao.php");

// Verificar se já existe auxiliar no terreiro
$sql = "SELECT * FROM usuarios WHERE id_terreiro = ? AND tipo = 'auxiliar'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION["id_terreiro"]);
$stmt->execute();
$result = $stmt->get_result();
$auxiliar = $result->fetch_assoc();

// Cadastrar auxiliar
if (isset($_POST['cadastrar'])) {
    if ($auxiliar) {
        echo "Já existe um auxiliar cadastrado para este terreiro.";
    } else {
        $nome = $_POST['nome'];
        $usuario = $_POST['usuario'];
        $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);

        $sql = "INSERT INTO usuarios (id_terreiro, nome, usuario, senha, tipo) VALUES (?, ?, ?, ?, 'auxiliar')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isss", $_SESSION["id_terreiro"], $nome, $usuario, $senha);

        if ($stmt->execute()) {
            header("Location: usuarios.php");
            exit;
        } else {
            echo "Erro ao cadastrar auxiliar.";
        }
    }
}

// Editar auxiliar
if (isset($_POST['editar']) && $auxiliar) {
    $nome = $_POST['nome'];
    $usuario = $_POST['usuario'];

    // Se senha for preenchida, atualiza também
    if (!empty($_POST['senha'])) {
        $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);
        $sql = "UPDATE usuarios SET nome = ?, usuario = ?, senha = ? WHERE id = ? AND id_terreiro = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssii", $nome, $usuario, $senha, $auxiliar['id'], $_SESSION["id_terreiro"]);
    } else {
        $sql = "UPDATE usuarios SET nome = ?, usuario = ? WHERE id = ? AND id_terreiro = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssii", $nome, $usuario, $auxiliar['id'], $_SESSION["id_terreiro"]);
    }

    if ($stmt->execute()) {
        header("Location: usuarios.php");
        exit;
    } else {
        echo "Erro ao editar auxiliar.";
    }
}

// Remover auxiliar
if (isset($_GET['remover']) && $auxiliar) {
    $sql = "DELETE FROM usuarios WHERE id = ? AND id_terreiro = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $auxiliar['id'], $_SESSION["id_terreiro"]);
    $stmt->execute();
    header("Location: usuarios.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gerenciar Auxiliar</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h2>Gerenciar Auxiliar</h2>
    <p><a href="painel.php">⬅ Voltar ao Painel</a></p>

    <?php if (!$auxiliar) { ?>
        <h3>Cadastrar Auxiliar</h3>
        <form method="post">
            Nome: <input type="text" name="nome" required><br><br>
            Usuário: <input type="text" name="usuario" required><br><br>
            Senha: <input type="password" name="senha" required><br><br>
            <button type="submit" name="cadastrar">Cadastrar</button>
        </form>
    <?php } else { ?>
        <h3>Auxiliar Atual</h3>
        <form method="post">
            Nome: <input type="text" name="nome" value="<?php echo htmlspecialchars($auxiliar['nome']); ?>" required><br><br>
            Usuário: <input type="text" name="usuario" value="<?php echo htmlspecialchars($auxiliar['usuario']); ?>" required><br><br>
            Senha (deixe em branco para não alterar): <input type="password" name="senha"><br><br>
            <button type="submit" name="editar">Salvar Alterações</button>
        </form>
        <br>
        <a href="usuarios.php?remover=1" onclick="return confirm('Deseja remover o auxiliar?')">❌ Remover Auxiliar</a>
        lklllllll
    <?php } ?>
</body>
</html>