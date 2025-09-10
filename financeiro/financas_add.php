<?php
// Inclui o arquivo de conexão do banco, que deve existir na mesma pasta ou em um caminho acessível
include __DIR__ . '/../conexao.php';

// Inicia a sessão para garantir que o ID do terreiro está disponível

// Verifica se o usuário está logado. Se não, redireciona para a página de login.
if (!isset($_SESSION['id_usuario']) || $_SESSION["tipo"] !== "adm") {
    header("Location: ../index.php");
    exit();
}

$id_terreiro = $_SESSION['id_terreiro'] ?? 1;

// Processa o formulário se ele foi submetido via POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['tipo']) && isset($_POST['descricao']) && isset($_POST['valor']) && isset($_POST['data'])) {
        $tipo = $_POST['tipo'];
        $descricao = $_POST['descricao'];
        $valor = $_POST['valor'];
        $data = $_POST['data'];
        $produto = $_POST['produto'] ?? null;
        $quantidade = $_POST['quantidade'] ?? 0;
        
        // Define o tipo original da movimentação para ser salvo no banco de dados
        //$tipo_original = $tipo;

        // Determina o tipo financeiro a ser inserido na tabela 'financas'
        $tipo_financeiro = $tipo;
        if ($tipo === 'estoque_entrada') {
            $tipo_financeiro = 'despesa';
        } elseif ($tipo === 'estoque_saida') {
            $tipo_financeiro = 'arrecadacao';
        }

        // Inserir na tabela de finanças
        // Agora, a query salva também o tipo_original
        $sql = "INSERT INTO financas (id_terreiro, tipo, descricao, valor, data) VALUES (?, ?, ?, ?, ?)";
        
        if ($stmt = $conn->prepare($sql)) {
            // Usa o tipo financeiro e o tipo original corretos para o banco de dados
            $stmt->bind_param("issdss", $id_terreiro, $tipo_financeiro, $descricao, $valor, $data);
            if ($stmt->execute()) {
                // Inserir ou atualizar no estoque se for entrada ou saída
                if ($tipo === 'estoque_entrada' || $tipo === 'estoque_saida') {
                    $quantidade_final = ($tipo === 'estoque_entrada') ? $quantidade : -$quantidade;
                    
                    // Verificar se o produto já existe
                    $sql_check = "SELECT quantidade FROM estoque WHERE id_terreiro = ? AND produto = ?";
                    if ($stmt_check = $conn->prepare($sql_check)) {
                        $stmt_check->bind_param("is", $id_terreiro, $produto);
                        $stmt_check->execute();
                        $result_check = $stmt_check->get_result();
                        if ($row = $result_check->fetch_assoc()) {
                            // Produto existe, então atualiza a quantidade
                            $nova_quantidade = $row['quantidade'] + $quantidade_final;
                            $sql_estoque = "UPDATE estoque SET quantidade = ? WHERE id_terreiro = ? AND produto = ?";
                            if ($stmt_estoque = $conn->prepare($sql_estoque)) {
                                $stmt_estoque->bind_param("dis", $nova_quantidade, $id_terreiro, $produto);
                                $stmt_estoque->execute();
                                $stmt_estoque->close();
                            }
                        } else {
                            // Produto não existe, insere um novo
                            $sql_estoque = "INSERT INTO estoque (id_terreiro, produto, quantidade, data_registro) VALUES (?, ?, ?, ?)";
                            if ($stmt_estoque = $conn->prepare($sql_estoque)) {
                                $stmt_estoque->bind_param("isss", $id_terreiro, $produto, $quantidade_final, $data);
                                $stmt_estoque->execute();
                                $stmt_estoque->close();
                            }
                        }
                        $stmt_check->close();
                    }
                }
                
                // Redireciona para a lista de movimentações após a inserção
                header("Location: index.php?action=list");
                exit();
            } else {
                echo "Erro ao salvar os dados: " . $stmt->error;
            }
            $stmt->close();
        } else {
            echo "Erro na preparação da query: " . $conn->error;
        }
    } else {
        echo "Por favor, preencha todos os campos.";
    }
}

// Fecha a conexão com o banco de dados
$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Adicionar Movimentação</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .estoque-field {
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Adicionar Movimentação Financeira</h2>
        <form method="POST" action="index.php?action=add">
            <div class="form-group">
                <label for="tipo">Tipo:</label>
                <select id="tipo" name="tipo" required onchange="toggleEstoqueField()">
                    <option value="arrecadacao">Arrecadação</option>
                    <option value="despesa">Despesa</option>
                    <option value="estoque_entrada">Entrada de Estoque</option>
                    <option value="estoque_saida">Saída de Estoque</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="descricao">Descrição:</label>
                <input type="text" id="descricao" name="descricao" required>
            </div>
            
            <div class="form-group">
                <label for="valor">Valor:</label>
                <input type="number" step="0.01" id="valor" name="valor" required>
            </div>
            
            <div class="form-group">
                <label for="data">Data:</label>
                <input type="date" id="data" name="data" required>
            </div>
            
            <div class="form-group estoque-field" id="produto-field">
                <label for="produto">Produto:</label>
                <input type="text" id="produto" name="produto">
            </div>

            <div class="form-group estoque-field" id="quantidade-field">
                <label for="quantidade">Quantidade:</label>
                <input type="number" id="quantidade" name="quantidade" step="1">
            </div>
            
            <div class="form-group">
                <button type="submit">Salvar</button>
            </div>
        </form>
    </div>
    <script>
        function toggleEstoqueField() {
            const tipo = document.getElementById('tipo').value;
            const produtoField = document.getElementById('produto-field');
            const quantidadeField = document.getElementById('quantidade-field');
            if (tipo === 'estoque_entrada' || tipo === 'estoque_saida') {
                produtoField.style.display = 'block';
                quantidadeField.style.display = 'block';
            } else {
                produtoField.style.display = 'none';
                quantidadeField.style.display = 'none';
            }
        }
    </script>
</body>
</html>
