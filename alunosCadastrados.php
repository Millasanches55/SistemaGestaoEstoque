<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
     <a href="index.php">← Voltar para lista de TCCs</a>
    
</body>
</html>

<?php
require_once 'Aluno.php';

// Ativa exibição de erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    $pdo = new PDO("mysql:host=localhost;dbname=tcc_db;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Junta informações da tabela Professor com Tcc (para obter o título)
    $stmt = $pdo->query("
        SELECT tcc.titulo, aluno.*
        FROM Aluno aluno
        JOIN Tcc tcc ON tcc.codTcc = aluno.codTcc
    ");
    
    echo "<h2>Alunos Cadastrados por TCC</h2>";

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<div style='border: 1px solid #ccc; padding: 10px; margin-bottom: 15px; border-radius: 8px; background-color: #f9f9f9;'>";
        echo "<h3 style='margin-top: 0;'>Título do TCC: " . htmlspecialchars($row['titulo']) . "</h3>";
        echo "<ul style='list-style-type: none; padding-left: 0;'>";

        $tipos = ['aluno1', 'aluno2', 'aluno3'];
        foreach ($tipos as $tipo) {
            if (!empty($row[$tipo])) {
                $aluno = new Aluno($row[$tipo], ucfirst($tipo));
                echo "<li>" . $aluno->exibirDados() . "</li>";
            }
        }

        echo "</ul>";
        echo "</div>";
    }

} catch (PDOException $e) {
    echo "Erro ao conectar ou buscar dados: " . $e->getMessage();
}
?>