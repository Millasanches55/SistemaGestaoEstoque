<?php
session_start();
if (!isset($_SESSION["id_usuario"]) || $_SESSION["tipo"] !== "adm") {
    header("Location: index.php");
    exit;
}

include("conexao.php");

// ADICIONAR ITEM
if (isset($_POST['adicionar'])) {
    $produto = $_POST['produto'];
    $quantidade = $_POST['quantidade'];
    $origem = $_POST['origem']; // compra ou doacao

    $sql = "INSERT INTO estoque (id_terreiro, produto, quantidade, origem) 
            VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isis", $_SESSION["id_terreiro"], $produto, $quantidade, $origem);
    $stmt->execute();
}

// DELETAR ITEM
if (isset($_GET['deletar'])) {
    $id = $_GET['deletar'];
    $sql = "DELETE FROM estoque WHERE id = ? AND id_terreiro = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $id, $_SESSION["id_terreiro"]);
    $stmt->execute();
}

// LISTAR ITENS
$sql = "SELECT * FROM estoque WHERE id_terreiro = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION["id_terreiro"]);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gerenciar Estoque</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h2>Gerenciar Estoque</h2>
    <p><a href="painel.php">⬅ Voltar ao Painel</a></p>

    <h3>Adicionar Item</h3>
    <form method="post">
        Nome do Produto: <input type="text" name="produto" required><br><br>
        Quantidade: <input type="number" name="quantidade" min="1" required><br><br>
        Tipo de Aquisição: 
        <select name="origem" required>
            <option value="compra">Compra</option>
            <option value="doacao">Doação</option>
        </select><br><br>
        <button type="submit" name="adicionar">Adicionar</button>
    </form>

    <h3>Itens no Estoque</h3>
    <table border="1" cellpadding="8">
        <tr>
            <th>ID</th>
            <th>Produto</th>
            <th>Quantidade</th>
            <th>Origem</th>
            <th>Data de Registro</th>
            <th>Ações</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()) { ?>
        <tr>
            <td><?php echo $row['id']; ?></td>
            <td><?php echo $row['produto']; ?></td>
            <td><?php echo $row['quantidade']; ?></td>
            <td><?php echo ucfirst($row['origem']); ?></td>
            <td><?php echo $row['data_registro']; ?></td>
            <td>
                <a href="editar_estoque.php?id=<?php echo $row['id']; ?>">Editar</a> | 
                <a href="estoque.php?deletar=<?php echo $row['id']; ?>" onclick="return confirm('Deseja excluir este item?')">Excluir</a>
            </td>
        </tr>
        <?php } ?>
    </table>
</body>
</html>