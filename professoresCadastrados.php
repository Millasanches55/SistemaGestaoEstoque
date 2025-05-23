<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel='stylesheet' href='style.css'/>
    <title>Professores Cadastrados</title>
</head>
<body>
    <section>
    <br>
    <a href="index.php" class='botao'>← Voltar para lista de TCCs</a>
<?php
require_once 'Professor.php';

// Ativa exibição de erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    $pdo = new PDO("mysql:host=localhost;dbname=tcc_db;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Junta informações da tabela Professor com Tcc (para obter o título)
    $stmt = $pdo->query("
        SELECT tcc.titulo, professor.*
        FROM Professor professor
        JOIN Tcc tcc ON tcc.codTcc = professor.codTcc
    ");
    
    echo "<h2>Professores Cadastrados por TCC</h2>";
    echo "<hr>";

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<div id='professores-div'>";
        echo "<h3>Título do TCC: " . htmlspecialchars($row['titulo']) . "</h3>";
        echo "<ul>";

        $tipos = ['orientador', 'coorientador', 'profConvidado1', 'profConvidado2'];
        foreach ($tipos as $tipo) {
            if (!empty($row[$tipo])) {
                $prof = new Professor($row[$tipo], ucfirst($tipo));
                echo "<li>" . $prof->exibirProfessor() . "</li>";
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
