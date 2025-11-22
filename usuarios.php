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
            $_SESSION["fonteh3"] = $fonteh3;
            $_SESSION["icone-fonte"] = $icone_fonte;
        }
        else {
            $fontep = "15px";
            $fonteh2 = "25px";
            $fonteh3 = "20px";
            $icone_fonte = "+A";
            $_SESSION["fontep"] = $fontep;
            $_SESSION["fonteh2"] = $fonteh2;
            $_SESSION["fonteh3"] = $fonteh3;
            $_SESSION["icone-fonte"] = $icone_fonte;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gerenciar Auxiliar</title>
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
        <h2><i class='bx  bx-group'  ></i>  Gerenciar Auxiliar</h2>
        <a href="painel.php" class="botao"><i class='bx  bx-arrow-left-stroke-circle'  ></i>  Voltar ao Painel</a>
        <br><br>
        <table id="tabela-usuarios">
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Tipo</th>
            </tr>
            <?php
                try {
                    $pdo = new PDO("mysql:host=localhost;dbname=db_terreiro", "root", "");
                    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    
                    $query = $pdo->prepare("SELECT id, nome, tipo FROM usuarios");
                    $query->execute();
                    $resultado = $query->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($resultado as $linha) {
                        echo "<tr>";
                        
                        echo "<td>" . $linha["id"] . "</td>";
                        echo "<td>" . $linha["nome"] . "</td>";
                        echo "<td>" . $linha["tipo"] . "</td>";

                        echo "</tr>";
                    }

                } catch(PDOException $e) {
                    echo "Erro: " . $e->getMessage();
                }
            ?>
        </table>
        <br>
        <?php if (!$auxiliar) { ?>
            <h3>Cadastrar Auxiliar</h3>
            <form method="post">
                <p>Nome:</p> <input type="text" name="nome" class="input-texto" required><br><br>
                <p>Usuário:</p> <input type="text" name="usuario" class="input-texto"  required><br><br>
                <p>Senha:</p> <input type="password" name="senha" class="input-texto" required><br><br>
                <button class="botao" type="submit" name="cadastrar">Cadastrar</button>
            </form>
        <?php } else { ?>
            <h3>Auxiliar Atual</h3>
            <form method="post">
                <p>Nome:</p> <input type="text" name="nome" value="<?php echo htmlspecialchars($auxiliar['nome']); ?>" class="input-texto"  required><br><br>
                <p>Usuário:</p> <input type="text" name="usuario" value="<?php echo htmlspecialchars($auxiliar['usuario']); ?>" class="input-texto"  required><br><br>
                <p>Senha (deixe em branco para não alterar):</p> <input type="password" name="senha" class="input-texto" ><br><br>
                <button class="botao" type="submit" name="editar">Salvar Alterações</button>
            </form>
            <br>
            <a class="botao" href="usuarios.php?remover=1" onclick="return confirm('Deseja remover o auxiliar?')">❌ Remover Auxiliar</a>
        <?php } ?>
    </section>
</body>
</html>