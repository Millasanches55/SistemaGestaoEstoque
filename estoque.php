<?php
session_start();
include("conexao.php");

// Redireciona se o usuário não estiver logado ou não tiver o tipo correto
if (!isset($_SESSION["id_usuario"]) || !in_array($_SESSION["tipo"], ["adm", "auxiliar"])) {
    header("Location: index.php");
    exit;
}

$id_terreiro = $_SESSION["id_terreiro"];

// Função de normalização (mantida fora do bloco POST para clareza)
function normalizarTexto($texto) {
    $texto = mb_strtolower($texto ?? '', 'UTF-8');
    $texto = preg_replace('/[áàãâä]/u', 'a', $texto);
    $texto = preg_replace('/[éèêë]/u', 'e', $texto);
    $texto = preg_replace('/[íìîï]/u', 'i', $texto);
    $texto = preg_replace('/[óòõôö]/u', 'o', $texto);
    $texto = preg_replace('/[úùûü]/u', 'u', $texto);
    $texto = preg_replace('/ç/u', 'c', $texto);
    return trim($texto);
}

// --- Processar Ações (Adicionar/Remover) ---
if (isset($_POST['acao'])) {
    $produto = trim($_POST['produto'] ?? '');
    $quantidade = (int)($_POST['quantidade'] ?? 0);
    $acao = $_POST['acao'];
    $origem = $_POST['origem'] ?? null;

    if ($produto === '' || $quantidade <= 0) {
        echo "<p style='color: red;'>Produto ou quantidade inválidos.</p>";
    } else {
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
                    $id_estoque = (int)$row['id'];
                    $quantidade_atual = (int)$row['quantidade'];
                    $produto_encontrado = $row['produto'];
                }
            }
            $stmt_all->close();

            // Inicializa variáveis do histórico
            $quantidade_anterior = $produto_encontrado ? $quantidade_atual : 0;
            $nova_quantidade = $quantidade_anterior;
            $quantidade_movimentada = $quantidade; // o valor efetivamente movimentado nesta operação

            if ($produto_encontrado) {
                // Atualiza produto existente
                if ($acao === 'adicionar') {
                    $nova_quantidade = $quantidade_atual + $quantidade;
                    $tipo_historico = 'estoque_entrada';
                } else { // remover
                    if ($quantidade > $quantidade_atual) {
                        throw new Exception("Quantidade de saída maior que a quantidade em estoque.");
                    }
                    $nova_quantidade = $quantidade_atual - $quantidade;
                    $tipo_historico = 'estoque_saida';
                }

                // Atualiza nome e quantidade (mantém origem atual)
                // Atualiza apenas a quantidade (mantém o nome original existente)
$sql_update = "UPDATE estoque SET quantidade = ? WHERE id = ? AND id_terreiro = ?";
$stmt_update = $conn->prepare($sql_update);
$stmt_update->bind_param("iii", $nova_quantidade, $id_estoque, $id_terreiro);
$stmt_update->execute();
$stmt_update->close();


            } else {
                // Cria novo produto (somente para adicionar)
                if ($acao === 'adicionar') {
                    $tipo_historico = 'estoque_entrada';
                    $sql_insert = "INSERT INTO estoque (id_terreiro, produto, quantidade, origem) VALUES (?, ?, ?, ?)";
                    $stmt_insert = $conn->prepare($sql_insert);
                    $stmt_insert->bind_param("isis", $id_terreiro, $produto, $quantidade, $origem);
                    $stmt_insert->execute();
                    $id_estoque = $conn->insert_id;
                    $stmt_insert->close();

                    $quantidade_anterior = 0;
                    $nova_quantidade = $quantidade; // depois da inserção
                    $quantidade_movimentada = $quantidade;
                } else {
                    throw new Exception("Produto não encontrado no estoque para a ação de remoção.");
                }
            }

            // Inserir histórico UMA ÚNICA vez, com os valores corretos
            if ($id_estoque) {
                $sql_hist = "INSERT INTO estoque_historico 
                             (id_estoque, quantidade, tipo, quantidade_anterior, quantidade_atual)
                             VALUES (?, ?, ?, ?, ?)";
                $stmt_hist = $conn->prepare($sql_hist);
                // binding: id_estoque (i), quantidade_movimentada (i), tipo_historico (s), quantidade_anterior (i), nova_quantidade (i)
                $stmt_hist->bind_param("iisii", $id_estoque, $quantidade_movimentada, $tipo_historico, $quantidade_anterior, $nova_quantidade);
                $stmt_hist->execute();
                $stmt_hist->close();
            }

            $conn->commit();
            echo "<p style='color: green;'>✅ Movimentação registrada com sucesso.</p>";

        } catch (Exception $e) {
            $conn->rollback();
            echo "<p style='color: red;'>Erro: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
}

// --- DELETAR ITEM ---
if (isset($_GET['deletar'])) {
    $id = intval($_GET['deletar']);
    $sql = "DELETE FROM estoque WHERE id = ? AND id_terreiro = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $id, $id_terreiro);
    $stmt->execute();
    $stmt->close();
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
        .origem-field { display: block; }
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
            origemField.style.display = (acao === 'adicionar') ? 'block' : 'none';
        }
    </script>
</body>
</html>
