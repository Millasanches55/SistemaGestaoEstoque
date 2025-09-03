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
    $quantidade = $_POST['quantidade'];
    $tipo_aquisicao = $_POST['tipo_aquisicao'];

    $sql = "UPDATE estoque 
            SET produto = ?, quantidade = ?, origem = ? 
            WHERE id = ? AND id_terreiro = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sisii", $nome, $quantidade, $tipo_aquisicao, $id, $_SESSION["id_terreiro"]);
    
    if ($stmt->execute()) {
        header("Location: estoque.php");
        exit;
    } else {
        echo "Erro ao atualizar o item.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Editar Item do Estoque</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h2>Editar Item do Estoque</h2>
    <p><a href="estoque.php">⬅ Voltar ao Estoque</a></p>

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

        <button type="submit">Salvar Alterações</button>
    </form>
</body>
</html>