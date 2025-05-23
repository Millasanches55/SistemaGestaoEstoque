<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel='stylesheet' href='style.css'/>
    <title>Alunos Cadastrados</title>
</head>
<body>
    <section>
    <br>
    <a href="index.php" class='botao'>← Voltar para lista de TCCs</a>
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
        SELECT tcc.curso, tcc.titulo, aluno.*
        FROM Aluno aluno
        JOIN Tcc tcc ON tcc.codTcc = aluno.codTcc
    ");
    
    echo "<h2>Alunos Cadastrados por TCC</h2>";

    echo "<hr>";

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<div>";
        echo "<h3>Título do TCC: " . htmlspecialchars($row['titulo']) . "</h3>";
        echo "<ul>";

        $tipos = ['aluno1', 'aluno2', 'aluno3'];
        foreach ($tipos as $tipo) {
            if (!empty($row[$tipo])) {
                $aluno = new Aluno($row[$tipo], ucfirst($tipo));
                echo "<li>" . $aluno->exibirDados() . "</li>";
            }
        }

        echo "</ul>";
        echo "</div>";
        echo "<hr>";
    }

} catch (PDOException $e) {
    echo "Erro ao conectar ou buscar dados: " . $e->getMessage();
}
?>

</section>

</body>
</html>