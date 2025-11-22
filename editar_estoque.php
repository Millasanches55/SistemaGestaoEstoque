<?php
session_start();
if (!isset($_SESSION["id_usuario"])) {
    header("Location: index.php");
    exit;
}

include("conexao.php");

// Verifica se o ID foi passado
if (!isset($_GET['id'])) {
    header("Location: estoque.php");
    exit;
}

$id = intval($_GET['id']);

// Busca o item do estoque
$sql = "SELECT * FROM estoque WHERE id = ? AND id_terreiro = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $id, $_SESSION["id_terreiro"]);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    echo "Item não encontrado ou não pertence ao seu terreiro.";
    exit;
}

$item = $result->fetch_assoc();

$tema = $_SESSION['tema'];
$fontep = $_SESSION['fontep'];
$fonteh2 = $_SESSION['fonteh2'];
$fonteh3 = $_SESSION['fonteh3'];
$icone_tema = $_SESSION['icone-tema'];
$icone_fonte = $_SESSION['icone-fonte'];

// Atualizar item
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
    else {
        $nome = $_POST['nome'];
        $nova_quantidade = (int)$_POST['quantidade'];
        $tipo_aquisicao = $_POST['tipo_aquisicao'];

        // Pega a quantidade atual
        $sql = "SELECT quantidade, produto FROM estoque WHERE id = ? AND id_terreiro = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $id, $_SESSION["id_terreiro"]);
        $stmt->execute();
        $result = $stmt->get_result();
        $item_atual = $result->fetch_assoc();
        $quantidade_atual = (int)$item_atual['quantidade'];
        $produto = $item_atual['produto'];

        // Calcula diferença
        $dif = $nova_quantidade - $quantidade_atual;

        // Atualiza estoque
        $sql_update = "UPDATE estoque SET produto = ?, quantidade = ?, origem = ? WHERE id = ? AND id_terreiro = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("sisii", $nome, $nova_quantidade, $tipo_aquisicao, $id, $_SESSION["id_terreiro"]);
        $stmt_update->execute();

            // Se houver diferença, registra no histórico
        if ($dif != 0) {
            $tipo_mov = ($dif > 0) ? 'estoque_entrada' : 'estoque_saida';
            $quantidade_mov = abs($dif);

            $sql_hist = "INSERT INTO estoque_historico (id_estoque, quantidade, tipo, quantidade_anterior, quantidade_atual)
                        VALUES (?, ?, ?, ?, ?)";
            $stmt_hist = $conn->prepare($sql_hist);
            $stmt_hist->bind_param("iisii", $id, $quantidade_mov, $tipo_mov, $quantidade_atual, $nova_quantidade);
            $stmt_hist->execute();
        }
        header("Location: estoque.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Editar Item do Estoque</title>
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
        <h2><i class='bx  bx-edit'  ></i> Editar Item do Estoque</h2>
            <a href="estoque.php" class="botao"><i class='bx  bx-arrow-left-stroke-circle'  ></i> Voltar ao Estoque</a>
            <br><br>
            <form method="post">
                <p>Nome do Produto: </p>
                <input type="text" class="input-texto" name="nome" value="<?php echo htmlspecialchars($item['produto']); ?>" required><br><br>

                <p>Quantidade: </p>
                <input type="number" class="input-texto" name="quantidade" min="1" value="<?php echo $item['quantidade']; ?>" required><br><br>

                <p>Tipo de Aquisição:</p>
                <select name="tipo_aquisicao" class="input-texto" required>
                    <option value="compra" <?php if($item['origem']=="compra") echo "selected"; ?>>Compra</option>
                    <option value="doacao" <?php if($item['origem']=="doacao") echo "selected"; ?>>Doação</option>
                </select><br><br>

                <button type="submit" class="botao">Salvar Alterações</button>
            </form>
    </section>
</body>
</html>