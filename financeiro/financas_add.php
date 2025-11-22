<?php
// Inclui o arquivo de conexão do banco, que deve existir na mesma pasta ou em um caminho acessível
include __DIR__ . '/../conexao.php';

// Verifica se o usuário está logado. Se não, redireciona para a página de login.
if (!isset($_SESSION['id_usuario']) || $_SESSION["tipo"] !== "adm") {
    header("Location: ../index.php");
    exit();
}

$id_terreiro = $_SESSION['id_terreiro'] ?? 1;

// Processa o formulário se ele foi submetido via POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Garante que todos os campos estão definidos
    // Captura o tipo de movimentação
    $tipo = $_POST['tipo'] ?? '';
    $produto_texto = $_POST['produto_texto'] ?? '';
    $produto_select = $_POST['produto_select'] ?? '';
    $quantidade = $_POST['quantidade'] ?? 0;
    $origem = $_POST['origem'] ?? 'compra';
    $descricao = $_POST['descricao'] ?? '';
    $valor = $_POST['valor'] ?? 0;
    $data = $_POST['data'] ?? date('Y-m-d');

    // define o produto com base no tipo
    if ($tipo === 'estoque_entrada') {
        $produto = $produto_texto; // novo produto digitado
    } elseif ($tipo === 'estoque_saida') {
        $produto = $produto_select; // produto selecionado
    } else {
        $produto = '';
    }

    // A variável $tipo_financeiro pode receber os valores mais longos pois a coluna `tipo` na tabela `financas` foi ajustada.
    $tipo_financeiro = $tipo;

    // Inicia a transação para garantir a integridade dos dados
    $conn->begin_transaction();

    try {
        // 1. Inserir na tabela de finanças
        $sql_financas = "INSERT INTO financas (id_terreiro, tipo, descricao, valor, data) VALUES (?, ?, ?, ?, ?)";
        
        if ($stmt_financas = $conn->prepare($sql_financas)) {
            $stmt_financas->bind_param("issds", $id_terreiro, $tipo_financeiro, $descricao, $valor, $data);
            $stmt_financas->execute();
            $stmt_financas->close();
        } else {
            throw new Exception("Erro na preparação da query de finanças: " . $conn->error);
        }

        // 2. Se for uma movimentação de estoque, atualiza a tabela 'estoque' e insere no histórico.
        if ($tipo === 'estoque_entrada' || $tipo === 'estoque_saida') {
            // Define "compra" como origem padrão para entradas
            if ($tipo === 'estoque_entrada' && empty($origem)) {
                $origem = 'compra';
            }
            // Validação: precisa ter produto e quantidade válidos
            if (empty($produto) || empty($quantidade)) {
                throw new Exception("Por favor, informe o produto e a quantidade para movimentação de estoque.");
            }

            $quantidade_movimentacao = ($tipo === 'estoque_entrada') ? $quantidade : -$quantidade;
            
            // Função para normalizar texto (remover acentos, espaços extras e deixar em minúsculas)
            function normalize_name($str) {
                $str = trim(mb_strtolower($str, 'UTF-8'));
                // remover acentuação
                $str = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $str);
                // remover múltiplos espaços
                $str = preg_replace('/\s+/', ' ', $str);
                return $str;
            }

            // Para entradas de estoque, tentamos encontrar produtos com grafia semelhante
            if ($tipo === 'estoque_entrada') {
                $produto_normal = normalize_name($produto);

                // Pegar todos os produtos do terreiro para comparar similaridade
                $sql_all = "SELECT id, produto, quantidade FROM estoque WHERE id_terreiro = ?";
                if ($stmt_all = $conn->prepare($sql_all)) {
                    $stmt_all->bind_param("i", $id_terreiro);
                    $stmt_all->execute();
                    $result_all = $stmt_all->get_result();

                    $found_match = false;
                    $matched_row = null;
                    while ($row_all = $result_all->fetch_assoc()) {
                        $db_name = $row_all['produto'];
                        $db_normal = normalize_name($db_name);

                        // similar_text para percentual de similaridade
                        similar_text($produto_normal, $db_normal, $percent);
                        $distance = levenshtein($produto_normal, $db_normal);

                        // Critério: percentual alto (>=85) ou distância pequena (<=2)
                        if ($percent >= 85 || $distance <= 2) {
                            $found_match = true;
                            $matched_row = $row_all;
                            break;
                        }
                    }
                    $stmt_all->close();

                    if ($found_match) {
                        // Se encontrou produto similar, atualiza a quantidade existente
                        $id_estoque = $matched_row['id'];
                        $nova_quantidade = $matched_row['quantidade'] + $quantidade; // entrada soma
                        $sql_estoque = "UPDATE estoque SET quantidade = ? WHERE id = ?";
                        $stmt_estoque = $conn->prepare($sql_estoque);
                        $stmt_estoque->bind_param("di", $nova_quantidade, $id_estoque);
                        $stmt_estoque->execute();
                        $stmt_estoque->close();
                    } else {
                        // Nenhum similar encontrado: insere novo produto
                        $sql_estoque = "INSERT INTO estoque (id_terreiro, produto, quantidade, origem) VALUES (?, ?, ?, ?)";
                        $stmt_estoque = $conn->prepare($sql_estoque);
                        $stmt_estoque->bind_param("isis", $id_terreiro, $produto, $quantidade, $origem);
                        $stmt_estoque->execute();
                        $id_estoque = $conn->insert_id;
                        $stmt_estoque->close();
                    }
                } else {
                    throw new Exception("Erro na preparação da query de verificação de estoque.");
                }
            } else {
                // Saída: manter verificação exata (produto_select)
                $sql_check = "SELECT id, quantidade FROM estoque WHERE id_terreiro = ? AND produto = ?";
                if ($stmt_check = $conn->prepare($sql_check)) {
                    $stmt_check->bind_param("is", $id_terreiro, $produto);
                    $stmt_check->execute();
                    $result_check = $stmt_check->get_result();

                    $id_estoque = null;

                    if ($row = $result_check->fetch_assoc()) {
                        // Produto existe, então atualiza a quantidade
                        $id_estoque = $row['id'];
                        $nova_quantidade = $row['quantidade'] + $quantidade_movimentacao;
                        $sql_estoque = "UPDATE estoque SET quantidade = ? WHERE id = ?";
                        $stmt_estoque = $conn->prepare($sql_estoque);
                        $stmt_estoque->bind_param("di", $nova_quantidade, $id_estoque);
                        $stmt_estoque->execute();
                        $stmt_estoque->close();
                    } else {
                        throw new Exception("Produto não encontrado no estoque para a ação de saída.");
                    }
                    $stmt_check->close();
                } else {
                    throw new Exception("Erro na preparação da query de verificação de estoque.");
                }
            }

            // 3. Registrar a movimentação na tabela estoque_historico
            if ($id_estoque) {
                $tipo_historico = ($tipo === 'estoque_entrada') ? 'estoque_entrada' : 'estoque_saida';
                // Para histórico, sempre registrar a quantidade como positiva (a natureza já determina entrada/saída)
                $quant_historico = ($tipo === 'estoque_entrada') ? $quantidade : $quantidade;
                $sql_historico = "INSERT INTO estoque_historico (id_estoque, quantidade, tipo, data_registro) VALUES (?, ?, ?, NOW())";
                $stmt_historico = $conn->prepare($sql_historico);
                $stmt_historico->bind_param("ids", $id_estoque, $quant_historico, $tipo_historico);
                $stmt_historico->execute();
                $stmt_historico->close();
            } else {
                throw new Exception("Não foi possível obter o ID do estoque para registrar o histórico.");
            }
        }

        // Se tudo ocorreu bem, confirma as alterações no banco de dados.
        $conn->commit();
        echo "<p style='color: green;'>
                <i class='bx bx-check-circle'></i> Movimentação adicionada com sucesso.
            </p>";

        // Redireciona para a lista de movimentações após a inserção
        header("Location: index.php?action=list");
        exit();

    } catch (Exception $e) {
        $conn->rollback(); // Reverte todas as operações em caso de erro.
        echo "<p style='color: red;'>Erro: " . $e->getMessage() . "</p>";
    }
}

// Busca todos os produtos do estoque com suas quantidades
$produtos_estoque = [];
$sql_produtos = "SELECT produto, quantidade FROM estoque WHERE id_terreiro = ?";
$stmt_produtos = $conn->prepare($sql_produtos);
$stmt_produtos->bind_param("i", $id_terreiro);
$stmt_produtos->execute();
$result_produtos = $stmt_produtos->get_result();

while ($row = $result_produtos->fetch_assoc()) {
    $produtos_estoque[] = [
        'nome' => $row['produto'],
        'quantidade' => $row['quantidade']
    ];
}

$stmt_produtos->close();


// Fecha a conexão com o banco de dados
$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Adicionar Movimentação</title>
    <link rel="stylesheet" href="../<?php echo $tema; ?>">
    <link href='https://cdn.boxicons.com/fonts/basic/boxicons.min.css' rel='stylesheet'>
    <style>
        .estoque-field {
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2><i class='bx  bx-plus-circle'  ></i> Adicionar Movimentação Financeira</h2>
        <form method="POST" action="index.php?action=add">
            <div class="form-group">
                <h3>Tipo:</h3>
                <select id="tipo" name="tipo" required onchange="toggleEstoqueField()">
                    <option value="arrecadacao">Arrecadação</option>
                    <option value="despesa">Despesa</option>
                    <option value="estoque_entrada">Entrada de Estoque</option>
                    <option value="estoque_saida">Saída de Estoque</option>
                </select>
            </div>
            
            <div class="form-group">
                <h3>Descrição:</h3>
                <input type="text" id="descricao" name="descricao" required>
            </div>
            
            <div class="form-group">
                <h3>Valor:</h3>
                <input type="number" step="0.01" id="valor" name="valor" required>
            </div>
            
            <div class="form-group">
                <h3>Data:</h3>
                <input type="date" id="data" name="data" required>
            </div>
            
            <div class="form-group estoque-field" id="produto-field">
                <h3>Produto:</h3>
                
                <!-- Campo de texto (entrada de estoque) -->
                <input type="text" id="produto_texto" name="produto_texto" placeholder="Digite o nome do novo produto">

                <!-- Campo de seleção (saída de estoque) -->
                <select id="produto_select" name="produto_select">
                    <option value="">Selecione um produto</option>
                    <?php foreach ($produtos_estoque as $p): ?>
                        <option value="<?= htmlspecialchars($p['nome']) ?>">
                            <?= htmlspecialchars($p['nome']) ?> — <?= htmlspecialchars($p['quantidade']) ?> unid.
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>


            <div class="form-group estoque-field" id="quantidade-field">
                <h3>Quantidade:</h3>
                <input type="number" id="quantidade" name="quantidade" step="1">
            </div>
            
            <div class="form-group estoque-field" id="origem-field">
                <h3>Origem:</h3>
                <select id="origem" name="origem">
                    <option value="compra">Compra</option>
                    <option value="doacao">Doação</option>
                </select>
            </div>
            
            <div class="form-group">
                <br><br>
                <button type="submit" class="botao">Salvar</button>
            </div>
        </form>
    </div>
    <script>
        function toggleEstoqueField() {
            const tipo = document.getElementById('tipo').value;
            const produtoField = document.getElementById('produto-field');
            const produtoTexto = document.getElementById('produto_texto');
            const produtoSelect = document.getElementById('produto_select');
            const quantidadeField = document.getElementById('quantidade-field');
            const origemField = document.getElementById('origem-field');

            if (tipo === 'estoque_entrada' || tipo === 'estoque_saida') {
                produtoField.style.display = 'block';
                quantidadeField.style.display = 'block';

                if (tipo === 'estoque_entrada') {
                    // Mostrar input de texto e esconder o select
                    produtoTexto.style.display = 'block';
                    produtoSelect.style.display = 'none';
                    origemField.style.display = 'block';
                } else {
                    // Mostrar select e esconder o input
                    produtoTexto.style.display = 'none';
                    produtoSelect.style.display = 'block';
                    origemField.style.display = 'none';
                }
            } else {
                produtoField.style.display = 'none';
                quantidadeField.style.display = 'none';
                origemField.style.display = 'none';
            }
        }

        // Ao carregar a página, garantir o estado correto
        document.addEventListener("DOMContentLoaded", toggleEstoqueField);
    </script>
</body>
</html>
