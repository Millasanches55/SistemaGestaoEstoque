<?php
session_start();
include("conexao.php");

// Redireciona se o usuário não estiver logado ou não tiver o tipo correto
if (!isset($_SESSION["id_usuario"]) || !in_array($_SESSION["tipo"], ["adm", "auxiliar"])) {
    header("Location: index.php");
    exit;
}

$id_terreiro = $_SESSION["id_terreiro"];

// --- Processar Ações (Adicionar/Remover) ---
if (isset($_POST['acao'])) {
    $produto = $_POST['produto'] ?? null;
    $quantidade = $_POST['quantidade'] ?? 0;
    $acao = $_POST['acao'];
    $origem = $_POST['origem'] ?? null;
    
    // Inicia a transação para garantir que ambas as operações sejam concluídas
    $conn->begin_transaction();

    try {
        // Encontra o ID do produto no estoque para registrar no histórico
        $sql_find_product = "SELECT id, quantidade FROM estoque WHERE id_terreiro = ? AND produto = ?";
        $stmt_find = $conn->prepare($sql_find_product);
        $stmt_find->bind_param("is", $id_terreiro, $produto);
        $stmt_find->execute();
        $result_find = $stmt_find->get_result();
        // Busca todos os produtos do mesmo terreiro
    } catch (Exception $e) {
        $conn->rollback();
        echo "<p style='color: red; display: flex;'>Erro: " . $e->getMessage() . "</p><br>";
    }
}
$sql_find_product = "SELECT id, produto, quantidade FROM estoque WHERE id_terreiro = ?";
$stmt_find = $conn->prepare($sql_find_product);
$stmt_find->bind_param("i", $id_terreiro);
$stmt_find->execute();
$result_find = $stmt_find->get_result();

$produto_normalizado = normalizar_nome($produto);
$produto_encontrado = null;

// Percorre todos e compara de forma tolerante
while ($row = $result_find->fetch_assoc()) {
    $existente_normalizado = normalizar_nome($row['produto']);

    // 1️⃣ Igualdade direta (após normalização)
    if ($produto_normalizado === $existente_normalizado) {
        $produto_encontrado = $row;
        break;
    }

    // 2️⃣ Comparação aproximada (aceita pequenas diferenças)
    similar_text($produto_normalizado, $existente_normalizado, $percent);
    if ($percent > 90) { // pode ajustar esse limiar
        $produto_encontrado = $row;
        break;
    }
}

$id_estoque = null;
$nova_quantidade = $quantidade;
$tipo_historico = ($acao === 'adicionar') ? 'entrada' : 'saida';

if ($produto_encontrado) {
    $id_estoque = $produto_encontrado['id'];
    $quantidade_atual = $produto_encontrado['quantidade'];
    

    try{
        $id_estoque = null;
        $nova_quantidade = $quantidade;
        $tipo_historico = ($acao === 'adicionar') ? 'entrada' : 'saida';

        if ($row = $result_find->fetch_assoc()) {
            $id_estoque = $row['id'];
            $quantidade_atual = $row['quantidade'];
            
            if ($acao === 'adicionar') {
                $nova_quantidade = $quantidade_atual + $quantidade;
            } else {
                if ($quantidade > $quantidade_atual) {
                    throw new Exception("Quantidade de saída maior que a quantidade em estoque.");
                }
                $nova_quantidade = $quantidade_atual - $quantidade;
            }

            // Atualiza a quantidade no estoque principal
            $sql_update_estoque = "UPDATE estoque SET quantidade = ? WHERE id = ?";
            $stmt_update = $conn->prepare($sql_update_estoque);
            $stmt_update->bind_param("di", $nova_quantidade, $id_estoque);
            $stmt_update->execute();
        } else {
            // Se o produto não existe, insere um novo (somente para ação de adicionar)
            if ($acao === 'adicionar') {
                $sql_insert_estoque = "INSERT INTO estoque (id_terreiro, produto, quantidade, origem) VALUES (?, ?, ?, ?)";
                $stmt_insert = $conn->prepare($sql_insert_estoque);
                $stmt_insert->bind_param("isis", $id_terreiro, $produto, $quantidade, $origem);
                $stmt_insert->execute();
                $id_estoque = $conn->insert_id;
            } else {
                throw new Exception("Produto não encontrado no estoque para a ação de remoção.");
            }
        }
        $stmt_find->close();

        // Insere o registro na tabela de histórico
        if ($id_estoque) {
            $sql_historico = "INSERT INTO estoque_historico (id_estoque, quantidade, tipo) VALUES (?, ?, ?)";
            $stmt_historico = $conn->prepare($sql_historico);
            $stmt_historico->bind_param("ids", $id_estoque, $quantidade, $tipo_historico);
            $stmt_historico->execute();
            $stmt_historico->close();
        }

        $conn->commit();
        echo "<p style='color: green; display: flex;'>Movimentação de estoque registrada com sucesso.</p><br>";

    } catch (Exception $e) {
        $conn->rollback();
        echo "<p style='color: red; display: flex;'>Erro na movimentação: " . $e->getMessage() . "</p><br>";
    }
}

// --- DELETAR ITEM ---
if (isset($_GET['deletar'])) {
    $id = $_GET['deletar'];
    $sql = "DELETE FROM estoque WHERE id = ? AND id_terreiro = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $id, $id_terreiro);
    $stmt->execute();
}

// --- LISTAR ITENS ---
$sql = "SELECT * FROM estoque WHERE id_terreiro = ? ORDER BY id";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_terreiro);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gerenciar Estoque</title>
    <link rel="stylesheet" href="style.css">
    <link href='https://cdn.boxicons.com/fonts/basic/boxicons.min.css' rel='stylesheet'>
    <style>
        .origem-field {
            display: block;
        }
    </style>
</head>
<body>
    <section>
        <h2><i class='bx bx-box-alt'></i> Gerenciar Estoque</h2>
        <p><a class="botao" href="painel.php"><i class='bx bx-arrow-left-stroke-circle'></i> Voltar ao Painel</a></p>
        <br>
        <h3>Registrar Movimentação</h3>
        <br>
        <form method="post">
            Ação:
            <select name="acao" id="acao-select" onchange="toggleOrigemField()">
                <option value="adicionar">Adicionar (Entrada)</option>
                <option value="remover">Remover (Saída)</option>
            </select><br><br>
            Nome do Produto: <input type="text" name="produto" required><br><br>
            Quantidade: <input type="number" name="quantidade" min="1" required><br><br>
            <div id="origem-field" class="origem-field">
                Tipo de Aquisição:
                <select name="origem">
                    <option value="compra">Compra</option>
                    <option value="doacao">Doação</option>
                </select><br><br>
            </div>
            <button class="botao" type="submit">Salvar</button>
        </form>
        <br>
        <h3>Itens no Estoque</h3>
        <br>
        <table id="tabela-estoque">
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
                <td><?php echo htmlspecialchars($row['produto']); ?></td>
                <td><?php echo htmlspecialchars($row['quantidade']); ?></td>
                <td><?php echo ucfirst(htmlspecialchars($row['origem'])); ?></td>
                <td><?php echo htmlspecialchars($row['data_registro']); ?></td>
                <td>
                    <a href="editar_estoque.php?id=<?php echo $row['id']; ?>">Editar</a> |
                    <a href="estoque.php?deletar=<?php echo $row['id']; ?>" onclick="return confirm('Deseja excluir este item?')">Excluir</a>
                </td>
            </tr>
            <?php } ?>
        </table>
    </section>

    <script>
        function toggleOrigemField() {
            const acao = document.getElementById('acao-select').value;
            const origemField = document.getElementById('origem-field');
            if (acao === 'adicionar') {
                origemField.style.display = 'block';
            } else {
                origemField.style.display = 'none';
            }
        }
    </script>
</body>
</html>




