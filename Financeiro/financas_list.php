<?php
// Inclui o arquivo de conexão do banco
include 'conexao.php'; 

// Inicia a sessão para obter o ID do terreiro
session_start();

$id_terreiro = $_SESSION['id_terreiro'] ?? 1; // Exemplo de uso de ID padrão se a sessão não estiver definida
?>

<h2>Movimentações Financeiras</h2>
<a href="financas_add.php">➕ Adicionar Nova</a>

<table border="1" cellpadding="8">
    <tr>
        <th>Data</th>
        <th>Tipo</th>
        <th>Descrição</th>
        <th>Valor</th>
    </tr>
    <?php
    // Prepara a consulta SQL
    $sql = "SELECT * FROM financas WHERE id_terreiro = ? ORDER BY data DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_terreiro);
    $stmt->execute();
    $result = $stmt->get_result();

    // Exibe os dados em uma tabela
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['data']) . "</td>";
        echo "<td>" . htmlspecialchars($row['tipo']) . "</td>";
        echo "<td>" . htmlspecialchars($row['descricao']) . "</td>";
        echo "<td>R$ " . number_format($row['valor'], 2, ',', '.') . "</td>";
        echo "</tr>";
    }

    $stmt->close();
    $conn->close();
    ?>
</table>
