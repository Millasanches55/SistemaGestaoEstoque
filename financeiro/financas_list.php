<?php
// Inclui o arquivo de conexão do banco, que deve existir na mesma pasta ou em um caminho acessível
include __DIR__ . '/../conexao.php';

// Inicia a sessão para garantir que o ID do terreiro está disponível
session_start();

// Verifica se o usuário está logado. Se não, redireciona para a página de login.
if (!isset($_SESSION['id_usuario']) || !isset($_SESSION['id_terreiro'])) {
    header("Location: ../index.php");
    exit();
}

$id_terreiro = $_SESSION['id_terreiro'] ?? 1;

// Busca as movimentações financeiras no banco de dados para o terreiro logado
// Agora, a consulta também seleciona o campo 'tipo_original'
$movimentacoes = [];
$sql = "SELECT id, descricao, tipo, valor, data, tipo_original FROM financas WHERE id_terreiro = ? ORDER BY data DESC";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $id_terreiro);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $movimentacoes[] = $row;
    }
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Lista de Movimentações</title>
    <!-- Inclui o arquivo de estilos CSS -->
    <link rel="stylesheet" href="estilo.css">
</head>
<body>
    <div class="container">
        <h2>Lista de Movimentações</h2>
        <table class="historico-table">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Descrição</th>
                    <th>Tipo</th>
                    <th>Valor</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($movimentacoes)): ?>
                    <tr>
                        <td colspan="4">Nenhuma movimentação encontrada.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($movimentacoes as $mov): ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($mov['data'])); ?></td>
                            <td><?php echo htmlspecialchars($mov['descricao']); ?></td>
                            <td>
                                <?php 
                                    // Se 'tipo_original' existir, exibe o tipo de estoque
                                    if ($mov['tipo_original'] === 'estoque_entrada') {
                                        echo 'Entrada de Estoque';
                                    } else if ($mov['tipo_original'] === 'estoque_saida') {
                                        echo 'Saída de Estoque';
                                    } else {
                                        // Caso contrário, exibe o tipo financeiro padrão
                                        echo ucfirst($mov['tipo']); 
                                    }
                                ?>
                            </td>
                            <td>
                                <span style="color: <?php echo ($mov['tipo'] == 'arrecadacao') ? 'green' : 'red'; ?>;">
                                    R$ <?php echo number_format($mov['valor'], 2, ',', '.'); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
