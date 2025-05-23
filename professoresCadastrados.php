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

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<div style='border: 1px solid #ccc; padding: 10px; margin-bottom: 15px; border-radius: 8px; background-color: #f9f9f9;'>";
        echo "<h3 style='margin-top: 0;'>Título do TCC: " . htmlspecialchars($row['titulo']) . "</h3>";
        echo "<ul style='list-style-type: none; padding-left: 0;'>";

        $tipos = ['orientador', 'coorientador', 'profConvidado1', 'profConvidado2'];
        foreach ($tipos as $tipo) {
            if (!empty($row[$tipo])) {
                $prof = new Professor($row[$tipo], ucfirst($tipo));
                echo "<li>" . $prof->exibirProfessor() . "</li>";
            }
        }

        echo "</ul>";
        echo "</div>";
    }

} catch (PDOException $e) {
    echo "Erro ao conectar ou buscar dados: " . $e->getMessage();
}
?>


