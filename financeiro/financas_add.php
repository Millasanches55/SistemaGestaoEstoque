<?php
// Ativa exibição de erros (importante durante desenvolvimento)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Inclui o arquivo de conexão com o banco de dados
include __DIR__ . '/../conexao.php';

// Inicia a sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verifica se o usuário está logado corretamente
if (!isset($_SESSION['id_usuario']) || !isset($_SESSION['id_terreiro'])) {
    header("Location: ../index.php");
    exit();
}

// Recupera o ID do terreiro a partir da sessão
$id_terreiro = $_SESSION['id_terreiro'];

// Variável para armazenar mensagens
$mensagem = '';

// Processamento do formulário
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Verifica se todos os campos foram preenchidos
    if (!empty($_POST['tipo']) && !empty($_POST['descricao']) && !empty($_POST['valor']) && !empty($_POST['data'])) {

        // Obtém os dados do formulário
        $tipo = $_POST['tipo'];
        $descricao = $_POST['descricao'];
        $valor = floatval($_POST['valor']);
        $data = $_POST['data'];

        // Verifica se a conexão está OK
        if ($conn->connect_error) {
            $mensagem = "Erro de conexão: " . $conn->connect_error;
        } else {
            // Prepara a query
            $sql = "INSERT INTO financas (id_terreiro, tipo, descricao, valor, data) VALUES (?, ?, ?, ?, ?)";

            if ($stmt = $conn->prepare($sql)) {
                // Faz o bind dos parâmetros
                $stmt->bind_param("issds", $id_terreiro, $tipo, $descricao, $valor, $data);

                // Executa e verifica sucesso
                if ($stmt->execute()) {
                    $mensagem = "✅ Movimentação adicionada com sucesso!";
                } else {
                    $mensagem = "❌ Erro ao executar: " . $stmt->error;
                }

                $stmt->close();
            } else {
                $mensagem = "❌ Erro na preparação da query: " . $conn->error;
            }
        }
    } else {
        $mensagem = "⚠️ Preencha todos os campos obrigatórios.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adicionar Movimentação</title>
    <link rel="stylesheet" href="../style.css">
</head>
    <body>
        <section>
            <h2>Adicionar Movimentação Financeira</h2>

            <!-- Exibição de mensagens -->
            <?php if (!empty($mensagem)): ?>
                <div class="message-box">
                    <p><?php echo $mensagem; ?></p>
                </div>
            <?php endif; ?>

            <!-- Formulário de envio -->
            <form class="container" method="POST" action="index.php?action=add">

                <div class="form-group">
                    <label for="tipo">Tipo:</label>
                    <select id="tipo" name="tipo" required>
                        <option value="arrecadacao">Arrecadação</option>
                        <option value="despesa">Despesa</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="descricao">Descrição:</label>
                    <input type="text" id="descricao" name="descricao" required>
                </div>

                <div class="form-group">
                    <label for="valor">Valor:</label>
                    <input type="number" id="valor" name="valor" step="0.01" required>
                </div>

                <div class="form-group">
                    <label for="data">Data:</label>
                    <input type="date" id="data" name="data" required>
                </div>

                <div class="form-group">
                    <button type="submit" class="botao">💾 Salvar</button>
                </div>
            </form>
        </section>
    </body>
</html>
