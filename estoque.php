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
    $texto = trim(mb_strtolower($texto ?? '', 'UTF-8'));
    // remover acentuação via iconv quando disponível
    $trans = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);
    if ($trans !== false) $texto = $trans;
    // remover caracteres não alfanuméricos exceto espaços
    $texto = preg_replace('/[^a-z0-9\s]/', '', $texto);
    // normalizar múltiplos espaços
    $texto = preg_replace('/\s+/', ' ', $texto);
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
            if (!$stmt_all) throw new Exception("Erro preparação SQL: " . $conn->error);
            $stmt_all->bind_param("i", $id_terreiro);
            $stmt_all->execute();
            $result_all = $stmt_all->get_result();

            $id_estoque = null;
            $quantidade_atual = 0;
            $produto_encontrado = null;
            $maior_similaridade = 0;
            $menor_distancia = PHP_INT_MAX;

            // Verifica similaridade com todos os produtos
            while ($row = $result_all->fetch_assoc()) {
                $existente_normalizado = normalizarTexto($row['produto']);

                // similar_text fornece percentual; levenshtein fornece distância
                similar_text($produto_normalizado, $existente_normalizado, $percent);
                $distance = levenshtein($produto_normalizado, $existente_normalizado);

                // Critério: percentual alto OU distância pequena
                if ($percent >= 85 || $distance <= 2) {
                    // preferir maior percent ou menor distância quando empate
                    if ($percent > $maior_similaridade || ($percent == $maior_similaridade && $distance < $menor_distancia)) {
                        $maior_similaridade = $percent;
                        $menor_distancia = $distance;
                        $id_estoque = (int)$row['id'];
                        $quantidade_atual = (int)$row['quantidade'];
                        $produto_encontrado = $row['produto'];
                    }
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

                // Atualiza apenas a quantidade (mantém o nome original existente)
                $sql_update = "UPDATE estoque SET quantidade = ? WHERE id = ? AND id_terreiro = ?";
                $stmt_update = $conn->prepare($sql_update);
                if (!$stmt_update) throw new Exception("Erro preparação UPDATE: " . $conn->error);
                $stmt_update->bind_param("iii", $nova_quantidade, $id_estoque, $id_terreiro);
                $stmt_update->execute();
                $stmt_update->close();

            } else {
                // Cria novo produto (somente para adicionar)
                if ($acao === 'adicionar') {
                    $tipo_historico = 'estoque_entrada';
                    $sql_insert = "INSERT INTO estoque (id_terreiro, produto, quantidade, origem) VALUES (?, ?, ?, ?)";
                    $stmt_insert = $conn->prepare($sql_insert);
                    if (!$stmt_insert) throw new Exception("Erro preparação INSERT: " . $conn->error);
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
                if (!$stmt_hist) throw new Exception("Erro preparação hist: " . $conn->error);
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

$tema = $_SESSION['tema'];
$fontep = $_SESSION['fontep'];
$fonteh2 = $_SESSION['fonteh2'];
$fonteh3 = $_SESSION['fonteh3'];
$icone_tema = "<i class='bx  bx-moon' style='font-size: 20px;' ></i>";
$icone_fonte = "+A";

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
        }
        else {
            $fontep = "15px";
            $fonteh2 = "25px";
            $fonteh3 = "20px";
            $icone_fonte = "+A";
            $_SESSION["fontep"] = $fontep;
            $_SESSION["fonteh2"] = $fonteh2;
            $_SESSION["fonteh3"] = $fonteh3;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gerenciar Estoque</title>
    <link rel="stylesheet" href="<?php echo $tema; ?>">
    <link href='https://cdn.boxicons.com/fonts/basic/boxicons.min.css' rel='stylesheet'>
    <style>
        .origem-field { display: block; }
    </style>
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
        <h2><i class='bx bx-box-alt'></i> Gerenciar Estoque</h2>
        <p><a class="botao" href="painel.php"><i class='bx bx-arrow-left-stroke-circle'></i> Voltar ao Painel</a></p>
        <br>
        <h3>Registrar Movimentação</h3>
        <br>
        <form method="post">
            <p>Ação:</p>
            <select name="acao" class="input-texto" id="acao-select" onchange="toggleOrigemField()">
                <option value="adicionar">Adicionar (Entrada)</option>
                <option value="remover">Remover (Saída)</option>
            </select><br><br>
            <p>Nome do Produto:</p> <input type="text" class="input-texto" name="produto" required><br><br>
            <p>Quantidade:</p> <input type="number" class="input-texto" name="quantidade" min="1" required><br><br>
            <div id="origem-field" class="origem-field">
                <p>Tipo de Aquisição:</p>
                <select name="origem" class="input-texto">
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
                    <a href="editar_estoque.php?id=<?php echo $row['id']; ?>" class="link-estoque">Editar</a> |
                    <a href="estoque.php?deletar=<?php echo $row['id']; ?>" onclick="return confirm('Deseja excluir este item?')" class="link-estoque">Excluir</a>
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
