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
    // Conexão PDO (Critério 9.1)
    $pdo = new PDO("mysql:host=localhost;dbname=tcc_db;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Junta informações da tabela Professor com Tcc (para obter o título)
    // SELECT com JOIN (Critério 9.2)
    $stmt = $pdo->query("
        SELECT tcc.curso, tcc.titulo, aluno.*
        FROM Aluno aluno
        JOIN Tcc tcc ON tcc.codTcc = aluno.codTcc
    ");
    
    echo "<h2>Alunos Cadastrados por TCC</h2>";

    echo "<hr>";


    // While (Critério 5.3)
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<div class='cartao-tcc'>";

        // String concatenation (Critério 3.2)
echo "<h3>Título do TCC: <span>" . htmlspecialchars($row['titulo']) . "</span></h3>";
echo "<h4>Curso: <span>" . htmlspecialchars($row['curso']) . "</span></h4>";
echo "<ul class='lista-alunos'>";


// Array e Foreach (Critérios 4.1 e 5.2)
$tipos = ['aluno1', 'aluno2', 'aluno3'];
foreach ($tipos as $tipo) {
    // If (Critério 6.1)
    if (!empty($row[$tipo])) {

        // Instanciação de objeto (Critério 7.4)
        // CamelCase (Critério 2.1)
        $aluno = new Aluno($row[$tipo], ucfirst($tipo));

        // Método de Classe (Critério 7.1)
        echo "<li>👨‍🎓 " . $aluno->exibirDados() . "</li>";
    }
}

echo "</ul>";
echo "</div>";

    }

} catch (PDOException $e) {
    // Exibição de erro com string (Critério 3.2)
    echo "Erro ao conectar ou buscar dados: " . $e->getMessage();
}
?>

</section>

</body>
</html>