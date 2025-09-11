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

// Atualizar item
if ($_SERVER["REQUEST_METHOD"] == "POST") {
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
        $tipo_mov = ($dif > 0) ? 'entrada' : 'saida';
        $quantidade_mov = abs($dif);

        $sql_hist = "INSERT INTO estoque_historico (id_estoque, produto, quantidade, tipo) 
                     VALUES (?, ?, ?, ?)";
        $stmt_hist = $conn->prepare($sql_hist);
        $stmt_hist->bind_param("isis", $id, $produto, $quantidade_mov, $tipo_mov);
        $stmt_hist->execute();
    }

    header("Location: estoque.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Editar Item do Estoque</title>
    <link rel="stylesheet" href="style.css">
    <link href='https://cdn.boxicons.com/fonts/basic/boxicons.min.css' rel='stylesheet'>
</head>
<body>
    <section>
        <h2><i class='bx  bx-edit'  ></i> Editar Item do Estoque</h2>
            <a href="estoque.php" class="botao"><i class='bx  bx-arrow-left-stroke-circle'  ></i> Voltar ao Estoque</a>
            <br><br>
            <form method="post">
                Nome do Produto: 
                <input type="text" name="nome" value="<?php echo htmlspecialchars($item['produto']); ?>" required><br><br>

                Quantidade: 
                <input type="number" name="quantidade" min="1" value="<?php echo $item['quantidade']; ?>" required><br><br>

                Tipo de Aquisição:
                <select name="tipo_aquisicao" required>
                    <option value="compra" <?php if($item['origem']=="compra") echo "selected"; ?>>Compra</option>
                    <option value="doacao" <?php if($item['origem']=="doacao") echo "selected"; ?>>Doação</option>
                </select><br><br>

                <button type="submit" class="botao">Salvar Alterações</button>
            </form>
    </section>
</body>
</html>