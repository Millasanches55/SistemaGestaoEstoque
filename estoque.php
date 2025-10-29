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
    $produto = trim($_POST['produto'] ?? '');
    $quantidade = $_POST['quantidade'] ?? 0;
    $acao = $_POST['acao'];
    $origem = $_POST['origem'] ?? null;

    // Normaliza nome (remove acentos e converte para minúsculas)
    function normalizarTexto($texto) {
        $texto = mb_strtolower($texto, 'UTF-8');
        $texto = preg_replace('/[áàãâä]/u', 'a', $texto);
        $texto = preg_replace('/[éèêë]/u', 'e', $texto);
        $texto = preg_replace('/[íìîï]/u', 'i', $texto);
        $texto = preg_replace('/[óòõôö]/u', 'o', $texto);
        $texto = preg_replace('/[úùûü]/u', 'u', $texto);
        $texto = preg_replace('/ç/u', 'c', $texto);
        return trim($texto);
    }

    $produto_normalizado = normalizarTexto($produto);

    $conn->begin_transaction();

    try {
        // Buscar todos os produtos do terreiro
        $sql_all = "SELECT id, produto, quantidade FROM estoque WHERE id_terreiro = ?";
        $stmt_all = $conn->prepare($sql_all);
        $stmt_all->bind_param("i", $id_terreiro);
        $stmt_all->execute();
        $result_all = $stmt_all->get_result();

        $id_estoque = null;
        $quantidade_atual = 0;
        $produto_encontrado = null;
        $maior_similaridade = 0;

        // Verifica similaridade com todos os produtos
        while ($row = $result_all->fetch_assoc()) {
            $existente_normalizado = normalizarTexto($row['produto']);
            similar_text($produto_normalizado, $existente_normalizado, $percent);
            if ($percent > 85 && $percent > $maior_similaridade) {
                $maior_similaridade = $percent;
                $id_estoque = $row['id'];
                $quantidade_atual = $row['quantidade'];
                $produto_encontrado = $row['produto'];
            }
        }
        $stmt_all->close();

        $nova_quantidade = $quantidade;
        $tipo_historico = ($acao === 'adicionar') ? 'estoque_entrada' : 'estoque_saida';

        if ($produto_encontrado) {
            // Produto similar encontrado → atualizar
            if ($acao === 'adicionar') {
                $nova_quantidade = $quantidade_atual + $quantidade;
            } else {
                if ($quantidade > $quantidade_atual) {
                    throw new Exception("Quantidade de saída maior que a quantidade em estoque.");
                }
                $nova_quantidade = $quantidade_atual - $quantidade;
            }

            $sql_update = "UPDATE estoque SET quantidade = ? WHERE id = ?";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param("di", $nova_quantidade, $id_estoque);
            $stmt_update->execute();

        } else {
            // Nenhum produto semelhante → criar novo
            if ($acao === 'adicionar') {
                $sql_insert = "INSERT INTO estoque (id_terreiro, produto, quantidade, origem) VALUES (?, ?, ?, ?)";
                $stmt_insert = $conn->prepare($sql_insert);
                $stmt_insert->bind_param("isis", $id_terreiro, $produto, $quantidade, $origem);
                $stmt_insert->execute();
                $id_estoque = $conn->insert_id;
            } else {
                throw new Exception("Produto não encontrado no estoque para a ação de remoção.");
            }
        }

        // Inserir histórico
        if ($id_estoque) {
            $sql_hist = "INSERT INTO estoque_historico (id_estoque, quantidade, tipo) VALUES (?, ?, ?)";
            $stmt_hist = $conn->prepare($sql_hist);
            $stmt_hist->bind_param("ids", $id_estoque, $quantidade, $tipo_historico);
            $stmt_hist->execute();
        }

        $conn->commit();
        echo "<p style='color: green;'>✅ Movimentação registrada com sucesso.</p>";

    } catch (Exception $e) {
        $conn->rollback();
        echo "<p style='color: red;'>Erro: " . $e->getMessage() . "</p>";
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